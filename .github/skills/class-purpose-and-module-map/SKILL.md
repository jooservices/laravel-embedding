---
name: class-purpose-and-module-map
description: "Use when: orienting inside the codebase; understanding which class or module owns a behavior; deciding where to implement a change; and preventing cross-layer edits that belong elsewhere."
---

# Class Purpose And Module Map Skill

This skill helps agents understand what each major class and module is for before editing.

## Public Foundation

### `Contracts/*`

Defines package extension boundaries for chunkers, providers, manager orchestration, persistence, and search.

### `DTOs/*`

Carries typed package data such as chunks, embedding vectors, stored results, targets, and batch status.

### `Facades/*`

Provides Laravel-facing entry points for embedding generation and search.

## Runtime Modules

### `Services/Embedding/EmbeddingManager`

Primary orchestration service. Owns chunk/embed flows, persistence decisions, context overrides, ingestion helpers, queue helpers, and batch status delegation.

### `Services/Embedding/EmbeddingBatchTracker`

Owns batch lifecycle records, progress counters, failure summaries, and status DTO conversion.

### `Services/Chunking/*`

Splits text into `ChunkData`. Chunkers should not know about providers, queues, or storage.

### `Services/Providers/Ollama/*`

Owns Ollama HTTP transport, request payloads, and response normalization.

### `Services/Providers/OpenAI/*`

Reserved placeholder surface. Do not present as supported until runtime wiring and fallback tests exist.

### `Repositories/EloquentEmbeddingRepository`

Owns vector persistence, target filtering, staged batch activation, active/inactive visibility, metadata filters, and pgvector-backed search delegation.

### `Models/*`

Eloquent representations for stored embeddings and embedding batch status. Connection/table names are config-driven.

### `Jobs/*`

Own queue execution, retry/backoff/timeout properties, overlap middleware, and batch status updates. Actual embedding work should be delegated to contracts.

### `Support/PgvectorSimilarityQuery`

Owns PostgreSQL driver checks, vector validation, distance selection, and `<=>` ordering.

### `Services/Ingestion/ContentNormalizer`

Owns plain text, Markdown, HTML, and file normalization before chunking.

## Placement Heuristics

- Change `Services/Chunking/` for chunk boundary behavior
- Change provider modules for API payloads, transport, or response shape
- Change `Repositories/` for persistence, filters, replacement, and search storage behavior
- Change `Jobs/` for queue execution behavior
- Change `Support/` for isolated query/database helpers
- Change docs whenever public runtime behavior changes

## Definition Of Done

- The agent can name the owning module before editing
- Code changes land in the layer responsible for the behavior
- Public foundation contracts are edited more carefully than internal helpers
