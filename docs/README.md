# Training Videos Plugin Documentation

Internal docs for the `gm-training-videos` plugin. Plugin code, versioning, and changelog live in the repo root (`CLAUDE.md`, `README.md`, `training-videos.php`, `CHANGELOG.md`).

## Index

| Document | Purpose |
|----------|---------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Canonical "how the whole system works" — plugin ↔ portal flow, wire format, caching + grace logic, failure surfaces |
| [TECHNOLOGY-STACK.md](TECHNOLOGY-STACK.md) | What this project runs on — language, framework, hosting, integrations, secrets, deploy flow |
| [SITES.md](SITES.md) | Deployment registry — every client site running the plugin (interim, hand-maintained until all installs are on ≥v1.4.4) |
| [CLAUDE-ONBOARDING.md](CLAUDE-ONBOARDING.md) | Runbook for Claude Code agents asked to walk a client install through the onboarding wizard — where to find brand colors/fonts, how to fill each step, common post-wizard tweaks |

## Subdirectories

| Folder | Contents |
|--------|----------|
| [runbooks/](runbooks/) | Step-by-step operational procedures (onboard, revoke, release, debug) |

## Related

- Repo root [`CLAUDE.md`](../CLAUDE.md) — plugin overview, deployment workflow, versioning rules
- Repo root [`README.md`](../README.md) — installation + usage docs (human-facing)
- [Open cards](https://github.com/ericdowns/gm-training-videos/issues?q=is%3Aopen+is%3Aissue+label%3Acard) — backlog of plugin work
- **Companion repo** (registry server side): `~/Projects/gm-maintenance/` — see its `docs/TRAINING-VIDEOS.md`
- **Project hub** (cross-repo status): `~/.claude-royal/project-notes/training-videos/`
- `/loom` skill at `~/.claude-royal/skills/loom/` — Loom-side workflows
- `/technology-stack` skill — reusable doc generator for tech-stack inventories
