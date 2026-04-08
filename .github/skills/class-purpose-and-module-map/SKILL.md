---
name: class-purpose-and-module-map
description: "Use when: orienting inside the codebase; understanding which class or module owns a behavior; deciding where to implement a change; and preventing cross-layer edits that belong elsewhere."
---

# Class Purpose and Module Map Skill

## Purpose

This skill helps agents understand what each major class and module is for before they edit anything.

## Public foundation classes

### `Core/Dto`

- Main consumer-facing base class
- Best for constructor-driven transport objects
- Entry point for `from*()`, `toArray()`, `toJson()`, collection helpers, and immutable-style helpers such as `with()`

### `Core/Data`

- Mutable counterpart to `Dto`
- Uses the same hydration and normalization surface
- Adds in-place mutation helpers and should be used deliberately because updates bypass recasting and revalidation

### `Core/Context`

- Immutable options bag for hydration and normalization
- Carries naming strategy, validation toggle, serialization options, cast mode, and custom data
- Some fields are public API placeholders with only partial runtime effect today

### `Core/SerializationOptions`

- Controls output filtering, wrapping, depth, and lazy-property inclusion during normalization

## Runtime pipeline classes

### `Engine/Engine`

- Orchestrates end-to-end hydration and normalization
- Invokes lifecycle hooks such as `transformInput`, `afterHydration`, and `beforeSerialization`
- Delegates actual mapping, metadata, hydration, and normalization to focused collaborators

### `Hydration/Mapper`

- Resolves source keys into DTO property names
- Applies `MapFrom` and naming-strategy rules
- Owns key lookup behavior, not casting or validation

### `Hydration/Hydrator`

- Turns mapped input into constructor arguments and instantiates objects
- Owns validation timing, casting flow, nested DTO hydration, and constructor argument resolution

### `Meta/MetaFactory`

- Converts reflection data and supported attributes into `ClassMeta` and `PropertyMeta`
- This is where agents should wire metadata extraction, not inside unrelated runtime classes

### `Normalization/Normalizer`

- Converts objects back into arrays
- Applies output filtering, transformer logic, nested DTO normalization, and lazy-property handling

## Support classes and modules

### `Validation/ValidatorRegistry`

- Registry and dispatcher for validator instances
- Owns validation lookup and aggregation of violations

### `Casting/*`

- Encapsulates value conversion concerns
- Add or change casting behavior here before reaching for hydrator changes

### `Schema/*`

- Generates shallow JSON Schema and OpenAPI structures
- Intended as lightweight contract export, not a full recursive schema system today

### `Collections/*`

- Wraps DTO lists and paginator-like structures for transport-oriented output

### `Exceptions/*`

- Defines structured error types and path-aware error composition

## Placement heuristics

- Change `Core/` when the public developer experience changes
- Change `Meta/` when attribute or reflection extraction changes
- Change `Hydration/` when input mapping, validation timing, or casting flow changes
- Change `Normalization/` when output shape or transformer behavior changes
- Change `Validation/` when rules or validator dispatch change
- Change `Schema/` when exported schema shape changes

## Definition of done

- The agent can name the owning module before editing
- Code changes land in the layer responsible for the behavior
- Public foundation classes are edited more carefully than internal helpers
