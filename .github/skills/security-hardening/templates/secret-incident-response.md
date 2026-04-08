# Template: Secret Incident Response

## Goal

Handle detected credentials or secret leaks quickly and safely.

## Steps

1. Identify exposed secret and affected scope.
2. Remove secret from code/history as required.
3. Rotate/revoke compromised credential immediately.
4. Replace usage with environment-based secret management.
5. Re-run secret scan and quality gates.

## Exit Criteria

- Exposed secret is invalidated.
- Repository no longer contains active secret material.
- Follow-up prevention notes are documented.
