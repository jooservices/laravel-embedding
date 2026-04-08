---
name: commit-and-pr-authoring
description: "Use when: writing commit messages, PR titles, PR summaries, or release-facing change descriptions; aligning wording with Conventional Commits, semantic PR rules, and repository labels."
---

# Commit and PR Authoring Skill

## Purpose

This skill helps agents describe changes in repository-native git and PR language.

## Commit message rules

- Use Conventional Commits
- Allowed types:
  - `feat`
  - `fix`
  - `docs`
  - `style`
  - `refactor`
  - `perf`
  - `test`
  - `chore`
  - `ci`
  - `build`
  - `revert`
- Shape:
  - `type(scope): Description`

## PR title rules

- Must use the same Conventional Commit type set
- Subject must start with an uppercase letter
- Scope is optional

## PR summary expectations

- State the user-visible or maintainer-visible outcome
- Call out tests run or expected
- Mention docs updates if behavior changed
- Mention compatibility or risk if relevant

## Label awareness

Changed files may trigger labels such as:

- `source`
- `tests`
- `documentation`
- `dependencies`
- `ci/cd`
- `configuration`

## Definition of done

- Commit and PR wording matches repository automation
- The summary explains the real scope without hiding risk
