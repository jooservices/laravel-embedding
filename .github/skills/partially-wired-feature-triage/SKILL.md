---
name: partially-wired-feature-triage
description: "Use when: an agent is asked to use or document a declared-but-not-fully-wired package feature; deciding whether to implement runtime support, document a limitation, or suggest a workaround; and preventing feature hallucination."
---

# Partially Wired Feature Triage Skill

## Purpose

This skill gives agents a safe response pattern for features that exist in config or placeholder classes but are not fully supported in runtime behavior.

## Known Partially Wired Or Reserved Features

- OpenAI provider classes and config are reserved, but the service provider throws for `openai`
- Image embeddings are deferred until officially supported by Ollama `/api/embed`
- Non-PostgreSQL drivers may persist vectors but do not provide similarity search through this package

## Triage Options

### Option 1: Document The Limitation

Use when the task is docs, review, or support guidance and no runtime implementation is requested.

### Option 2: Offer A Supported Workaround

Prefer:

- Ollama text embeddings for current runtime embedding generation
- PostgreSQL with `pgvector` for similarity search
- Host-application extraction/normalization before calling package ingestion helpers
- Application-level provider integration when the package does not support that provider yet

### Option 3: Implement Runtime Wiring Explicitly

Only do this when the task actually asks for new runtime support and you can also add tests and docs.

## Response Rules For Agents

- State clearly that the feature is reserved or unsupported today
- Do not imply existing support because config or placeholder classes exist
- If implementing support, update runtime code, tests, and docs together

## Definition Of Done

- The agent responds truthfully about support status
- Users get either a safe workaround or a real implementation path
