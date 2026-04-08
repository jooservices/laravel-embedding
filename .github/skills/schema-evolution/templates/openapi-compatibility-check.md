# Template: OpenAPI Compatibility Check

## Goal

Prevent accidental breaking changes in generated OpenAPI output.

## Steps

1. Capture current output for affected DTOs.
2. Apply intended generator changes.
3. Compare before/after for:
   - required fields
   - field types and formats
   - object/array structure
4. Add tests for compatibility-sensitive paths.
5. Document migration guidance for breaking changes.

## Exit Criteria

- Compatibility impact is explicitly known.
- Breaking changes are intentional and documented.
