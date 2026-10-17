# Phase 0 Discovery: Skippy YAML Builder and Quill

Date: 2026-10-17 · Issue: PenZenMaster/quill#1 · Plan: [../skippy-yaml-integration-plan.md](../skippy-yaml-integration-plan.md)

## Sources inspected

- Skippy YAML Builder: private repo `PenZenMaster/skippy_yaml_builder`, local `E:\projects\skippy_yaml_builder`, v1.3.0 (Python desktop app, tkinter-style form, 266 tests).
- 46 real client YAML files (13 clients) under its gitignored `client_yaml/<client>/`. Aggregate counts were taken; **no client values are recorded in this repository.**
- Quill v0.9.2 baseline and `docs/agent-instructions.md`.
- Acme Plumbing and starter themes as the working reference for the contract.

## What Skippy produces today

Purpose: client-intake YAML that drives `rr_yacss_factory` bulk YACSS builds (link pyramids, listicles, silo pages). It is not a website description.

- Flat mapping. Keys are the **form's UI labels** (for example `* Client Name`, `* Target Cities (one per line)`), so renaming a label breaks consumers.
- No `schema_version`, no provenance, no approval state.
- Multi-line fields are stored either as YAML lists or as newline-joined strings (`YACSS Tiers` is `"1:3\n2:3"`).
- Blank values are empty strings. There is no way to distinguish "none", "unknown" and "not asked".
- Roughly 25 keys are website-relevant. About 30 are YACSS build mechanics and are irrelevant to Quill.

### Key mapping (legacy key to proposed `site_profile` v1.0 field)

| Legacy key | Present in | Proposed field | Notes |
|---|---|---|---|
| `* Client Name` | 46/46 | `business.display_name` | |
| `Legal / Company Name` | 25/46 | `business.legal_name` | Falls back to display name |
| `* Business Category` / `Primary Business Category` | 46/46 | `business.category` | Two keys, usually equal; conflict must be flagged |
| `* Phone` | 46/46 | `business.phone_display`, derive `phone_e164` | Format `(999) 999-9999`; E.164 needs country |
| `Email` / `Contact Email Address` | 46/46 | `business.contact_email` | Two keys; the service-area fixture shows one blank and one filled |
| `* Website` | 46/46 | `business.website` | |
| `Street Address`, `City`, `State`, `ZIP`, `Country` | 46/46 | `locations[0].address` | Single address only; blank street means service-area business |
| `Google Maps Embed Code` | 46/46 | `locations[0].map_embed` | Raw iframe HTML: untrusted, must be sanitized |
| `* Target Cities` | 46/46 | `service_areas[]` | Cities for SEO city pages, not physical presence |
| `* Services` | 46/46 | `services[]` | Plain strings: no ids, slugs or descriptions |
| `Social/Citation URLs` | 46/46 | `seo.social_profiles[]` | Citation URLs mixed with social profiles |
| `Logo URL`, `Hero Image URL`, `Content Image URL(s)` | 46/46, 46/46, 44/46 | `branding.logo`, media inventory | Remote URLs, no alt text or rights |
| `FAQ Questions & Answers` | 46/46 | `content.faq[]` | List of `{question, answer}` |
| `Broker *` | 46/46 | out of scope | Always blank in the sample; ignore |
| `YACSS *`, `city_embeds` | various | out of scope | Build mechanics for rr_yacss_factory |

### Corpus statistics (46 files, 13 clients; counts only, no client values recorded)

| Observation | Result | Implication |
|---|---|---|
| Files per client | 8 of 13 clients have more than one file | There is no single profile per client |
| Identity drift between a client's files | Phone differs for 3 clients, website for 4, client name for 1, contact email for 1 | The adapter must merge per client and report conflicts; it cannot read one file in isolation |
| `Email` versus `Contact Email Address` | Never both set and different; 1 file has neither, 1 has only `Email` | Safe to coalesce; warn when neither is set |
| `* Business Category` versus `Primary Business Category` | Differ in 14 of 46 files | Real conflict: treat `Primary` as the canonical category, keep the other as a secondary term, warn |
| Blank street address | 1 of 46 | Service-area businesses are rare here but real; need an explicit flag |
| Blank phone | 1 of 46 | Required field: block the build |
| Blank logo / hero image | 11 / 19 of 46 | Media is often missing; generator must supply a neutral placeholder and list it as an unresolved question |
| Empty Google Maps embed | 10 of 46 | Optional; no script tags found in the 36 that have one (still sanitize) |
| Target cities per file | 0 to 428 | Navigation cannot list them; city pages need an index, not a menu |
| Services per file | 0 to 253 | Counts this large are product or keyword lists, not a service menu; the schema needs a curated "featured services" subset |
| Social/Citation URLs not on a social host | 111 entries | Confirms citations and social profiles must be separate fields |
| Build type | Diagram 36, Listicle 4, Masspage_Silo_Local 1, blank 5 | Irrelevant to Quill |

### Gaps the legacy format cannot express

Opening hours, multiple locations, service-area-business flag, service slugs and descriptions, page list and template family, navigation, branding tokens (palette, type), SEO overrides, locale and timezone, image alt text and rights, provenance and approval. A one-way adapter can fill the first group only partially; the rest must be supplied by a new `site_profile` document or marked unknown.

### Data quality observations

- Two email keys and two category keys per file.
- Several keys blank in every sampled file (`Broker *`, `City Page Hero Image Base URL`).
- Google Maps embed is stored as raw HTML.
- Services are bare strings, so stable ids must be derived (slugified, then frozen on first approval).

## Quill content API relevant to generated themes

From the baseline and `docs/agent-instructions.md`:

- Helpers: `quill_text`, `quill_richtext`, `quill_button`, `quill_image`, `quill_managed_region_open/close`. Field IDs `{scope}.{section}.{field}`, saved values keyed by ID.
- Storage: options `quill_values`, `quill_field_registry`, `quill_activity_db_version`. Values are **shared by ID across themes on one site**; a regenerated theme inherits existing client edits (this is the behavior the plan relies on, and it also means test sites need values cleared between unrelated themes).
- Fallback: themes wrap helpers in `function_exists()` safe wrappers so they render defaults when Quill is inactive.
- Permissions: editing requires an authenticated user with edit capability; managed regions link out to the owning plugin, whose own capability applies (HTML Forms requires `edit_forms`).
- Verified in practice (Acme Plumbing, 2026-10-17): activation setup, seed-once menu, image import, HTML Forms integration, visual and edit-mode QA all passed.

## Owners of related concerns

| Concern | Current owner |
|---|---|
| Forms | HTML Forms plugin (theme creates the form once, never overwrites) |
| SEO and schema | Not yet decided per site; must be set during provisioning to avoid duplicate LocalBusiness graphs |
| Media | WordPress Media Library, imported from a directory outside the theme by WP-CLI |
| Business identity | Skippy YAML (build time) then WordPress; Quill holds only client-edited copy |

## Decisions and recommendation

1. **Add a new versioned `site_profile` schema; do not extend the legacy files.** Changing label-keyed YAML risks the YACSS factory pipeline and cannot carry the missing site data.
2. **Provide a one-way `from-skippy` adapter** that maps the table above, flags conflicts (email, category, address), and records everything else as `unknown`. Required identity gaps block the build.
3. **Build the validator and manifest tooling as a separate Python package/repo (`quill-site-builder`)**, matching Skippy's toolchain (pytest, ruff, black, mypy). Nothing YAML-related goes into the Quill plugin.
4. **Treat embedded HTML (maps) and URLs as untrusted**; parse with `yaml.safe_load` only.
5. **Reuse Acme and the starter** as the first `local-service` theme family and as the template for the Phase 3 generator and Phase 4 provisioner.

## Fixtures

Fully fictional, legacy shape, committed under `docs/discovery/fixtures/`:

- `skippy-legacy-single-location.yaml`: ordinary single-address business.
- `skippy-legacy-service-area.yaml`: no street address, five target cities, one blank email key and one filled.

A genuinely multi-location fixture cannot exist in the legacy shape (one address per file); it will be authored in the new `site_profile` format in Phase 1.

## Decisions (owner answers, 2026-10-17)

1. **Schema/structured-data owner:** the RankRocket SEO Control Layer. Generated themes do not emit LocalBusiness JSON-LD; the manifest supplies verified facts to the plugin. Encoded in the schema as `seo.schema_owner`.
2. **Profile storage (recommendation, pending owner confirmation):** a new private repo `quill-client-profiles`, one folder per client: `clients/<client-slug>/base.yaml` (the human-maintained BASE file) and `clients/<client-slug>/approved/<digest>.json` (immutable snapshots with provenance). Rationale: Skippy's `client_yaml/` is gitignored and shared with `rr_yacss_factory`, and the builder repo must stay free of client data.
3. **Tooling repo:** new private repo `PenZenMaster/quill-site-builder` (created).
4. **Pilot:** Salvo Metal Works, using `client_yaml/salvo_metal_works/general.yaml` (website is the site root, has logo, hero, map and FAQ). Note: most other Salvo files use a product-category URL as `* Website`; the adapter must reduce a deep URL to its origin.
5. **Conflicts:** human pick. The owner will adopt an SOP requiring one BASE YAML file per client. Several files per client exist because they are shared with `rr_yacss_factory`. The adapter reads the BASE file for site facts and treats the other files as legacy YACSS inputs only.

## Resolved by the corpus
1. Email keys never conflict (coalesce); category keys conflict in 14 files, so `Primary Business Category` wins and the conflict is reported.
2. Citation URLs and social profiles must be separate fields (111 non-social entries).

## Open
- Confirm the `quill-client-profiles` repo recommendation above.
- What the BASE file format is: the new `site_profile` directly, or the legacy shape plus a small `site:` extension block? Recommendation: the new `site_profile` format, with `from-skippy` used once to bootstrap each client's BASE file.
