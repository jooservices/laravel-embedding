---
name: ci-hooks-maintenance
description: "Use when: updating CI workflows, captainhook rules, lint/test gate order, or commit policy; validating hook behavior; and preventing local-vs-CI drift."
---

# CI and Hooks Maintenance Skill

## Quick Start

1. Identify whether change is local hook (`captainhook.json`) or remote CI (`.github/workflows/*.yml`).
2. Apply minimal config change.
3. Validate affected commands locally.
4. Ensure hook/CI ordering still matches project policy.

## Repository Truth

- Local hooks are configured in `captainhook.json`.
- CI gates are configured in `.github/workflows/ci.yml`.
- Release flow is configured in `.github/workflows/release.yml`.
- PR title validation lives in `.github/workflows/semantic-pr.yml`.
- Pre-commit runs syntax, secret scan, Pint, PHPCS, and PHPStan.
- Pre-push runs secret scan and tests.

## Change Safety Rules

- Never remove quality gates without explicit justification.
- Keep command names aligned with scripts in `composer.json`.
- Prefer additive changes over broad rewrites.
- Document behavior changes in development docs.

## Core Workflow

1. Confirm intended behavior and gate ordering.
2. Update one file at a time (`captainhook.json` or a workflow file).
3. Run impacted commands locally (`composer lint`, `composer test`, targeted hook command).
4. Verify no mismatch between local hooks, CI matrix, semantic PR policy, and release flow.
5. Commit with `ci:` or `chore(ci):` conventional message.

## Failure Playbook

- Hook blocks all commits unexpectedly:
  - Check missing local dependencies (for example `gitleaks`).
  - Add clear fallback or onboarding docs if required.
- CI command not found:
  - Ensure command exists in `composer.json` scripts.
- Coverage gate fails in CI but not locally:
  - Re-run `composer test:coverage` and inspect the 90% threshold behavior.
- Gate order drift:
  - Restore order according to policy (security, style, structure, static analysis, tests, release).

## Definition Of Done

- Hook and CI changes are minimal and reviewed.
- Local commands pass.
- CI configuration remains policy-aligned.
- Docs are synced when behavior changes.
