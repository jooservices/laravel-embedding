# Template: Add Transformer

## Goal

Add output transformation behavior for normalization/serialization.

## Preconditions

- Define output contract (shape, type, formatting) before implementation.
- Confirm whether transformation is global (registry) or property-specific (`#[TransformWith]`).

## Steps

1. Add transformer class implementing the repository transformer contract.
2. Register transformer in normalization transformer registry.
3. Keep priority aligned with current behavior:
   - Attribute-specific transformer first
   - Registry transformer second
4. Add unit tests for scalar/object/array cases as needed.
5. Add lazy-property interaction tests if transformer affects nested output.

## Output Contract Checks

- Deterministic output for same input
- Explicit handling for `null`
- Stable output for nested arrays/DTOs
- No unexpected mutation of input objects

## Test Placement

- Unit: `tests/Unit/Normalization/Transformers/<TransformerName>Test.php`
- Registry behavior: `tests/Unit/Normalization/TransformerRegistryTest.php`
- Normalization behavior: `tests/Unit/Normalization/NormalizerTest.php`

## Local Checks

```bash
composer lint
composer test
```

## Common Failure Handling

- Transformer not applied: verify registry registration and `canTransform` logic.
- Wrong priority: confirm attribute transformer remains first, registry second.
- Depth/lazy regressions: add or update `Normalizer` and lazy property tests.

## Done Checklist

- Output shape remains stable and deterministic
- Transformer does not break depth/serialization options behavior
- Nested DTO and array behavior covered
- Coverage includes normal and edge-case output scenarios
