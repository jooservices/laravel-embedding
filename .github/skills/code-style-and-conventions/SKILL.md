---
name: code-style-and-conventions
description: "Use when: writing or reviewing PHP code in this repository; matching the house style beyond formatter output; choosing naming, class shape, comments, tests, and repository-consistent implementation patterns."
---

# Code Style and Conventions Skill

## Purpose

This skill teaches agents how code is expected to look and feel in `jooservices/dto`, beyond what automated formatters can fix.

## Style baseline

- Use `declare(strict_types=1);`
- Keep namespaces aligned with the module path under `src/`
- Prefer constructor-promoted public properties for DTO contracts
- Prefer `readonly` where immutability is part of the DTO contract
- Use `final` for concrete helper or infrastructure classes unless extension is intentional
- Keep methods small and responsibility-focused

## Repository coding conventions

- `Dto` classes model transport-style, constructor-driven objects
- `Data` classes model mutable state objects and should be introduced deliberately
- Public APIs should read clearly at call sites before they read cleverly in implementation
- Add comments only where behavior or intent would otherwise be non-obvious
- Prefer explicit names over short or overloaded names
- Avoid introducing new abstraction layers when the existing module already has a clear home

## Test conventions

- Mirror the source structure under `tests/Unit/` when behavior is isolated
- Use `tests/Integration/` when behavior crosses mapper, hydrator, engine, normalizer, or validation boundaries
- Prefer regression-style tests for bugs and contract-style tests for public behavior
- Keep fixtures in `tests/Fixtures/` when shared across multiple tests

## Documentation conventions

- Use the canonical product name `JOOservices DTO Library`
- Use `jooservices/dto` only for the Composer package identifier
- Examples should reflect real repository behavior, not aspirational behavior

## Decision heuristics

1. Can the existing module own this change cleanly?
2. Is the class shape consistent with nearby code?
3. Would a maintainer recognize this as repository-native code?
4. Are comments and helper methods earning their keep?
5. Are tests placed where contributors will expect them?

## Definition of done

- The code matches repository patterns, not just formatter output
- Naming, class shape, and tests feel native to the codebase
- No extra abstraction or commentary was added without clear value
