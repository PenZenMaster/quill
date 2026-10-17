# Changelog

## Unreleased

- BREAKING: full rename Pencil -> Quill. Plugin file `quill.php`, text domain `quill`,
  classes `Quill_*`, constants `QUILL_*`, helpers `quill_text()`, `quill_richtext()`,
  `quill_button()`, `quill_image()`, `quill_managed_region_open/close()`, filter
  `quill_can_edit`, REST namespace `quill/v1`, options `quill_*`, DB table `{prefix}quill_activity`,
  CSS classes `quill-*`, data attributes `data-quill-*`, JS global `quillEditor`.
  Migration: none required (no installs or themes existed on the old names).
- Replaced the upstream About, Release notes and Support content with a fork attribution
  and a link to the Quill issue tracker; removed upstream author photo and signature.
- Added `.gitattributes` (LF normalisation).
- Added characterization tests for `Quill_Activity`, `Quill_Plugin` and template helpers (60 tests total).
- Imported Pencil by Rino v0.9.2 as the Quill baseline (unmodified).
- Added phpcs (WPCS), phpunit, pre-commit lint hook, and CI.
- Added characterization tests for `Pencil_Fields`.
- Lint-only fixes: class docblocks, `@package` tag, alignment. No behavior change.
