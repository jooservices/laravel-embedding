# JOOservices Laravel Embedding Repository Instructions

This repository is a PHP 8.5+ Laravel package named `jooservices/laravel-embedding`.

## Core intent

- Preserve the existing package architecture before introducing abstractions.
- Favor minimal changes that fit current modules under `src/`.
- Treat docs and tests as part of the implementation, not follow-up work.

## Repository quality rules

- Formatting authority: `Pint`
- Narrow PHPDoc cleanup: `PHP-CS-Fixer`
- Structural checks: `PHPCS`
- Static analysis: `PHPStan`
- Maintainability checks: `PHPMD`
- Tests: `PHPUnit`

## Required command map

- `composer lint`
- `composer lint:all`
- `composer lint:fix`
- `composer test`
- `composer test:coverage`

Never invent alternative commands such as `composer fix`.

## Agent-first guidance

Before making non-trivial changes, also read:

- `.github/skills/code-style-and-conventions/SKILL.md`
- `.github/skills/architecture-and-design-principles/SKILL.md`
- `.github/skills/class-purpose-and-module-map/SKILL.md`
- `.github/skills/task-routing-and-intent-map/SKILL.md`
- `.github/skills/change-type-taxonomy/SKILL.md`
- `.github/skills/review-and-risk-assessment/SKILL.md`
- `.github/skills/commit-and-pr-authoring/SKILL.md`
- `.github/skills/dependency-and-versioning-policy/SKILL.md`
- `.github/skills/partially-wired-feature-triage/SKILL.md`

These files explain how code should look, how decisions should be made, and which classes/modules own which behaviors.

## Coverage and CI

- CI enforces a 90% minimum statement coverage threshold.
- CI runs `composer audit`, a lint matrix, coverage tests, and optional dependency review on pull requests.
- Release is tag-driven through `vX.Y.Z` tags.

## Hooks and Git hygiene

- CaptainHook validates Conventional Commits on `commit-msg`.
- `pre-commit` runs PHP linting, `gitleaks protect --staged`, `composer lint:pint`, `composer lint:phpcs`, and `composer lint:phpstan`.
- `pre-push` runs `gitleaks detect` and `composer test`.
- PR titles must use the configured Conventional Commit types and start with an uppercase subject.

## Runtime truth guards

Do not present these as fully supported runtime features unless you are explicitly wiring them into the runtime and tests:

- Image embeddings (Deferred until officially supported by Ollama `/api/embed`)
- OpenAI support (Interface exists but no actual fallback tests written)

Also keep in mind:

- `$model->queueEmbedding()` expects `getEmbeddableContent()` to exist.
- Vectors use `pgvector` operators `<=>`.

## Documentation policy

- Use the canonical product name `JOOservices Laravel Embedding Library`.
- Use `jooservices/laravel-embedding` only for the Composer package identifier.
- Do not document behavior that is only declared in code but not wired into runtime behavior.
- When public behavior changes, update docs and examples in the same change.

## Change checklist

Before considering a task done:

1. Keep the change minimal and module-appropriate.
2. Add unit tests and integration tests where flow crosses boundaries.
3. Run the relevant lint and test commands.
4. Re-check docs, examples, CI assumptions, and release impact.
5. Use Conventional Commits for commits and PR titles.
