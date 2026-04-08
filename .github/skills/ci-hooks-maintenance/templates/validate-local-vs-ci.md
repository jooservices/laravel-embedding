# Template: Validate Local vs CI

## Goal

Ensure local hooks and CI gates stay aligned.

## Steps

1. List local gates from `captainhook.json`.
2. List CI gates from `.github/workflows/ci.yml`.
3. Compare command parity and ordering.
4. Resolve drift by updating config or docs.
5. Run local baseline:

```bash
composer lint
composer test
```

## Exit Criteria

- Local and CI checks are consistent.
- Any intentional differences are documented.
