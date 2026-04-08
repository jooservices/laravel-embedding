---
name: architecture-and-design-principles
description: "Use when: deciding how to change this package; choosing between minimal extension and refactor; reviewing public API impact; and keeping runtime, docs, tests, and contributor expectations aligned."
---

# Architecture and Design Principles Skill

## Purpose

This skill tells agents how to think when making changes in `jooservices/dto`.

## Core principles

- Prefer minimal, local changes over broad rewrites
- Preserve public behavior unless the change explicitly updates the contract
- Treat docs and tests as part of the implementation
- Prefer additive evolution over breaking behavior
- Keep runtime truth ahead of documentation convenience
- Respect the package boundary: this is a library, not an application framework

## Package-specific principles

- `Core/` is the public foundation surface; change it carefully
- `Engine/`, `Hydration/`, `Normalization/`, and `Meta/` form a pipeline and should stay loosely coupled by role
- Attributes are declarative metadata, not a promise that runtime wiring already exists
- Validation timing and casting order are behavioral contracts
- Schema output should be described honestly, especially where it is intentionally shallow

## Agent guardrails

- Do not invent support for partially wired attributes
- Do not “fix” architectural boundaries by moving logic into new layers unless the existing structure is clearly failing
- Do not hide compatibility risks inside style or refactor commits
- Do not update docs to imply support that tests and runtime do not back up

## Change review questions

1. Is this a public API change or an internal implementation change?
2. Which module actually owns this behavior?
3. Is the change additive, behavioral, or breaking?
4. What tests prove the intended contract?
5. What docs need to move with the code?
6. What assumptions about current limitations must remain visible?

## Definition of done

- The change follows repository architecture rather than fighting it
- Public behavior impact is intentional and tested
- Documentation and examples tell the same story as the runtime
