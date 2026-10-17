# Project Status

Goal: replace Elementor with Claude-generated custom themes (one per project) that
are editable through the Pencil frontend editor. The theme owns layout; the client
edits content only.

## Completed
- Pencil v0.9.2 imported unmodified (commit 1).
- Tooling: composer, phpcs (WPCS), phpunit, pre-commit hook, GitHub Actions CI.
- Characterization tests for `Pencil_Fields` (17 tests).
- Private repo PenZenMaster/quill created and pushed.

## In progress
- Characterization tests for `Pencil_Activity`, `Pencil_Plugin`, template functions.

## Next
- Decide and execute rename `pencil_*` / `Pencil_*` -> `quill_*` / `Quill_*` (breaks themes using the `pencil_*` helpers; consider keeping deprecated aliases).
- Document the theme-generation contract for Claude (based on `docs/agent-instructions.md`).

## Workflow
Test-driven: write a failing test first, then the code. Gate: `composer qa` (phpcs + phpunit).
