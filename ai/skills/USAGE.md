# AI Skills Usage Guide

This guide explains how to use the repository skill pack across supported agent environments.

## Start here

For any non-trivial task, agents should treat these files as the shared baseline:

- `AGENTS.md`
- `CLAUDE.md`
- `ai/skills/README.md`

The canonical repository skills live under `.github/skills/`.

## What this skill pack is for

This pack is designed to help agents:

- understand repository-native code style
- follow architecture and design principles
- choose the right module before editing
- classify the change correctly
- implement safely with tests and docs
- review risk before shipping
- respect CI, hooks, release, dependency, and versioning policy
- avoid hallucinating support for partially wired features

## Recommended workflow for agents

1. Read `AGENTS.md`.
2. Route the task with `.github/skills/task-routing-and-intent-map/SKILL.md`.
3. Classify the change with `.github/skills/change-type-taxonomy/SKILL.md`.
4. Load the task-specific skills from `.github/skills/`.
5. Implement or review the change.
6. Re-check docs, tests, risk, and release impact before finishing.

## Common task recipes

### Add or change package code

Use:

- `repo-quality-foundation`
- `code-style-and-conventions`
- `architecture-and-design-principles`
- `class-purpose-and-module-map`
- `php-package-development`
- `runtime-compatibility-guard`
- `coverage-and-lint-guard`

### Fix a bug or regression

Use:

- `task-routing-and-intent-map`
- `change-type-taxonomy`
- `php-package-development`
- `review-and-risk-assessment`
- `coverage-and-lint-guard`

### Review a change

Use:

- `review-and-risk-assessment`
- `runtime-compatibility-guard`
- `coverage-and-lint-guard`

### Update docs

Use:

- `documentation-sync`
- `architecture-and-design-principles`
- `partially-wired-feature-triage`

### Triage CI, hooks, or release issues

Use:

- `ci-hooks-maintenance`
- `security-hardening`
- `review-and-risk-assessment`
- `commit-and-pr-authoring`

### Work on schema behavior

Use:

- `schema-evolution`
- `class-purpose-and-module-map`
- `runtime-compatibility-guard`
- `partially-wired-feature-triage`

## Tool-specific usage

### Cursor

- Always-on baseline: `.cursor/rules/00-repo-quality-foundation.mdc`
- Additional rules are available for coverage/lint, runtime compatibility, and CI/release work

Recommended pattern:

1. Let the baseline rule stay always-on.
2. Open the matching rule or mention the relevant repository skill in your prompt.
3. Ask for implementation, review, or triage with explicit scope.

### Claude Code

- Always-on repo guidance: `CLAUDE.md`
- Task-specific commands: `.claude/commands/*.md`

Recommended commands:

- `package-change`
- `quality-check`
- `docs-sync`
- `ci-triage`
- `security-review`
- `schema-review`
- `release-readiness`

### VS Code with Copilot

- Baseline instructions: `.github/copilot-instructions.md`
- Extra instructions: `.github/instructions/repo-quality.instructions.md`
- Prompt files: `.github/prompts/*.prompt.md`

Recommended pattern:

1. Keep the instructions files in the repo.
2. Start from the closest prompt file.
3. Pass a narrow scope in `${input}`.

### JetBrains AI Assistant

- Use `jetbrains/prompts/*.md` as prompt-library-ready templates

Recommended pattern:

1. Import or paste the prompt into JetBrains Prompt Library.
2. Start with the closest task template.
3. Keep `AGENTS.md` open for repository context.

### Antigravity

- Use `antigravity/prompts/*.md` as portable prompts

Recommended pattern:

1. Start with `antigravity/prompts/README.md`.
2. Use the nearest prompt template for the task.
3. Keep prompts aligned with the canonical `.github/skills/` files.

## Prompting tips for teammates

- Say what kind of task it is:
  - feature
  - bugfix
  - review
  - docs
  - CI
  - release
- Say which surface is affected:
  - hydration
  - validation
  - normalization
  - schema
  - docs
  - workflows
- If behavior is changing, ask the agent to include:
  - tests
  - docs impact
  - compatibility notes
  - risk summary

## When to be extra careful

- Editing `Core/`
- Changing validation timing or casting behavior
- Changing normalization output
- Changing schema output
- Adding dependencies
- Touching hooks, CI, release, or security workflows
- Touching declared-but-not-fully-wired attributes

## Maintenance rule

When repository behavior changes, update the canonical `.github/skills/` files first, then keep adapter layers in sync:

- `.cursor/rules/`
- `.claude/commands/`
- `.github/copilot-instructions.md`
- `.github/instructions/`
- `.github/prompts/`
- `jetbrains/prompts/`
- `antigravity/prompts/`
