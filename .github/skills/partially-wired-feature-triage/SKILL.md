---
name: partially-wired-feature-triage
description: "Use when: an agent is asked to use or document a declared-but-not-fully-wired feature; deciding whether to implement runtime support, document a limitation, or suggest a workaround; and preventing feature hallucination."
---

# Partially Wired Feature Triage Skill

## Purpose

This skill gives agents a safe response pattern for features that exist in code but are not fully supported in runtime behavior.

## Known partially wired features

- `Computed`
- `DefaultFrom`
- `Deprecated`
- `DiscriminatorMap`
- `OptionalProperty`
- `Pipeline`
- `StrictType`

## Triage options

### Option 1: Document the limitation

Use when the task is docs, review, or support guidance and no runtime implementation is requested.

### Option 2: Offer a supported workaround

Prefer:

- `transformInput()`
- `afterHydration()`
- custom casters
- custom transformers
- surrounding application services

### Option 3: Implement the runtime wiring explicitly

Only do this when the task actually asks for new runtime support and you can also add tests and docs.

## Response rules for agents

- State clearly that the feature is declared but not fully integrated today
- Do not imply existing support because an attribute class exists
- If implementing support, update runtime code, tests, and docs together

## Definition of done

- The agent responds truthfully about support status
- Users get either a safe workaround or a real implementation path
