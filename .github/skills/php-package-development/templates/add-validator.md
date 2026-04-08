# Template: Add Validator

## Goal

Introduce a new validation rule with predictable behavior and full test coverage.

## Preconditions

- Define exact rule semantics and error message contract.
- Confirm whether rule is property-only or context-aware (depends on all data).

## Steps

1. Add validator class in `src/Validation/Validators/` implementing the repository validator contract.
2. Register validator in the validation registry/factory used by Engine.
3. Add attribute support in `src/Attributes/Validation/` if this rule is attribute-driven.
4. Add focused unit tests:
   - Happy path
   - Boundary conditions
   - Invalid input cases and message assertions
5. Add integration test if rule depends on context or cross-field data.

## Validation Behavior Checklist

- Missing value behavior is explicit
- `null` behavior is explicit
- Type coercion assumptions are explicit
- Error message content and field path are stable

## Test Placement

- Unit: `tests/Unit/Validation/Validators/<RuleName>ValidatorTest.php`
- Attribute (if any): `tests/Unit/Attributes/Validation/<RuleName>Test.php`
- Integration (if needed): `tests/Integration/Validation/<RuleName>IntegrationTest.php`

## Local Checks

```bash
composer lint
composer test
```

## Common Failure Handling

- Rule not triggered: verify attribute metadata is parsed and rule is registered.
- False positives: tighten validator conditions and add counter-examples in tests.
- Context-related failures: add integration tests covering full payload and dependent fields.

## Done Checklist

- Rule enforces expected constraints
- Error messages are deterministic and actionable
- Rule works with hydration flow and context settings
- Integration coverage exists for cross-field/context-dependent logic
