# Template: Database Index Change

Use this when adding, removing, or documenting an index.

## Checklist

1. Identify the query path that needs the index.
2. Decide whether the index belongs in package migrations or host-application docs.
3. Keep PostgreSQL-only indexes guarded or documented as PostgreSQL-only.
4. Add feature coverage for package-owned schema changes.
5. Update performance docs when operators need to make a choice.

## Done When

- Migration behavior remains portable where required.
- PostgreSQL-specific behavior is explicit.
- Docs explain why and when to add the index.
