---
applyTo: "**/*"
description: Repository-wide quality, compatibility, CI, docs, and hook rules
---

- This repository is the `JOOservices DTO Library` package `jooservices/dto`.
- Match repository-native style, class shape, and naming conventions.
- Understand module ownership before editing core behavior.
- Prefer minimal, additive, compatibility-safe changes.
- Use `Pint` as the primary formatting authority.
- Keep `PHP-CS-Fixer` to its narrow PHPDoc-cleanup role.
- Expect `PHPCS`, `PHPStan`, `PHPMD`, PHPUnit, gitleaks, and `composer audit` to matter.
- CI requires at least 90% statement coverage.
- Commit messages use Conventional Commits.
- PR titles must use allowed Conventional Commit types and start with an uppercase subject.
- Do not present partially wired runtime features as fully supported.
- Update docs and examples when public behavior changes.
