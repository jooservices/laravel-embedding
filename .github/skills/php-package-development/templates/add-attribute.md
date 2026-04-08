# Template: Add Attribute

## Goal

Add a new PHP attribute used by DTO metadata and runtime flow.

## Preconditions

- Attribute behavior is clearly defined (hydration, normalization, validation, or metadata only).
- Target location is confirmed: `src/Attributes/` or `src/Attributes/Validation/`.

## Steps

1. Add attribute class in `src/Attributes/` or `src/Attributes/Validation/`.
2. Update metadata parsing so the attribute is reflected in property/class metadata.
3. Wire runtime behavior where needed:
   - Hydration path
   - Normalization path
   - Validation path
4. Add unit tests for:
   - Reflection parsing into meta model
   - Runtime effect when applied to DTO properties
   - No-op behavior when absent

## Integration Test Decision

Add integration tests if:
- Attribute influences end-to-end `Dto::from()` behavior
- Attribute changes `toArray()` or serialization options behavior
- Attribute depends on context values or cross-field interaction

## Test Placement

- Unit attribute tests: `tests/Unit/Attributes/<AttributeName>Test.php`
- Meta tests: `tests/Unit/Meta/MetaFactoryTest.php` or dedicated meta tests
- Runtime tests: hydration/normalization unit tests depending on behavior

## Local Checks

```bash
composer lint
composer test
```

## Common Failure Handling

- Pint/PHPCS failures: run `composer lint:fix`, then re-run `composer lint`.
- PHPStan failures: fix types/signatures, avoid adding suppressions unless justified.
- Hook failure on gitleaks: remove secret and retry commit.

## Done Checklist

- Attribute target(s) and constructor args are strict and documented
- Metadata and runtime behavior are both covered by tests
- No unintended impact on existing attributes
- Integration tests added when behavior crosses module boundaries
