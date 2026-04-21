# Copilot Instructions For `jooservices/laravel-embedding`

Read [AGENTS.md](/Users/vietvu/Sites/JOOservices/laravel-embedding/AGENTS.md) as the primary repository policy.

When generating or editing code:

- prefer the existing package architecture over new abstractions
- match repository-native style and naming, not just formatter output
- understand which class or module owns the behavior before editing
- keep tests and docs in the same change when public behavior moves
- respect current runtime limitations from `AGENTS.md`, `README.md`, and `docs/`
- assume local hooks and CI will enforce linting, coverage, security, and commit hygiene

Use prompt files in `.github/prompts/` for focused tasks.
