# Template: Prepare Release Checklist

## Goal

Prepare a release candidate safely before creating a version tag.

## Steps

1. Confirm target version (`vX.Y.Z`) and scope.
2. Ensure branch is synced with latest `develop`.
3. Run local validation:

```bash
composer lint
composer test
```

4. Confirm no pending uncommitted changes.
5. Review notable changes for release notes.

## Exit Criteria

- Local gates are green.
- Version is approved.
- Branch is ready for tagging.
