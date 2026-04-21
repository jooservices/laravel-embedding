---
name: php-package-development
description: "Use when: developing or maintaining this Laravel embedding PHP package; adding chunkers, providers, search/persistence behavior, queue flows, ingestion helpers, or fixing package bugs with tests and docs."
---

# PHP Package Development Skill

This skill standardizes how contributors work in this repository.

## Quick Start

1. Pick the change type: chunking, provider, persistence, search, queueing, ingestion, docs, or bugfix.
2. Implement the minimal change in the correct `src/` module.
3. Add unit tests for isolated logic.
4. Add feature tests when behavior crosses Laravel container, Eloquent, facades, migrations, or queue boundaries.
5. Run `composer lint` and `composer test`.
6. If style fails, run `composer lint:fix`, then rerun lint and tests.
7. Finalize with a Conventional Commit message.

## Scope

Use this workflow for:

- Feature development inside `src/`
- Bug fixes with regression tests
- Provider, chunker, repository, search, ingestion, or queue behavior
- PR hardening before merge

Do not use this skill for:

- Non-PHP infrastructure work unrelated to package behavior
- Broad refactors without tests and behavior constraints
- Release management outside repository CI/release workflows

## Repository Truth

- Runtime requirement: PHP 8.5+ (`composer.json`)
- Core commands:
  - `composer lint`
  - `composer lint:all`
  - `composer lint:fix`
  - `composer test`
  - `composer test:coverage`
- CI enforces at least 95% statement coverage
- Supported runtime provider today: Ollama
- Supported similarity search backend today: PostgreSQL with `pgvector`

## Module Map

- `src/Contracts/`: extension boundaries
- `src/DTOs/`: package data objects
- `src/Services/Chunking/`: text splitting strategies
- `src/Services/Providers/`: provider clients, adapters, response normalizers
- `src/Services/Embedding/`: orchestration, ingestion, queues, batch tracking
- `src/Repositories/`: persistence and search storage behavior
- `src/Models/`: config-driven Eloquent models
- `src/Jobs/`: background processing and batch lifecycle updates
- `src/Support/`: focused query/database helpers
- `src/Traits/`: host model integration helpers

## Test Decision Matrix

- Add unit tests when logic is isolated to one class, helper, DTO, chunker, provider normalizer, or query helper.
- Add feature tests when behavior crosses service provider bindings, facades, Eloquent persistence, queue jobs, migrations, or Laravel model traits.
- Add docs examples when changing public APIs or operational behavior.

## Always-Follow Workflow

1. Identify module and public behavior impact.
2. Implement smallest possible change with strict typing and module boundaries.
3. Add or update tests in the right layer.
4. Run quality gates locally:
   - `composer lint`
   - `composer test`
5. If style issues exist, run `composer lint:fix`, then rerun lint and tests.
6. Check whether docs, examples, or release notes should change.
7. Keep unsupported runtime surfaces documented honestly.

## Failure Playbook

- `composer lint:pint` fails: run `composer lint:pint:fix` or `composer lint:fix`, then rerun.
- `composer lint:phpcs` fails: fix structural issues or use the approved fixer path.
- `composer lint:phpstan` fails: fix type/signature issues before adding suppressions.
- Coverage threshold fails: add or strengthen tests until coverage is back at or above 95%.
- Provider docs drift: compare docs against service provider bindings and tests.
- Queue docs drift: compare docs against `ProcessEmbeddingBatchJob`, `ProcessChunkJob`, and `EmbeddingBatchTracker`.

## Definition Of Done

- Correct module placement under `src/`
- Tests added/updated and passing
- Lint and static analysis passing
- Coverage impact reviewed
- Docs updated when behavior/API changes
- Unsupported features remain clearly marked as unsupported
