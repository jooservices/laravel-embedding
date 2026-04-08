---
name: repo-quality-foundation
description: "Use when: starting any non-trivial task in this repository; aligning an AI agent with coding standards, coverage, hooks, CI, release policy, documentation rules, and current runtime limitations."
---

# Repository Quality Foundation Skill

This is the baseline skill for `jooservices/dto`.

## Repository truth

- Runtime target: PHP 8.5+
- Package: `jooservices/dto`
- Product name: `JOOservices DTO Library`
- Architecture: DTO hydration, validation, normalization, schema generation, and collection wrappers

## Quality gates

- Format with `Pint`
- Keep `PHP-CS-Fixer` limited to its narrow PHPDoc cleanup role
- Run `PHPCS`, `PHPStan`, and `PHPMD` for structural, type, and maintainability checks
- Run `PHPUnit` tests
- Keep CI coverage at or above 95%

## Command map

- `composer lint`
- `composer lint:all`
- `composer lint:fix`
- `composer test`
- `composer test:coverage`

## Hooks and PR policy

- Conventional Commits are enforced by CaptainHook on `commit-msg`
- `pre-commit` runs PHP linting, `gitleaks protect --staged`, `composer lint:pint`, `composer lint:phpcs`, and `composer lint:phpstan`
- `pre-push` runs `gitleaks detect` and `composer test`
- PR titles must use the configured Conventional Commit types and start with an uppercase subject

## Runtime guardrails

Do not claim full runtime support for:

- `Computed`
- `DefaultFrom`
- `Deprecated`
- `DiscriminatorMap`
- `OptionalProperty`
- `Pipeline`
- `StrictType`

Also remember:

- Typed DTO arrays are not inferred automatically from PHPDoc
- `CastWith` and `TransformWith` options payloads are not fully consumed
- `Data::update()` and `Data::set()` bypass casting and validation
- Nested schema generation is intentionally shallow

## Always-follow workflow

1. Identify the touched module and public behavior impact.
2. Implement the smallest repository-consistent change.
3. Add or update tests.
4. Update docs when public behavior or workflow truth changes.
5. Run the relevant quality gates.
6. Finalize with Conventional Commit and PR-safe wording.
