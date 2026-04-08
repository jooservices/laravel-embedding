---
name: change-type-taxonomy
description: "Use when: classifying a change as feature, fix, refactor, docs, test, ci, build, security, or release work; deciding the expected tests/docs/risk level; and choosing commit or PR wording."
---

# Change Type Taxonomy Skill

## Purpose

This skill gives agents a shared language for classifying work before they implement or describe it.

## Change types

### `feat`

- Adds new user-visible capability
- Usually needs tests, docs, and compatibility review

### `fix`

- Corrects wrong runtime behavior
- Must prefer regression tests and clear risk review

### `refactor`

- Changes structure without intentionally changing behavior
- Requires stronger review for hidden compatibility drift

### `docs`

- Changes documentation only
- Must still match runtime truth and workflow truth

### `test`

- Adds or improves tests without changing package behavior

### `ci`

- Changes workflows, hooks, or automation policy

### `build`

- Changes package build or dependency-management behavior

### `chore`

- Repository maintenance with low direct product impact

### `perf`

- Improves performance or complexity characteristics
- Must not quietly change external behavior

### `revert`

- Reverses an earlier change

## Classification heuristics

- If public behavior changes, default away from `refactor`
- If docs are updated because behavior changed, do not classify the whole change as `docs`
- If hooks or workflows change, prefer `ci` or `chore(ci)`
- If dependency constraints change, also apply dependency/versioning review

## Definition of done

- The change type matches what actually changed
- Tests, docs, and commit wording follow the chosen change type
