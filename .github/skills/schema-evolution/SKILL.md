---
name: schema-evolution
description: "Use when: updating JSON Schema/OpenAPI generators, evolving DTO contract output, preserving backward compatibility, and validating schema changes with tests and examples."
---

# Schema Evolution Skill

## Quick Start

1. Define contract change and compatibility level.
2. Update schema generator logic in `src/Schema/`.
3. Add tests for changed schema output.
4. Document contract impact in examples/docs.

## Scope

Use this skill for:
- JSON Schema generator updates
- OpenAPI generator updates
- DTO contract output changes affecting external consumers

Do not use this skill for:
- Internal refactors without schema output impact
- Non-contract style-only changes

## Compatibility Policy

- Prefer additive changes for minor releases.
- Avoid breaking contract keys without explicit migration guidance.
- Keep required fields and types stable unless versioned change is planned.
- Do not overstate schema depth: nested DTOs and arrays are intentionally shallow today unless the generator logic is explicitly expanded.

## Core Workflow

1. Capture before/after schema output.
2. Implement minimal changes in generator classes.
3. Add unit tests under `tests/Unit/Schema/`.
4. Add integration/example validation when output is consumed end-to-end.
5. Update docs examples for new/changed fields.

## Failure Playbook

- Snapshot/output mismatch:
  - Verify whether behavior changed intentionally; update tests and docs together.
- Backward compatibility break:
  - Add compatibility layer or document migration path.
- Incomplete nested type representation:
  - Add dedicated tests for nested DTO and array object shapes.

## Definition Of Done

- Schema/OpenAPI output is validated by tests.
- Compatibility impact is explicit.
- Consumer-facing docs/examples are updated.
