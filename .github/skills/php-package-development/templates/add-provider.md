# Template: Add Embedding Provider

Use this when adding a runtime-supported embedding provider.

## Checklist

1. Add or complete provider client, adapter, and response normalizer classes under `src/Services/Providers/<Provider>/`.
2. Wire the provider in `LaravelEmbeddingServiceProvider`.
3. Add config keys in `config/embedding.php`.
4. Add unit tests for request payloads, response normalization, failure responses, and batch behavior.
5. Add feature tests for service-provider binding and facade behavior.
6. Update README and docs support matrix.
7. Keep unsupported providers marked as unsupported until this checklist is complete.

## Done When

- Runtime provider selection works from config.
- Provider errors become package exceptions.
- Docs and tests describe the same support level.
