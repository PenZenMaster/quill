# Quill

Frontend content editing for Claude-generated WordPress themes. The theme owns the
layout; the client edits text, rich text, buttons, and images directly on the site.
Quill replaces Elementor-style page builders on Rank Rocket projects.

Quill is a fork of [Pencil by Rino](https://github.com/rinothecoder/pencilino) v0.9.2
(GPL-2.0-or-later). See `NOTICE.md`.

## Requirements
- WordPress 6.4+, PHP 7.4+

## Development
Run in CMD:

    composer install
    composer qa

- `composer lint` / `composer lint-fix`: WordPress Coding Standards (phpcs/phpcbf)
- `composer test`: phpunit unit tests (`tests/unit`, WordPress stubbed in `tests/bootstrap.php`)
- A pre-commit hook runs phpcs (installed by `composer install`).

New code is test-driven: write a failing test, make it pass, refactor, keep `composer qa` green.

## Theme contract
See `docs/agent-instructions.md` for the helper functions themes use.
