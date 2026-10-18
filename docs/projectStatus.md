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
- Phase 4 media import built: `plan-media` in quill-site-builder, `media.yaml` in quill-client-profiles, idempotent importer in rank-rocket-theme (unit tested; run on Local 2026-10-18: hero and all 8 service pages serve the imported images). Rank Rocket palette applied to the theme.
- Issues #1-#4 (Phases 0-3) closed by the owner on 2026-10-18; #5-#8 remain open.
- Client profile editor UI (`quill-site-builder ui`, builder issue #4): create/edit/validate/delete base.yaml in a local browser form; 200 builder tests. Roadmap issues filed (builder #4-#7, quill #9-#10, profiles #1, theme #1); decisions: design requests are build-time only, pilot client is Salvo Metal Works.

## In progress
- Tests for `Quill_Admin` rendering (needs more WP stubs).

## Next
- Owner browser review of the Rank Rocket theme (layout, mobile, colours, images, edit mode).
- Move source images out of the Local uploads folder; add a branding block to the profile so palette and hero come from the manifest.
- Review and approve the Salvo Metal Works profile (auto-drafted, no provenance); use the new profile editor (quill-client-profiles#1).
- Phase 4 remainder (idempotent provisioner CLI covering pages, menu, forms and media), Phase 5 QA gates, Phase 6 pilot, Phase 7 optional (see `docs/skippy-yaml-integration-plan.md`).
- Feed any further findings into the starter and `docs/agent-instructions.md`.

## Workflow
Test-driven: write a failing test first, then the code. Gate: `composer qa` (phpcs + phpunit).
