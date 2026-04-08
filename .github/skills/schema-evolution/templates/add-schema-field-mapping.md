# Template: Add Schema Field Mapping

## Goal

Add or update field mapping rules in JSON Schema/OpenAPI generation.

## Steps

1. Define desired field contract and type mapping.
2. Update generator logic in `src/Schema/`.
3. Add unit tests under `tests/Unit/Schema/`.
4. Validate nested DTO and array-object scenarios.
5. Update docs/examples for consumer visibility.

## Exit Criteria

- New field mapping is covered by tests.
- Contract change is documented.
