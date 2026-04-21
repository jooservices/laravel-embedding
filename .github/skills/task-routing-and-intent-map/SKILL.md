---
name: task-routing-and-intent-map
description: "Use when: deciding which repository skill or workflow to apply first; classifying user intent; and routing bugfix, feature, docs, CI, security, review, and release work to the right skill set."
---

# Task Routing And Intent Map Skill

## Purpose

This skill helps agents choose the right workflow before they start editing.

## Routing Map

- New package feature in `src/`:
  - `repo-quality-foundation`
  - `code-style-and-conventions`
  - `architecture-and-design-principles`
  - `class-purpose-and-module-map`
  - `php-package-development`
  - `runtime-compatibility-guard`
- Bug fix or regression:
  - `php-package-development`
  - `review-and-risk-assessment`
  - `coverage-and-lint-guard`
- Docs drift or contributor guidance change:
  - `documentation-sync`
  - `architecture-and-design-principles`
- Provider support change:
  - `php-package-development`
  - `runtime-compatibility-guard`
  - `documentation-sync`
  - `dependency-and-versioning-policy`
- Persistence/search behavior:
  - `class-purpose-and-module-map`
  - `php-package-development`
  - `review-and-risk-assessment`
- CI, hooks, workflow, release automation:
  - `ci-hooks-maintenance`
  - `security-hardening`
  - `commit-and-pr-authoring`
- Security or dependency issue:
  - `security-hardening`
  - `dependency-and-versioning-policy`
- Code review request:
  - `review-and-risk-assessment`
- Release prep:
  - `release-management`
  - `review-and-risk-assessment`
  - `commit-and-pr-authoring`

## Fast Classification Questions

1. Is the user asking for implementation, review, documentation, automation, or release work?
2. Does the change touch public behavior?
3. Does the change touch provider support, vector search, queues, persistence, or docs?
4. Does the change touch workflows, secrets, or dependencies?

## Definition Of Done

- The agent can explain why it chose the starting skill set.
- The first workflow matches the actual task instead of forcing all tasks through one generic path.
