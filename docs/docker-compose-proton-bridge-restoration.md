# Docker Compose — Proton Bridge service restoration

**Date:** 2026-05-15  
**Project:** `/home/magpern/woocommerce`  
**Compose file:** `docker-compose.yml` (live) + `custom-wordpress-plugins/deploy/docker-compose.yml` (versioned copy)

---

## What was missing

After the May 2026 compose reconstruction (post-cutover), `docker-compose.yml` listed `db`, `wordpress`, `wpcli`, and `biopentra-mail-worker` but **omitted the `proton-bridge` service** and the three Bridge **named volumes**.

Effects:

- `docker compose` reported an **orphan** `proton-bridge` container on every command.
- `biopentra-mail-worker` still referenced `IMAP_HOST: proton-bridge` without a declared `depends_on` for Bridge in compose.
- Risk: a future `docker compose up` could fail to manage Bridge lifecycle or accidentally recreate it without the correct volume bindings.

**The Bridge container was still running** — this was **compose drift**, not a stopped Bridge process.

---

## Current running state (inspected, no restarts)

| Container | Image | Status | Notes |
|-----------|--------|--------|--------|
| `proton-bridge` | `ghcr.io/magpern/proton-bridge:latest` | Up | `container_name: proton-bridge`; **no host ports** (1025/1143 internal only) |
| `woocommerce-biopentra-mail-worker-1` | `ghcr.io/magpern/proton-mail-worker:latest` | Up | IMAP → `proton-bridge:2143` |
| `woocommerce-wordpress-1` | `wordpress:php8.3-apache` | Up | Resolves `proton-bridge` → `172.19.0.2` on `bridge-net` |
| `woocommerce-db-1` | `mariadb:11.4` | Up | Default network only |

**Volumes (existing, preserved):**

| Compose key | Docker volume name |
|-------------|-------------------|
| `bridge_config` | `woocommerce_bridge_config` → `/root/.config/protonmail` |
| `bridge_gnupg` | `woocommerce_bridge_gnupg` → `/root/.gnupg` |
| `bridge_pass` | `woocommerce_bridge_pass` → `/root/.password-store` |

**Networks:** `proton-bridge` on `woocommerce_bridge-net` only; WordPress and mail worker on `default` + `bridge-net`.

**Containers restarted during this task:** **None.**

---

## Service restored in compose

```yaml
  proton-bridge:
    image: ghcr.io/magpern/proton-bridge:latest
    container_name: proton-bridge
    restart: unless-stopped
    volumes:
      - bridge_config:/root/.config/protonmail
      - bridge_gnupg:/root/.gnupg
      - bridge_pass:/root/.password-store
    networks:
      - bridge-net
    stdin_open: true
    tty: true
```

Also added **external** volume declarations for `bridge_config`, `bridge_gnupg`, `bridge_pass`, and `depends_on: proton-bridge` on `biopentra-mail-worker`.

Matches `plugins/biopentra-contact-inbox/docs/proton-bridge-docker.md`.

---

## Validation

```bash
cd /home/magpern/woocommerce
docker compose config -q
docker compose config | grep -i -C 8 proton
```

Expected: `image: ghcr.io/magpern/proton-bridge:latest`, no config errors, **no orphan warning** on subsequent `docker compose` commands once the file is saved.

---

## Safe reconciliation plan (when you choose to align Compose with running containers)

Because `proton-bridge` **already exists** with the correct name and volumes:

1. **Do not** run `docker compose up -d --force-recreate` on Bridge without a maintenance window.
2. Prefer verifying drift only:
   ```bash
   docker compose config -q
   docker compose ps -a
   ```
3. To attach the running container to project management **without recreate**, after config matches inspect:
   ```bash
   docker compose up -d --no-recreate proton-bridge biopentra-mail-worker
   ```
   If Compose reports the existing `proton-bridge` container as compatible, it should leave it running. If it proposes recreate, **stop** and compare `docker inspect proton-bridge` with `docker compose config` before proceeding.
4. Bridge login/GPG state lives in the three named volumes — **never** delete those volumes.

---

## Remaining risks

1. **Compose vs runtime drift** until someone runs `compose up` with `--no-recreate` or accepts orphan warnings cleared only by this file change.
2. **IMAP port mapping:** worker uses **2143** in compose env; Bridge image exposes **1143/tcp** internally — this mapping is image-specific and was working pre-incident; do not change ports without testing mail import.
3. **Secrets** in `.env` / `.env.worker` — not in git; back up before any stack recreate.
4. **Live `woocommerce/docker-compose.yml`** updated on disk; versioned copy in `deploy/docker-compose.yml` — keep them in sync on future edits.

---

## Related docs

- `plugins/biopentra-contact-inbox/docs/proton-bridge-docker.md`
- `docs/post-cutover-stabilization-report.md` (original orphan warning note)
- `docs/support-desk-menu-restoration.md`
