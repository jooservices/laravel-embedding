---
name: security-hardening
description: "Use when: hardening code and pipeline security; handling gitleaks findings; running dependency audits; applying secure coding checks to new code; and triaging security failures in hooks or CI."
---

# Security Hardening Skill

## Quick Start

1. Identify security surface: code, secrets, dependencies, or pipeline.
2. Run baseline checks (`composer audit`, gitleaks).
3. Fix findings with minimal-risk changes.
4. Re-run checks to verify closure.

## Repository Truth

- Secrets are scanned in local hooks; the dedicated secret-scanning workflow file exists but its `gitleaks` job is currently commented out.
- Dependency audit is part of CI security job.
- Pre-commit and pre-push can block on security checks.
- OpenSSF Scorecard runs on `master`, on a weekly schedule, and on manual dispatch.

## Security Baseline

- No plaintext credentials in repository.
- All dependency advisories resolved or explicitly tracked.
- New code paths reviewed for input validation and safe defaults.
- Security guidance should match the actual enabled workflows, not aspirational checks.

## Core Workflow

1. Reproduce finding (local or CI).
2. Classify severity and scope.
3. Apply fix:
   - Remove secrets and rotate credentials.
   - Patch or constrain vulnerable dependencies.
   - Tighten validation and error handling.
4. Re-scan and confirm no new findings.
5. Document security-impacting changes.

## Failure Playbook

- gitleaks false positive:
  - Verify pattern and adjust allowlist cautiously.
- composer audit fails:
  - Update dependency and retest full lint/test gates.
- Scorecard or SARIF issue appears:
  - Confirm whether the finding is repository-policy or workflow-configuration related before changing code.
- Security fix causes regressions:
  - Add targeted regression tests and isolate patch.

## Definition Of Done

- Findings are remediated or explicitly accepted with rationale.
- Security checks pass locally and in CI.
- Impact is tested and documented.
