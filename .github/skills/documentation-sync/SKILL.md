---
name: documentation-sync
description: "Use when: syncing docs with real repository behavior; updating development guides after script/config changes; correcting architecture or workflow drift; and keeping docs trustworthy for contributors."
---

# Documentation Sync Skill

## Quick Start

1. Identify source of truth: composer scripts, hooks, workflow files, config, tests, or `src/` behavior.
2. Compare docs against actual implementation.
3. Update docs with minimal, accurate edits.
4. Verify examples and command names are executable.

## Repository Truth Sources

- `composer.json` scripts
- `captainhook.json` hook behavior
- `.github/workflows/*.yml` CI/release behavior
- `config/embedding.php` configuration
- `src/` implementation for runtime behavior docs
- `tests/` for supported behavior contracts

## Drift Prevention Rules

- Never invent command names not present in `composer.json`.
- Use `JOOservices Laravel Embedding Library` as the product name and `jooservices/laravel-embedding` only for the Composer package identifier.
- Keep OpenAI and image embedding support marked as unsupported until runtime and tests prove otherwise.
- Keep PostgreSQL `pgvector` as the only documented similarity search backend.
- Keep queue replacement docs aligned with staged inactive rows and activation after successful chunk completion.
- Cross-link related docs when behavior changes.

## Failure Playbook

- Docs conflict across files: prioritize source-of-truth code/config/tests, then normalize related docs.
- Docs too abstract: add concrete commands, config examples, or code snippets.
- Runtime support unclear: choose explicit limitation wording over optimistic phrasing.
- Frequent drift in same area: add a maintenance checklist entry in that doc.

## Definition Of Done

- Documentation matches current implementation.
- Commands/examples are valid.
- Related pages are updated consistently.
- Unsupported behavior remains visible.
