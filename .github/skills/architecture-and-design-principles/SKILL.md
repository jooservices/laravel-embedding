---
name: architecture-and-design-principles
description: "Use when: deciding how to change this package; choosing between minimal extension and refactor; reviewing public API impact; and keeping runtime, docs, tests, and contributor expectations aligned."
---

# Architecture And Design Principles Skill

This skill tells agents how to think when making changes in `jooservices/laravel-embedding`.

## Core Principles

- Prefer minimal, local changes over broad rewrites
- Preserve public behavior unless the change explicitly updates the contract
- Treat docs and tests as part of the implementation
- Prefer additive evolution over breaking behavior
- Keep runtime truth ahead of documentation convenience
- Respect the package boundary: this is a Laravel package, not a full RAG application framework

## Package-Specific Principles

- `EmbeddingManager` orchestrates chunking, provider calls, persistence, ingestion, queueing, and batch status through focused collaborators
- Chunkers own text splitting only; they should not call providers or databases
- Provider adapters own API payload shape and response normalization
- Repository classes own Eloquent persistence, pgvector search, target filters, and staged replacement
- Queue jobs own dispatch/runtime queue behavior and should delegate embedding work to contracts
- Search helpers must stay honest about PostgreSQL-only vector search
- Config can reserve future provider keys, but docs must not present reserved providers as supported runtime behavior

## Runtime Truth Guards

- OpenAI support is reserved until the provider is wired into runtime behavior and covered by tests
- Image embeddings are deferred until officially supported by Ollama `/api/embed`
- SQLite/MySQL can persist vectors but do not support similarity search through this package
- `$model->queueEmbedding()` expects `getEmbeddableContent()`
- Vectors use `pgvector` `<=>` cosine-distance ordering

## Change Review Questions

1. Is this a public API change or an internal implementation change?
2. Which module actually owns this behavior?
3. Is the change additive, behavioral, or breaking?
4. What tests prove the intended contract?
5. What docs need to move with the code?
6. What runtime limitations must remain visible?

## Definition Of Done

- The change follows repository architecture rather than fighting it
- Public behavior impact is intentional and tested
- Documentation and examples tell the same story as the runtime
