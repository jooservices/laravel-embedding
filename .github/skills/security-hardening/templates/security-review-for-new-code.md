# Template: Security Review for New Code

## Goal

Perform lightweight but effective security checks for newly introduced code.

## Checklist

- Input validation is explicit.
- Error messages do not leak sensitive data.
- Unsafe dynamic execution paths are avoided.
- Secrets are never hardcoded.
- New dependencies are justified and audited.

## Commands

```bash
composer audit
composer lint
composer test
```

## Exit Criteria

- No unresolved critical security findings.
- New code follows secure defaults and project policy.
