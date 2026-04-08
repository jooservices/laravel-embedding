# Copilot Instructions For `jooservices/dto`

Read [AGENTS.md](/Users/vietvu/Sites/JOOservices/dto/AGENTS.md) as the primary repository policy.

When generating or editing code:

- prefer the existing package architecture over new abstractions
- match repository-native style and naming, not just formatter output
- understand which class or module owns the behavior before editing
- keep tests and docs in the same change when public behavior moves
- respect current runtime limitations from `docs/11-risks-legacy-and-gaps.md`
- assume local hooks and CI will enforce linting, coverage, security, and commit hygiene

Use prompt files in `.github/prompts/` for focused tasks.
