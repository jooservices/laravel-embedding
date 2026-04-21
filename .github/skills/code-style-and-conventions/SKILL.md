---
name: code-style-and-conventions
description: "Use when: writing or reviewing PHP code in this repository; matching the package style beyond formatter output; choosing naming, class shape, comments, tests, and repository-consistent implementation patterns."
---

# Code Style And Conventions Skill

This skill teaches agents how code is expected to look and feel in `jooservices/laravel-embedding`.

## Style Baseline

- Use `declare(strict_types=1);`
- Keep namespaces aligned with the module path under `src/`
- Prefer `final` for concrete helpers and infrastructure classes unless extension is intentional
- Prefer readonly constructor-promoted dependencies and DTO properties where immutability is part of the contract
- Keep methods small and responsibility-focused
- Add comments only where behavior or intent would otherwise be non-obvious

## Repository Coding Conventions

- Contracts define package boundaries for chunking, providers, persistence, manager orchestration, and search
- DTOs represent transport data returned to consumers and should stay constructor-driven
- Providers own remote API payloads and response normalization
- Repositories own persistence, target filtering, batch replacement, and database-specific query behavior
- Jobs should delegate package behavior to contracts and keep queue concerns local
- Public APIs should read clearly at call sites before they read cleverly in implementation
- Avoid new abstraction layers when an existing module already has a clear home

## Test Conventions

- Use `tests/Unit/` for isolated chunkers, normalizers, DTO helpers, provider response handling, and query helpers
- Use `tests/Feature/` when behavior crosses Laravel container bindings, Eloquent persistence, facades, queues, or migrations
- Prefer regression-style tests for bugs and contract-style tests for public behavior
- Keep coverage comfortably above the 95% CI threshold when adding behavior

## Documentation Conventions

- Use the canonical product name `JOOservices Laravel Embedding Library`
- Use `jooservices/laravel-embedding` only for the Composer package identifier
- Examples should reflect real repository behavior, not aspirational behavior
- Keep OpenAI and image embedding gaps visible until runtime and tests support them

## Decision Heuristics

1. Can the existing module own this change cleanly?
2. Is the class shape consistent with nearby code?
3. Would a maintainer recognize this as repository-native code?
4. Are comments and helper methods earning their keep?
5. Are docs and tests updated with public behavior?

## Definition Of Done

- The code matches repository patterns, not just formatter output
- Naming, class shape, and tests feel native to the codebase
- No extra abstraction or commentary was added without clear value
