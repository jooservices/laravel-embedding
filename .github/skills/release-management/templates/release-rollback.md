# Template: Release Rollback

## Goal

Mitigate a bad release with minimal user impact.

## Steps

1. Identify issue severity and affected version.
2. If needed, prepare quick patch branch.
3. Apply minimal fix and validate:

```bash
composer lint
composer test
```

4. Tag patched version (`vX.Y.Z+1` semantics through next patch tag).
5. Communicate incident and resolution notes.

## Exit Criteria

- Patched release is live.
- Root cause and corrective action documented.
