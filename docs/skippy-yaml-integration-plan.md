# Skippy YAML Builder → Quill Integration Plan

Status: **Phase 0 complete; Phases 1 onward proposed** · Owner: Rank Rocket Co · Date: 2026-10-17  
Scope: build-time generation of custom WordPress themes using the existing Quill content-editing contract.

## Decision and objectives

**Use a versioned, validated Skippy Business Profile YAML as the build-time input.** An AI coding agent generates a conventional WordPress theme, WordPress page setup, media import manifest, and Quill-compatible editable content. Quill continues to own saved client edits. YAML is **not** read by Quill on ordinary page requests, and Quill does not become a form plugin, CRM, or website generator.

This is a proposed architecture, not an implemented integration. **Before coding, locate and inspect the actual Skippy YAML Builder implementation and its current sample YAMLs**; the exact schema, dependencies, and existing behavior are not verified here. Preserve backward compatibility using an explicit migrator if necessary.

Success: given a sanitized sample profile, a developer can deterministically validate it, produce a reproducible theme build manifest, generate a Quill-compatible theme, deploy to staging, and verify SEO, accessibility, editing, and safe reruns without erasing client changes.

## Phase 0 outcome (2026-10-17)

Discovery is recorded in [discovery/skippy-yaml-discovery.md](discovery/skippy-yaml-discovery.md). Decisions that refine this plan:

- Skippy's existing YAML is a flat, label-keyed file for YACSS link-building with no schema version. It is **not extended**. A new versioned `site_profile` schema is added, with a one-way `from-skippy` adapter.
- A client can have several files with conflicting identity data (phone, website, name). The adapter merges per client, requires a human pick on conflict and records it in `provenance`.
- Validator, normalizer, manifest and field registry live in a **separate Python package (`quill-site-builder`)**, not in the Quill plugin.
- The Acme Plumbing and starter themes are the first `local-service` family: the starter plus the agent contract become the Phase 3 generator template, their guard tests become its acceptance gates, and their activation setup (pages, seeded-once menu, seed images, form creation) is generalized into the Phase 4 provisioner.
- Structured data (LocalBusiness/Service JSON-LD) is owned by the RankRocket SEO Control Layer; themes do not emit it.
- Each client gets one human-maintained BASE YAML (new `site_profile` format). Other Skippy files stay YACSS-only.
- Pilot: Salvo Metal Works via `general.yaml`.
- Phase 1 first slice shipped in `PenZenMaster/quill-site-builder` (schema v1.0, safe loader, validator, `validate` CLI, 26 tests).
- Large legacy lists (up to 428 cities, 253 services) need a curated "featured" subset for navigation and an index for city pages.

## Boundaries and responsibilities

| System | Owns | Does not own |
|---|---|---|
| CRM or intake (optional) | Leads, account and onboarding data | Published website content |
| Skippy YAML Builder | Capture, normalization, provenance, validation, schema version, approved snapshot | Live editing or WordPress database writes |
| Theme generator (Claude/Codex or build tool) | Templates, semantic HTML, CSS/JS, asset manifest, initial Quill defaults, WordPress provisioning recipe | Saved client edits |
| WordPress | Pages/URLs, menus, media IDs, posts, taxonomies, settings | Theme layout specification |
| Quill | Stable editable field IDs and saved text/rich text/button/image values, permissions, audit history | Layout, lead submission, CRM sync |
| Forms plugin | Forms, spam controls, submission notifications/storage | Static theme copy |

### Golden rules

1. Generated themes must use **`quill_text()`, `quill_richtext()`, `quill_button()`, `quill_image()` and managed-region helpers** via safe `function_exists()` wrappers. Follow [Quill's agent contract](agent-instructions.md).
2. WordPress Page records exist for every public page; `front-page.php` is used for the assigned static front page; URL helpers are used, never hardcoded hostnames.
3. Client-saved Quill values take precedence over the generated default. **Never auto-import YAML over existing Quill content.**
4. All editable field IDs are immutable once shipped, with explicit scopes on reused templates. Changing field IDs/scopes requires a reviewed migration.
5. Sensitive fields (keys, passwords, CRM tokens, customer PII) must not be placed in generated code, public assets, tracked fixtures, or AI prompts.
6. One authoritative owner per data field; changes from CRM/YAML to a deployed website require a preview, impact report and human approval.
7. Contact forms remain a separately installed form plugin or integration; theme owns visual styles, not submission handling.
8. Business identity and LocalBusiness structured data use approved facts; do not fabricate reviews, certifications, locations, staff, or claims.

## Data contracts

### Profile input

Define a machine-readable `schema_version` with at least these groups (only after comparing existing Builder input):
- `business`: legal/display name, phone (E.164 + display), contact email, website URL, physical address, public/private address flag, service-area business flag, hours, contact channels
- `locations[]`: stable IDs, NAP, coordinates if verified, GBP URLs/IDs when appropriate; choose a primary location
- `services[]`: immutable keys, display names, slugs, descriptions, associated locations
- `service_areas[]`: city, region, country and service coverage (do not imply physical presence)
- `branding`: logo/media references, palette, type tokens, imagery rights/alt text
- `site`: pages, slug, template family, nav strategy, contact CTA, locale and timezone
- `seo`: approved titles/descriptions, canonical rules, schema entity type, social profiles, indexability flags
- `provenance`: source system, last verified time, reviewer, field confidence/status
- `integrations`: **references only**, not credentials (e.g., form provider and ID added during provisioning)

Distinguish **required, optional, null, and unknown**. Block build on missing required identity data, invalid URL/email/phone, duplicate stable IDs/slugs, conflicting NAP, or unapproved claims; warn on absent optional values. Never output empty schema fields as invented defaults.

### Sanitized illustrative example (not the existing Builder schema)

```yaml
schema_version: "1.0"
business:
  id: "example-fabricator"
  display_name: "Example Fabrication"
  phone_e164: "+15550102000"
  contact_email: "hello@example.com"
  website: "https://example.com"
  service_area_business: false
locations:
  - id: "main"
    primary: true
    address:
      street: "100 Example Street"
      city: "Exampleville"
      region: "IL"
      postal_code: "60000"
      country: "US"
services:
  - id: "metal-fabrication"
    name: "Custom Metal Fabrication"
    slug: "metal-fabrication"
site:
  locale: "en_US"
  theme_family: "local-service"
  navigation: "wordpress"
  pages:
    - id: "home"
      slug: "home"
      template: "front-page"
    - id: "contact"
      slug: "contact"
      template: "contact"
branding:
  logo: "assets/logo.webp"
integrations:
  contact_form:
    provider: "html-forms"
    form_id: null
```

### Intermediate artifact / build manifest

Convert approved input to a normalized deterministic manifest (JSON or YAML): profile digest, generator/contract versions, pages, stable IDs, fields and defaults, media inventory, routes, menu entries, schema data, dependencies, and unresolved questions. Use a stable sorting/serialization strategy, fixtures, JSON Schema (or equivalent), a CLI `validate` command, and actionable diagnostics with YAML field paths. Prefer a separate builder package/repository later; start with small integration adapters/tests inside Quill only when needed, **not** a large YAML parsing runtime inside the Quill plugin.

## Pipeline

```text
CRM / questionnaire / legacy YAML (optional input)
             |
        Skippy YAML Builder
             |
    normalize → validate → human approve
             |
       immutable profile snapshot
             |
    manifest + field-ID registry + media map
             |
      Claude/Codex theme generation
             |
  static checks, security checks, test fixtures
             |
  staging WordPress provisioning & media import
             |
     theme activation + Quill plugin
             |
     QA + manual launch approval
```

Provision through WP-CLI or a controlled authenticated interface: import Media Library files, create/reuse pages by stable provisioner IDs, assign templates, configure permalink and static front page, assign menus, configure forms, and install/enable Quill. Make retries **idempotent**. Plan for dry-run, staging backup, rollback, and content ownership. Do not write raw DB rows as a shortcut.

## Stable field ID design

Maintain a generated registry: `page_id_or_template_scope`, `field_id`, `type`, `owner`, `default_source_path`. Use semantic identifiers such as `home.hero.heading` and `contact.intro.body`; for shared page templates explicitly pass `'scope' => 'page'`. Reserve existing IDs when regenerating a theme. Produce a diff showing new, removed, retyped, or re-scoped fields; block destructive changes by default.

Store **approved initial copy as PHP helper defaults**, not bulk writes to Quill's persistence. If client edits already exist, preserve them, even after site-wide redesign. For approved content updates after launch, offer a separate reviewed change path rather than quietly changing defaults and promising the change will appear.

## SEO / GEO / AEO quality gates

- Confirm crawlable semantic markup, correct heading hierarchy, alt text, mobile performance, accessibility, sitemap, canonical and robots policy.
- Use one coherent source of business identity for on-page NAP, contact page, footer and JSON-LD; location pages distinguish service areas from physical offices.
- Generate schema only for verified entities; align with the site's existing SEO plugin/schema ownership to avoid duplicate `LocalBusiness`/Service graphs.
- Include internal-link map for services, locations, blog and conversion pages, and a generated SEO inspection report. Do not promise rankings or AI citations.
- GA4/GSC configuration is deployment integration; avoid injecting secrets in themes.

## Security, privacy, and reliability

Use WordPress escaping/sanitization, capability/nonce checks in any new endpoints, and permission tests. Restrict file paths and MIME types, refuse untrusted YAML tags/constructors, validate external URLs, protect SSRF-sensitive fetches, and avoid arbitrary code execution through data values or prompts. Treat the YAML as untrusted input. No CRM keys in GitHub. Use least-privilege credentials on staging. Keep audit trail of source, reviewer, versions and build digest.

## Delivery stages and gates

| Stage | Deliverable | Exit gate |
|---|---|---|
| 0. Discovery | Identify actual Builder repo/schema, inventory samples; Quill v0.9.2 contract audit | Sample profiles and gaps documented |
| 1. Contract | Versioned schema, validator, fixtures, migration strategy | Positive/negative fixture tests pass |
| 2. Manifest | Normalizer, manifest and field-ID registry, diff command | Deterministic outputs, IDs stable |
| 3. Theme generator | Agent instructions and 1 local-service theme family | Generated theme follows Quill contract; deactivation fallback works |
| 4. Provisioning | Dry-run + idempotent staging setup, media/forms adapter | Repeated deployment does not duplicate pages or overwrite edits |
| 5. QA | Automated and manual release gate, evidence/report | No critical security/accessibility/SEO failures |
| 6. Pilot | One *staging* client-like site with a real approved business profile | Client editing, backups, rollback and launch checklist accepted |
| 7. Optional CRM | Votel/n8n mapping adapter and approval flow | CRM outage does not affect live pages |

## Explicit non-goals for v1

No direct Votel integration, no automatic overwrites of saved content, no AI-generated live site updates without human review, no new contact-form engine inside Quill, and no Elementor migration automation. Existing Elementor sites require a separate migration/runbook and backups.

## Acceptance test suite

1. Valid and invalid YAML fixtures including missing NAP, multi-location, service-area-only, duplicates, suspicious paths and unsafe HTML.
2. Same input produces the same normalized manifest/field registry.
3. One custom theme passes PHP lint/WPCS and responsive/mobile testing.
4. Plugin temporarily disabled: public theme still renders without fatal errors.
5. Client edits survive regenerated theme, redeployment and plugin reactivation.
6. Provisioning rerun does not duplicate pages/media/menu links or alter existing edits.
7. Generated schema and on-page NAP match approved business facts.
8. Form submits on staging with spam protection and verified delivery without Quill processing submissions.
9. Changes to slugs, field types, media, templates and scope trigger a reviewable diff.
10. Rollback to last known good theme leaves saved Quill content intact.

## Implementation issue index

GitHub issues created alongside this document carry the execution details. Complete stages 0–2 before generating production client themes; treat the first production deployment as a separate approval.

## Source of truth

- [Quill repository README](../README.md)
- [Quill theme/agent contract](agent-instructions.md)
- [Upstream Pencilino](https://github.com/rinothecoder/pencilino)

