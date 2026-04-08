# Template: Schema Regression Test

## Goal

Protect schema output from unintended regressions.

## Steps

1. Add focused regression tests for affected generator behavior.
2. Cover positive and edge-case DTO structures.
3. Keep assertions specific to contract-critical fields.
4. Re-run lint and tests.

## Commands

```bash
composer lint
composer test
```

## Exit Criteria

- Regression test fails before fix and passes after fix.
- Schema output remains stable for unchanged contracts.
