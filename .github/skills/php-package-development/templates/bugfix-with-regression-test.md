# Template: Bugfix With Regression Test

## Goal

Fix a defect and prevent reintroduction with minimal, targeted tests.

## Preconditions

- Bug is reproducible with clear input and expected output.
- Root cause area is identified to avoid broad, risky changes.

## Steps

1. Reproduce with a failing test first.
2. Keep bugfix minimal and local to the owning module.
3. Add/adjust test coverage around root cause and nearest edge case.
4. Re-run full lint + tests.
5. Update docs if behavior contract changes.

## Impact Review

- Which module owns the bug?
- Does the bug affect chunking, provider calls, persistence, search, queues, or ingestion?
- Could the fix affect backward compatibility of public package behavior?
- Is additional feature coverage needed for Laravel container, Eloquent, facade, or queue flow?

## Test Placement Heuristic

- Pure logic bug: mirrored unit test under `tests/Unit/`
- Multi-component Laravel flow bug: feature test under `tests/Feature/`

## Local Checks

```bash
composer lint
composer test
```

Optional full validation:

```bash
composer lint:all
composer test:coverage
```

## Common Failure Handling

- Regression test still flaky: simplify setup and remove non-deterministic data.
- New unrelated failures: isolate minimal fix, avoid opportunistic refactors.
- Hook failures: address lint/static/test errors before re-running commit/push.

## PR Checklist

- Regression test fails before fix and passes after fix
- No unrelated refactors mixed in
- Commit title follows Conventional Commits
- Security and dependency checks remain green
- Risk and behavior changes are documented in PR description
