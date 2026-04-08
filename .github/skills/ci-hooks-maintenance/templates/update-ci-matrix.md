# Template: Update CI Matrix

## Goal

Update CI tool matrix without breaking build policy.

## Steps

1. Edit `.github/workflows/ci.yml` matrix entries minimally.
2. Keep ordering aligned with project quality policy.
3. Ensure each matrix command maps to a valid composer script.
4. Validate locally with equivalent commands.
5. Note impact in PR description.

## Exit Criteria

- CI workflow syntax is valid.
- All listed tools are executable in the project.
- Pipeline behavior is intentional and documented.
