# M3 production read-only inventory — procedure (safe)

**Purpose:** Collect the WP-D / Phase 3 inventory without printing secrets, PII, or expanded compose environment.

**Forbidden unless explicitly authorised:** any production write, restart, recreate, `.env*` edit, token rotation, plugin install, option change, mail send, Action Scheduler enqueue, HTTP token probe, or fixture.

## Never

- `docker compose config` (expands `env_file` / variable interpolation and can dump secrets)
- `docker compose config --environment` / unredacted `inspect` of `Env`
- `cat` / `printenv` of `.env`, `.env.worker`, or similar
- Dumping log bodies that may contain URI tokens or message content
- Pasting secret values into chat, Git, PR comments, or tickets

## Allowed inspection patterns

### Service topology (no env expansion)

```bash
docker compose ps --format '{{.Name}} {{.Service}} {{.Status}}'
# Ports only:
docker compose ps --format '{{.Name}} {{.Ports}}'
```

### Compose **structure** without values

Prefer reading `docker-compose.yml` / `compose.yml` and listing **key names only** from env files:

```bash
# Keys only — never print values
python3 - <<'PY'
from pathlib import Path
for p in (".env", ".env.worker"):
    path = Path(p)
    if not path.exists():
        print(p, "MISSING"); continue
    print("##", p, "keys")
    for line in path.read_text().splitlines():
        if not line.strip() or line.strip().startswith("#"):
            continue
        print(line.split("=", 1)[0])
PY
```

When quoting a compose service block in docs or chat, **redact** any line matching `TOKEN|PASS|PASSWORD|SECRET|KEY`.

### WP-CLI (read-only)

Use `./wp` for version/env/option **existence** and non-PII flags. Do not export customer data.

### Action Scheduler counts

Aggregate by hook/status only; never include args payloads.

## After inventory

- Record findings in `m3-production-readonly-inventory.md` (no secrets, no PII).
- If a command accidentally prints a secret: **stop**, do not paste it onward, note the incident in the inventory doc, and wait for an **explicit** operator decision on rotation (do not rotate unilaterally).
