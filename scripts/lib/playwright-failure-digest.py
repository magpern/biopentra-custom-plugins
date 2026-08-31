#!/usr/bin/env python3
"""Digest Playwright JSON reporter into comparable failure rows."""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path


def _err_sig(message: str) -> str:
    text = re.sub(r"\x1b\[[0-9;]*m", "", message or "")
    first = text.strip().split("\n", 1)[0]
    return first[:240]


def walk(node: dict, prefix: str, rows: list[dict]) -> None:
    title = node.get("title") or ""
    path = f"{prefix} › {title}" if prefix and title else (title or prefix)
    for spec in node.get("specs") or []:
        spec_title = spec.get("title") or ""
        for test in spec.get("tests") or []:
            project = test.get("projectName") or ""
            results = test.get("results") or []
            expected = test.get("status") or test.get("expectedStatus") or ""
            final = results[-1] if results else {}
            status = final.get("status") or expected or "unknown"
            if status == "skipped":
                skip = (test.get("annotations") or [{}])
                reason = ""
                for ann in test.get("annotations") or []:
                    if ann.get("type") in ("skip", "fixme"):
                        reason = ann.get("description") or ""
                        break
                rows.append(
                    {
                        "kind": "skipped",
                        "project": project,
                        "title": spec_title,
                        "status": status,
                        "reason": reason,
                    }
                )
                continue
            if status != "passed":
                err = ""
                if isinstance(final.get("error"), dict):
                    err = _err_sig(final["error"].get("message") or "")
                rows.append(
                    {
                        "kind": "failed",
                        "project": project,
                        "title": spec_title,
                        "status": status,
                        "error": err,
                    }
                )
            else:
                rows.append(
                    {
                        "kind": "passed",
                        "project": project,
                        "title": spec_title,
                        "status": status,
                    }
                )
    for child in node.get("suites") or []:
        walk(child, path, rows)


def digest(path: Path) -> list[dict]:
    data = json.loads(path.read_text())
    rows: list[dict] = []
    for suite in data.get("suites") or []:
        walk(suite, "", rows)
    return rows


def key(row: dict) -> tuple[str, str, str]:
    return (row.get("project") or "", row.get("title") or "", row.get("kind") or "")


def main() -> int:
    if len(sys.argv) < 2:
        print("usage: playwright-failure-digest.py <report.json> [<report-b.json>]", file=sys.stderr)
        return 2
    a = digest(Path(sys.argv[1]))
    if len(sys.argv) == 2:
        for row in a:
            if row["kind"] == "failed":
                print(f"FAIL\t{row['project']}\t{row['title']}\t{row.get('error','')}")
            elif row["kind"] == "skipped":
                print(f"SKIP\t{row['project']}\t{row['title']}\t{row.get('reason','')}")
        return 0

    b = digest(Path(sys.argv[2]))
    map_a = {key(r): r for r in a if r["kind"] == "failed"}
    map_b = {key(r): r for r in b if r["kind"] == "failed"}
    only_a = sorted(set(map_a) - set(map_b))
    only_b = sorted(set(map_b) - set(map_a))
    both = sorted(set(map_a) & set(map_b))
    print(f"baseline_failures={len(map_a)}")
    print(f"feature_failures={len(map_b)}")
    print(f"shared_failures={len(both)}")
    print(f"only_baseline={len(only_a)}")
    print(f"only_feature={len(only_b)}")
    for k in both:
        ea = map_a[k].get("error") or ""
        eb = map_b[k].get("error") or ""
        match = "same" if ea == eb else "DIFFERENT-MESSAGE"
        print(f"SHARED\t{match}\t{k[0]}\t{k[1]}\t{ea}")
        if match != "same":
            print(f"  feature_error\t{eb}")
    for k in only_a:
        print(f"ONLY_BASELINE\t{k[0]}\t{k[1]}\t{map_a[k].get('error','')}")
    for k in only_b:
        print(f"ONLY_FEATURE\t{k[0]}\t{k[1]}\t{map_b[k].get('error','')}")
    return 0 if not only_a and not only_b else 1


if __name__ == "__main__":
    raise SystemExit(main())
