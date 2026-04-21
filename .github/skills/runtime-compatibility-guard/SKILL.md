---
name: runtime-compatibility-guard
description: "Use when: changing runtime behavior, public APIs, provider support, persistence/search behavior, queue flows, or guarding against unsupported-feature drift."
---

# Runtime Compatibility Guard Skill

## Focus

Use this skill whenever a change can affect public behavior or developer expectations.

## Guardrails

- Preserve existing public behavior unless the change intentionally revises it
- Prefer additive changes over silent behavioral rewrites
- Add regression tests for bug fixes
- Update docs/examples when behavior changes
- Keep provider and database support claims aligned with runtime code and tests

## Current Limitations That Must Stay Explicit

- OpenAI config is reserved, but OpenAI runtime embedding is not supported yet
- Image embeddings are deferred until officially supported by Ollama `/api/embed`
- Similarity search is PostgreSQL `pgvector` only
- SQLite/MySQL persistence is storage-only through this package
- `$model->queueEmbedding()` requires `getEmbeddableContent()`

## Review Checklist

1. Does the change alter chunking, embedding, persistence, search, ingestion, or queue behavior?
2. Does it affect public facade or contract expectations?
3. Does it change provider support or documented database support?
4. Does it require feature tests rather than unit tests only?
5. Does it require docs, examples, or release notes?
6. Does it keep unsupported runtime surfaces clearly marked?

## Definition Of Done

- Public behavior impact is understood and tested
- Unsupported features are not accidentally marketed as supported
- Compatibility notes are reflected in docs when needed
