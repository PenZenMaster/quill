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
- Acme browser QA passed (visual and edit modes).
- Skippy YAML integration (issues #1-#8): Phases 0-3 done. Discovery in `docs/discovery/`; tooling in PenZenMaster/quill-site-builder (schema, validator, from-skippy adapter, manifest, diff gate, copy check, theme check, emit-site-data/fields); profiles in PenZenMaster/quill-client-profiles; first generated theme PenZenMaster/rank-rocket-theme (tested on Local, form stored and emailed).
- Phase 4 media import built: `plan-media` in quill-site-builder, `media.yaml` in quill-client-profiles, idempotent importer in rank-rocket-theme (unit tested; not yet run on Local). Rank Rocket palette applied to the theme.

## In progress
- Tests for `Quill_Admin` rendering (needs more WP stubs).

## Next
- Run `bin/import-media.php` on Local (WP-CLI shell), then owner browser review of the Rank Rocket theme (layout, mobile, colours, images, edit mode).
- Move source images out of the Local uploads folder; add a branding block to the profile so palette and hero come from the manifest.
- Review and approve the Salvo Metal Works profile (auto-drafted, no provenance).
- Phase 4 remainder (idempotent provisioner CLI covering pages, menu, forms and media), Phase 5 QA gates, Phase 6 pilot, Phase 7 optional (see `docs/skippy-yaml-integration-plan.md`).
- Owner closes issues #1-#4 after review.
- Feed any further findings into the starter and `docs/agent-instructions.md`.

## Workflow
Test-driven: write a failing test first, then the code. Gate: `composer qa` (phpcs + phpunit).
