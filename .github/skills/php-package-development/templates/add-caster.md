# Template: Add Caster

## Goal

Add a new cast strategy for converting raw input into DTO property target types.

## Preconditions

- Confirm target type and source shapes to support.
- Confirm precedence requirements versus `#[CastWith]` attribute caster.

## Steps

1. Add caster in `src/Casting/Casters/` implementing `CasterInterface`.
2. Register caster in casting registry/bootstrap wiring.
3. Respect nullable/permissive behavior used by hydration context.
4. Ensure compatibility checks do not override existing attribute-level caster behavior.
5. Add unit tests for:
   - Supported type conversion
   - Invalid value handling
   - Null/permissive edge cases

## Edge Case Matrix

- `null` input with nullable target type
- Incompatible scalar/object input type
- Already-correct type input (should pass through)
- Permissive mode behavior differences
- Nested DTO/typed array interactions if applicable

## Test Placement

- Unit: `tests/Unit/Casting/Casters/<CasterName>Test.php`
- Registry: `tests/Unit/Casting/CasterRegistryTest.php` (if registry matching changes)
- Integration: `tests/Integration/DtoIntegrationTest.php` (if end-to-end flow changes)

## Local Checks

```bash
composer lint
composer test
```

## Common Failure Handling

- Registry does not pick caster: verify `canCast` conditions and registration wiring.
- Unexpected cast order: ensure attribute-level caster still has priority.
- PHPStan type mismatch: tighten return type and internal branch narrowing.

## Done Checklist

- Caster selected only for intended target types
- Exceptions are explicit for unsupported values
- Existing cast priority order remains intact
- Edge cases are covered with deterministic assertions
