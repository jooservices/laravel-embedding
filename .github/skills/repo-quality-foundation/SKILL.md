---
name: repo-quality-foundation
description: "Use when: starting any non-trivial task in this repository; aligning an AI agent with coding standards, coverage, hooks, CI, release policy, documentation rules, and current runtime limitations."
---

# Repository Quality Foundation Skill

This is the baseline skill for `jooservices/laravel-embedding`.

## Repository Truth

- Runtime target: PHP 8.5+
- Package: `jooservices/laravel-embedding`
- Product name: `JOOservices Laravel Embedding Library`
- Architecture: text normalization, chunking, embedding providers, vector persistence, PostgreSQL `pgvector` search, queue jobs, and batch tracking

## Quality Gates

- Format with `Pint`
- Keep `PHP-CS-Fixer` limited to narrow PHPDoc cleanup
- Run `PHPCS`, `PHPStan`, and `PHPMD` for structural, type, and maintainability checks
- Run `PHPUnit` tests
- Keep CI statement coverage at or above 95%

## Command Map

- `composer lint`
- `composer lint:all`
- `composer lint:fix`
- `composer test`
- `composer test:coverage`

Never invent alternate command names such as `composer fix`.

## Hooks And PR Policy

- Conventional Commits are enforced by CaptainHook on `commit-msg`
- `pre-commit` runs PHP linting, `gitleaks protect --staged`, `composer lint:pint`, `composer lint:phpcs`, and `composer lint:phpstan`
- `pre-push` runs `gitleaks detect` and `composer test`
- PR titles must use the configured Conventional Commit types and start with an uppercase subject

## Runtime Guardrails

Do not claim full runtime support for:

- OpenAI embeddings, until the provider is wired into runtime behavior and tests
- Image embeddings, until Ollama `/api/embed` officially supports them and the package adds tests
- Vector search on non-PostgreSQL drivers

Also remember:

- `$model->queueEmbedding()` requires `getEmbeddableContent()`
- Vectors use PostgreSQL `pgvector` cosine-distance operator `<=>`
- SQLite/MySQL persistence is storage-only; similarity search is PostgreSQL-only
- Public behavior changes require docs and tests in the same change

## Always-Follow Workflow

1. Identify the touched module and public behavior impact.
2. Implement the smallest repository-consistent change.
3. Add or update unit tests and feature tests where behavior crosses package boundaries.
4. Update docs when public behavior or workflow truth changes.
5. Run the relevant quality gates.
6. Finalize with Conventional Commit and PR-safe wording.
