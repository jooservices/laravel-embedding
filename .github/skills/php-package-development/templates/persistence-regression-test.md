# Template: Persistence Regression Test

Use this when changing repository, model, migration, or batch replacement behavior.

## Checklist

1. Identify whether the change affects active rows, staged rows, target filters, metadata filters, or search ordering.
2. Add feature tests under `tests/Feature/`.
3. Cover Eloquent targets and `EmbeddingTargetData` when target behavior changes.
4. Verify replacement flows keep old active rows searchable until staged activation completes.
5. Update docs if operators or package users need to understand new behavior.

## Done When

- Stored DTOs are returned in expected order.
- Active/inactive visibility is covered.
- Public persistence behavior is documented.
