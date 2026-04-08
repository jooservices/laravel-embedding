---
name: coverage-and-lint-guard
description: "Use when: adding or reviewing code that must satisfy style, static analysis, maintainability, and coverage requirements; triaging failures from Pint, PHPCS, PHPStan, PHPMD, PHP-CS-Fixer, or coverage checks."
---

# Coverage and Lint Guard Skill

## Repository truth

- `Pint` is the primary formatting authority
- `PHP-CS-Fixer` handles a narrow PHPDoc delta
- `PHPCS` checks structure
- `PHPStan` checks types and correctness
- `PHPMD` checks maintainability
- CI enforces 90% minimum statement coverage

## Local command order

1. `composer lint:pint`
2. `composer lint:phpcs`
3. `composer lint:phpstan`
4. `composer lint:phpmd`
5. `composer lint:cs`
6. `composer test`
7. `composer test:coverage` when coverage-sensitive behavior changes

## Coverage policy

- Add unit tests for isolated logic
- Add integration tests when behavior crosses hydrator, normalizer, mapper, or engine boundaries
- Favor regression tests for public behavior, exceptions, and edge cases
- Do not stop at green tests if coverage is likely to fall below the CI threshold

## Failure playbook

- `lint:pint` fails:
  - Run `composer lint:pint:fix` or `composer lint:fix`
- `lint:phpcs` fails:
  - Fix structural violations before touching analysis suppressions
- `lint:phpstan` fails:
  - Fix types and signatures, avoid broad ignores
- `lint:phpmd` fails:
  - Reduce complexity or split responsibilities
- `lint:cs` fails:
  - Keep PHPDoc cleanup narrow and non-overlapping with Pint
- Coverage fails:
  - Add or deepen tests until behavior and threshold are both covered

## Definition of done

- Style, structure, type analysis, and maintainability checks are green
- Tests cover the intended change and realistic edge cases
- Coverage impact has been considered explicitly
