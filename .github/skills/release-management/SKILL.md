---
name: release-management
description: "Use when: preparing and executing package releases; validating tag-based release flow; creating GitHub releases; publishing updates to Packagist; and ensuring release quality gates are green."
---

# Release Management Skill

## Quick Start

1. Confirm branch state is clean and up to date.
2. Run local quality gates (`composer lint:all`, `composer test`).
3. Confirm release version and changelog scope.
4. Create and push semantic version tag (`vX.Y.Z`).
5. Verify release workflow and Packagist publish steps.

## Repository Truth

- Release flow is tag-driven (`v*.*.*`) in `.github/workflows/release.yml`.
- Validation job runs tests before creating release artifacts.
- Publish job triggers Packagist update for stable tags.
- Commit messages are enforced by CaptainHook and PR titles are enforced by the semantic PR workflow.

## Preconditions

- You have push permission for tags.
- CI status for current branch is green.
- Version has been agreed and documented.

## Core Workflow

1. Prepare:
   - Sync with latest `develop`.
   - Ensure no pending uncommitted changes.
2. Validate locally:
   - `composer lint:all`
   - `composer test`
   - Confirm docs and examples are in sync with the release content
3. Tag and push:
   - `git tag vX.Y.Z`
   - `git push origin vX.Y.Z`
4. Monitor release workflow completion.
5. Verify GitHub release notes and Packagist update.

## Failure Playbook

- Release validation test fails:
  - Fix issue on branch, retag with next patch version if needed.
- Release passes locally but fails in CI:
  - Compare local checks with `validate` job behavior and workflow permissions.
- Tag format invalid:
  - Use strict `vX.Y.Z` naming.
- Packagist publish fails:
  - Check repository secrets and rerun release job.

## Definition Of Done

- Release tag exists and matches intended version.
- Release workflow completed successfully.
- GitHub release is available and correct.
- Packagist reflects the new version.
