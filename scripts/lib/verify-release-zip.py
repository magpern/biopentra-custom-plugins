#!/usr/bin/env python3
"""Verify monorepo plugin production release ZIP contents."""
from __future__ import annotations

import re
import sys
import zipfile
from typing import Any

# Profile per plugin slug under plugins/.
DEFAULT_FORBIDDEN_SEGMENTS = frozenset(
    {
        ".git",
        "node_modules",
        "scripts",
        "tests",
        "docs",
        ".github",
        "build",
        "builds",
        "cli",
        ".phpcs-cache",
        ".phpunit.result.cache",
    }
)

PLUGIN_PROFILES: dict[str, dict[str, Any]] = {
    "wc-inventory-overview": {
        "version_constant": "WC_INVENTORY_OVERVIEW_VERSION",
        "required_dirs": frozenset({"includes", "assets"}),
        "required_files": frozenset({"CHANGELOG.md", "readme.txt", "LICENSE"}),
        "code_prefixes": ("includes/", "assets/"),
        "forbidden_segments": DEFAULT_FORBIDDEN_SEGMENTS,
    },
    "biopentra-storefront": {
        "version_constant": "BIOPENTRA_STOREFRONT_VERSION",
        "required_dirs": frozenset({"includes", "assets", "modules"}),
        "required_files": frozenset({"LICENSE", "readme.txt"}),
        "code_prefixes": ("includes/", "modules/", "assets/"),
        "forbidden_segments": DEFAULT_FORBIDDEN_SEGMENTS,
    },
}

FORBIDDEN_ROOT_FILES = frozenset(
    {
        "composer.json",
        "composer.lock",
        "composer.phar",
        "phpcs.xml.dist",
        "phpunit.xml.dist",
        "README.md",
        ".gitignore",
        ".editorconfig",
        ".cursorignore",
        ".write-test",
    }
)


def segment_violations(path: str, forbidden_segments: frozenset[str]) -> list[str]:
    parts = [p for p in path.split("/") if p]
    hits: list[str] = []
    for i, part in enumerate(parts):
        if part not in forbidden_segments:
            continue
        # Composer vendor/ at plugin root only — allow assets/vendor/ (Chart.js).
        if part == "vendor" and i > 0:
            continue
        hits.append(part)
    # Dev readmes at plugin root only — allow modules/*/README.md.
    if len(parts) == 1 and parts[0] in FORBIDDEN_ROOT_FILES:
        hits.append(parts[0])
    if parts:
        leaf = parts[-1]
        if leaf.startswith(".env"):
            hits.append(leaf)
        for suffix in (".log", ".sql", ".sql.gz", ".dump", ".sqlite"):
            if leaf.endswith(suffix):
                hits.append(leaf)
    return hits


def verify_version_in_main(
    zf: zipfile.ZipFile, main_path: str, expected_version: str | None, version_constant: str
) -> int:
    try:
        text = zf.read(main_path).decode("utf-8", errors="replace")
    except KeyError:
        print(f"ERROR: cannot read {main_path} from zip", file=sys.stderr)
        return 1

    header_match = re.search(
        r"^\s*\*\s*Version:\s*(.+)$", text, re.MULTILINE | re.IGNORECASE
    )
    const_match = re.search(
        rf"define\s*\(\s*['\"]{re.escape(version_constant)}['\"]\s*,\s*['\"]([^'\"]+)['\"]",
        text,
    )
    if not header_match or not const_match:
        print(
            f"ERROR: missing Version header or {version_constant} in {main_path}",
            file=sys.stderr,
        )
        return 1

    header_v = header_match.group(1).strip()
    const_v = const_match.group(1).strip()
    if header_v != const_v:
        print(
            f"ERROR: header Version ({header_v}) != {version_constant} ({const_v})",
            file=sys.stderr,
        )
        return 1
    if expected_version and header_v != expected_version:
        print(
            f"ERROR: zip version {header_v} != expected {expected_version}",
            file=sys.stderr,
        )
        return 1
    print(f"    version in zip: {header_v} ({version_constant})")
    return 0


def verify(
    zip_path: str,
    plugin_slug: str,
    expected_version: str | None = None,
) -> int:
    if plugin_slug not in PLUGIN_PROFILES:
        print(
            f"ERROR: no verify profile for plugin slug {plugin_slug!r}",
            file=sys.stderr,
        )
        return 1

    profile = PLUGIN_PROFILES[plugin_slug]
    forbidden_segments: frozenset[str] = profile["forbidden_segments"]
    required_dirs: frozenset[str] = profile["required_dirs"]
    required_files: frozenset[str] = profile["required_files"]
    version_constant: str = profile["version_constant"]

    root_prefix = f"{plugin_slug}/"
    main_file = f"{root_prefix}{plugin_slug}.php"

    with zipfile.ZipFile(zip_path) as zf:
        names = [n for n in zf.namelist() if n]

        if not names:
            print("ERROR: zip is empty", file=sys.stderr)
            return 1

        non_root = [n for n in names if not n.startswith(root_prefix)]
        if non_root:
            print(
                "ERROR: entries must live under",
                root_prefix,
                "examples:",
                non_root[:5],
                file=sys.stderr,
            )
            return 1

        if main_file not in names:
            print(f"ERROR: missing {main_file}", file=sys.stderr)
            return 1

        forbidden_hits: list[str] = []
        for name in names:
            rel = name[len(root_prefix) :] if name.startswith(root_prefix) else name
            if not rel:
                continue
            for hit in segment_violations(rel, forbidden_segments):
                forbidden_hits.append(f"{name} ({hit})")

        if forbidden_hits:
            print("ERROR: zip contains forbidden paths:", file=sys.stderr)
            for line in forbidden_hits[:20]:
                print(f"  - {line}", file=sys.stderr)
            if len(forbidden_hits) > 20:
                print(f"  ... and {len(forbidden_hits) - 20} more", file=sys.stderr)
            return 1

        dir_segments = set()
        for name in names:
            rel = name[len(root_prefix) :]
            if rel:
                dir_segments.add(rel.split("/")[0])

        missing_dirs = required_dirs - dir_segments
        if missing_dirs:
            print(
                "ERROR: zip missing required directories:",
                ", ".join(sorted(missing_dirs)),
                file=sys.stderr,
            )
            return 1

        missing_files = [
            req for req in required_files if f"{root_prefix}{req}" not in names
        ]
        if missing_files:
            print(
                "ERROR: zip missing required files:",
                ", ".join(sorted(missing_files)),
                file=sys.stderr,
            )
            return 1

        code_prefixes: tuple[str, ...] = profile.get(
            "code_prefixes", ("includes/",)
        )
        code_entries = sum(
            1
            for n in names
            if any(n.startswith(f"{root_prefix}{p}") for p in code_prefixes)
        )
        if code_entries < 1:
            print(
                "ERROR: zip has no files under expected code paths:",
                ", ".join(code_prefixes),
                file=sys.stderr,
            )
            return 1

        if (
            verify_version_in_main(
                zf, main_file, expected_version, version_constant
            )
            != 0
        ):
            return 1

        print(f"OK: {len(names)} entries under {root_prefix}")
        print(f"    main: {main_file}")
        print(f"    runtime paths: {code_entries} file(s)")
        print(
            "    forbidden segments absent:",
            ", ".join(sorted(forbidden_segments)),
        )
        return 0


def main() -> int:
    if len(sys.argv) < 3:
        print(
            "usage: verify-release-zip.py ZIP_PATH PLUGIN_SLUG [EXPECTED_VERSION]",
            file=sys.stderr,
        )
        return 2
    expected = sys.argv[3] if len(sys.argv) > 3 else None
    return verify(sys.argv[1], sys.argv[2], expected)


if __name__ == "__main__":
    sys.exit(main())
