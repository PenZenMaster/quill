# Project Status

Goal: replace Elementor with Claude-generated custom themes (one per project) that
are editable through the Pencil frontend editor. The theme owns layout; the client
edits content only.

## Completed
- Pencil v0.9.2 imported unmodified (commit 1).
- Tooling: composer, phpcs (WPCS), phpunit, pre-commit hook, GitHub Actions CI.
- Characterization tests for Fields, Activity, Plugin, template helpers (60 tests).
- Full rename Pencil -> Quill (identifiers, files, text domain, REST namespace, CSS/JS).
- `.gitattributes` for LF normalisation.
- Private repo PenZenMaster/quill created and pushed.

## In progress
- Tests for `Quill_Admin` rendering (needs more WP stubs).

## Next
- Verify first GitHub Actions run; manual smoke test in a real WordPress install (activate, edit a field, view History).
- Document the theme-generation contract for Claude (based on `docs/agent-instructions.md`).

## Workflow
Test-driven: write a failing test first, then the code. Gate: `composer qa` (phpcs + phpunit).
