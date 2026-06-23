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

## DRY — shared primitives

| What | Where | Rule |
|---|---|---|
| Check result | `src/Domain/Finding.php` | Every check returns a `Finding`; never return raw arrays |
| Status values | `src/Domain/Status.php` | Use the `Status` enum; never hardcode status strings |
| Severity + penalty | `src/Domain/Severity.php` | Use the `Severity` enum; `penalty()` is the single source of scoring truth |
| Score calculation | `src/Scoring/ScoringService.php` | All module + overall scores come from here; no ad-hoc arithmetic |
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

## Testing expectations

- Every new Check class gets a corresponding unit test in `tests/Unit/`.
- Unit tests mock `Context` — no WordPress installation needed.
- Integration tests in `tests/Integration/` cover REST endpoints and repositories.
- Run `composer check` before marking any sprint task done.

---

## When compacting

Preserve: the architecture invariant (Module → Check → Finding), the security rules list, the list of modified files in the current sprint, and any failing test output.
