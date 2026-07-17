# WP Security — Claude context

See @docs/wp-security/about-the-plugin.md for the full technical specification.
See @.claude/skills/wp-security-architecture/SKILL.md for the architecture quick-reference.

---

## Commands

```bash
# Install / update PHP deps
composer install

# Lint (WordPress Coding Standards)
composer lint
composer lint-fix

# Static analysis (PHPStan level 6)
composer analyse

# Unit + integration tests
composer test

# Run all checks (lint → analyse → test)
composer check

# JS build
npm run build          # production
npm run start          # watch / dev
npm run lint:js        # ESLint
```

---

## Architecture invariant — YOU MUST respect this

Every piece of auditing logic MUST follow the Module → Check → Finding chain:

1. A **Check** runs one focused, side-effect-free test and returns a **Finding**.
2. A **Module** is a thin container that declares identity and yields Checks.
3. Modules and individual Checks are registered through WordPress filters — never hard-coded:
   - `wp_security/modules` → register a whole Module.
   - `wp_security/checks/{module-id}` → add Checks to an existing Module.

Never bypass this chain. Never write scan logic directly in a controller or service.

---

## Remediation actions — the one mutating-action primitive (Sprint 9)

Everything above is about `Check`s, which MUST stay read-only. `RemediationAction` (`src/Contracts/RemediationAction.php`) is the single, deliberate exception:

1. A **RemediationAction** declares a real WP capability (`update_plugins`, not just `manage_options`), a `describe()` confirmation string, an `isAvailable()` re-check, and an `apply()` that mutates and returns a `RemediationResult` (never a `Finding` — remediation outcomes are audit-log entries, not scored check results).
2. Actions register through `wp_security/remediations` — never hard-coded — mirroring `wp_security/modules` exactly.
3. `POST /wp-security/v1/remediations/{id}/apply` requires the action's own `capability()` *and* an explicit `confirm: true` in the body. A nonce-valid, authenticated POST without `confirm: true` MUST still do nothing.
4. Applications are logged to `wpsec_remediation_log` via `RemediationLogRepository` — never reuse `wpsec_findings`/`Status` for this.

Never add a mutating side effect to a `Check`. If a new action needs to change site state, it's a new `RemediationAction`, gated the same way.

---

## Instance- and theme-agnostic — no external runtime dependency (Sprint 10)

The plugin must work identically on any WordPress install out of the box: no Node/Puppeteer/browser runner, no per-site credential setup beyond what WordPress itself provides, no dependency on a specific theme's menu registration. This is why the Functional QA module's site crawler (`ScanContext`) parses rendered `<nav>`/`<footer>` HTML instead of calling `wp_get_nav_menu_items()` against a theme-specific location, and why broken-link/media checks use WordPress's own `wp_remote_head()`/`wp_remote_get()` HTTP API rather than a headless browser. Checks that genuinely require JavaScript execution (console errors, confirming a tag fires at runtime, a rendered mobile-viewport check) are a real, acknowledged limit of this approach — they're deferred as an optional future enhancement, not worked around with an external dependency this plugin otherwise doesn't need.

---

## DRY — shared primitives

| What | Where | Rule |
|---|---|---|
| Check result | `src/Domain/Finding.php` | Every check returns a `Finding`; never return raw arrays |
| Status values | `src/Domain/Status.php` | Use the `Status` enum; never hardcode status strings |
| Severity + penalty | `src/Domain/Severity.php` | Use the `Severity` enum; `penalty()` is the single source of scoring truth |
| Score calculation | `src/Scoring/ScoringService.php` | All module + overall scores come from here; no ad-hoc arithmetic |
| Remediation result | `src/Domain/RemediationResult.php` | Every `RemediationAction::apply()` returns a `RemediationResult`; never a `Finding` |
| Remediation log | `src/Persistence/RemediationLogRepository.php` | All applied-remediation audit rows go here; never `wpsec_findings` |
| REST base | `src/Rest/AbstractController.php` | All controllers extend this; permission check + respond() live here |

---

## Security rules — non-negotiable

- **Read-only scans.** A Check MUST NOT mutate site data. Report and recommend; never auto-fix.
- **Capability check on every REST route.** All routes use `permissionCheck()` from `AbstractController`. Do not skip it.
- **`$wpdb->prepare()` only.** No string-interpolated SQL anywhere in `src/`.
- **Escape all output.** Use `esc_html()`, `esc_attr()`, `wp_kses_post()`, etc. Never echo raw user data.
- **Sanitize all input.** Use `sanitize_text_field()`, `sanitize_email()`, `absint()`, etc. before storing or acting on any user-supplied value.
- **Secrets never returned to client.** API keys stored with `autoload = false`; only masked previews (`SG.••••`) sent in REST responses.

---

## Code style

- PHP 8.1+: typed properties, enums, `readonly`, `match`, named arguments.
- `declare(strict_types=1)` at the top of every PHP file.
- PSR-4 autoloading: namespace `WPSecurity\` maps to `src/`.
- WordPress Coding Standards (PHPCS `WordPress` + `WordPress-Extra`).
- All user-facing strings wrapped in `__()` / `esc_html__()` with text domain `wp-security`.

---

## Styles

**No inline styles permitted.** Every visual concern belongs in SCSS.

- SCSS files live in `assets/app/styles/`.
- Entry point: `assets/app/styles/index.scss` — imported from `assets/app/index.jsx`.
- Partials follow the `_name.scss` convention and are `@use`d from `index.scss`.
- All class names are prefixed `wpsec-` to avoid collisions with wp-admin.
- BEM naming: `wpsec-block`, `wpsec-block__element`, `wpsec-block--modifier`.
- Variables (colours, spacing, typography) live in `_variables.scss`; always `@use 'variables' as *` in partials that need them.
- Dynamic colours (e.g. per-grade score colours) are expressed with `data-*` attribute selectors in SCSS — never as computed inline styles.
- `sass` and `sass-loader` ship with `@wordpress/scripts`; no separate install needed.
- `npm run build` outputs both `build/index.js` and `build/index.css`; `AdminPage::enqueueAssets()` enqueues the CSS automatically when `build/index.css` exists.

---

## Testing expectations

- Every new Check class gets a corresponding unit test in `tests/Unit/`.
- Unit tests mock `Context` — no WordPress installation needed.
- Integration tests in `tests/Integration/` cover REST endpoints and repositories.
- Run `composer check` before marking any sprint task done.

---

## When compacting

Preserve: the architecture invariant (Module → Check → Finding), the security rules list, the list of modified files in the current sprint, and any failing test output.
