---
name: schema-evolution
description: "Use when: updating database migrations, persisted embedding shape, batch status schema, indexes, or compatibility expectations for stored package data."
---

# Schema Evolution Skill

## Quick Start

1. Define the storage change and compatibility level.
2. Update migrations or model casts in the owning module.
3. Add feature tests for persisted behavior.
4. Document operational impact in docs/examples.

## Scope

Use this skill for:

- Embeddings table changes
- Embedding batches table changes
- Index additions or changes
- Model cast changes affecting persisted data
- Migration compatibility concerns

Do not use this skill for:

- Internal refactors without storage impact
- Non-contract style-only changes
- Provider changes that do not alter persisted data

## Compatibility Policy

- Prefer additive migrations for minor releases.
- Avoid destructive table changes without explicit migration guidance.
- Keep existing persisted vectors readable unless a breaking release is planned.
- Document PostgreSQL-specific behavior separately from storage-only SQLite/MySQL behavior.

## Core Workflow

1. Capture before/after database shape.
2. Implement minimal migration/model changes.
3. Add feature tests under `tests/Feature/`.
4. Add docs when operators need to run, tune, or understand a migration/index.
5. Review release impact and downgrade behavior.

## Failure Playbook

- Migration fails on SQLite test database: keep package tests portable or branch by driver.
- PostgreSQL-only index is needed: document it as a host-application migration unless package-owned.
- Backward compatibility break: add compatibility layer or document migration path.

## Definition Of Done

- Persisted behavior is validated by tests.
- Compatibility impact is explicit.
- Consumer-facing docs/examples are updated.
