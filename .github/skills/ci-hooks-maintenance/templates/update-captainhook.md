# Template: Update CaptainHook

## Goal

Modify local Git hook behavior safely in `captainhook.json`.

## Steps

1. Identify exact hook section (`commit-msg`, `pre-commit`, `pre-push`).
2. Apply minimal change to one action at a time.
3. Ensure command exists in `composer.json` or system PATH.
4. Validate by running impacted command(s) locally.
5. Document any developer setup requirement changes.

## Exit Criteria

- Hook config remains valid JSON.
- Intended gate behavior is preserved or explicitly updated.
