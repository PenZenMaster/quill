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
- Starter theme (quill-theme-starter) and first client theme (acme-plumbing-theme) built, tested on Local, findings fixed.
- Theme contract extended: Forms (HTML Forms), defaults + manifest, shared saved values, menu seeding.
- HTML Forms contact form verified end to end.

## In progress
- Tests for `Quill_Admin` rendering (needs more WP stubs).

## Next
- Browser verification of Acme: layout, edit mode, Replace image with different aspect ratios.
- Feed any further findings into the starter and `docs/agent-instructions.md`.

## Workflow
Test-driven: write a failing test first, then the code. Gate: `composer qa` (phpcs + phpunit).
