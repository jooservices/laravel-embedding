---
name: dependency-and-versioning-policy
description: "Use when: considering new dependencies, changing Composer constraints, reviewing release impact, or deciding whether a change should be treated as patch, minor, or breaking from a package-maintainer perspective."
---

# Dependency and Versioning Policy Skill

## Purpose

This skill helps agents stay conservative about dependencies and package versioning.

## Dependency policy

- The package keeps runtime dependencies small and Laravel-package appropriate
- Prefer existing Laravel components and package-local code before adding runtime dependencies
- New dev dependencies should support the existing contributor workflow and CI
- Dependency changes should trigger security, CI, and documentation review

## Versioning heuristics

- Patch-style change:
  - bug fix
  - docs correction
  - test-only improvement
  - internal maintenance without public contract change
- Minor-style change:
  - additive user-visible capability
  - new supported provider, chunker, search helper, or ingestion helper
  - new extension point without breaking old behavior
- Major/breaking-style change:
  - changed public behavior
  - removed capability
  - changed persisted schema, queue semantics, or search behavior in incompatible ways

## Review checklist

1. Is a new runtime dependency truly necessary?
2. Does the change alter the public contract or only implementation detail?
3. Would downstream consumers need migration guidance?
4. Do release notes or docs need version-impact wording?

## Definition of done

- Dependency changes are justified and minimal
- Versioning impact has been considered explicitly
