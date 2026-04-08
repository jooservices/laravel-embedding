# AI Skills Map

This repository keeps one quality policy and exposes it through several AI tool formats.

See also:

- [AI Skills Usage Guide](./USAGE.md)

## Canonical repository skills

- `.github/skills/repo-quality-foundation/SKILL.md`
- `.github/skills/code-style-and-conventions/SKILL.md`
- `.github/skills/architecture-and-design-principles/SKILL.md`
- `.github/skills/class-purpose-and-module-map/SKILL.md`
- `.github/skills/task-routing-and-intent-map/SKILL.md`
- `.github/skills/change-type-taxonomy/SKILL.md`
- `.github/skills/review-and-risk-assessment/SKILL.md`
- `.github/skills/commit-and-pr-authoring/SKILL.md`
- `.github/skills/dependency-and-versioning-policy/SKILL.md`
- `.github/skills/partially-wired-feature-triage/SKILL.md`
- `.github/skills/php-package-development/SKILL.md`
- `.github/skills/coverage-and-lint-guard/SKILL.md`
- `.github/skills/runtime-compatibility-guard/SKILL.md`
- `.github/skills/documentation-sync/SKILL.md`
- `.github/skills/ci-hooks-maintenance/SKILL.md`
- `.github/skills/security-hardening/SKILL.md`
- `.github/skills/schema-evolution/SKILL.md`
- `.github/skills/release-management/SKILL.md`

## Adapter layers

- `AGENTS.md`: shared always-on repository policy
- `.cursor/rules/`: Cursor project rules
- `CLAUDE.md` and `.claude/commands/`: Claude Code guidance and slash commands
- `.github/copilot-instructions.md`, `.github/instructions/`, `.github/prompts/`: VS Code Copilot instructions and prompt files
- `jetbrains/prompts/`: prompt-library-ready markdown templates
- `antigravity/prompts/`: portable prompts for environments without a stable checked-in skill format

## Intent

All adapters should reflect the same repository truth:

- code style and conventions beyond formatter output
- design principles and change heuristics for agents
- class and module ownership
- task routing and change classification
- review, risk, and PR authoring
- dependency, versioning, and partially wired feature policy
- style and static analysis gates
- 90% coverage threshold
- git hook and PR hygiene
- CI and release policy
- security checks
- documentation sync
- runtime compatibility and supported-feature boundaries
