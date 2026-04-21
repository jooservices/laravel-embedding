# Template: Add Chunking Strategy

Use this when adding or changing a text chunking strategy.

## Checklist

1. Add the chunker under `src/Services/Chunking/`.
2. Update `ChunkingStrategy` and service-provider binding.
3. Preserve chunk index, offsets, content hash, size, and overlap expectations.
4. Add unit tests for short text, multi-chunk text, multibyte text, invalid size, and invalid overlap.
5. Update `config/embedding.php` comments and docs.

## Done When

- The strategy can be selected by config.
- Boundary behavior is tested.
- Docs list the new strategy honestly.
