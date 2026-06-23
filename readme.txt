=== WP Security ===
Contributors: david-gaitan
Tags: security, audit, hardening, scanner, performance
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An all-in-one security and performance auditing suite for WordPress.

== Description ==

WP Security inspects a WordPress installation across nine domains — server health, DNS and HTTP headers, WordPress core integrity, plugins and themes, database, page speed, accessibility, SEO, and users — and presents the findings through a single React-powered dashboard.

Every audit check is a small, self-contained, swappable unit. Third-party developers can add custom checks and entire modules through WordPress hooks without touching plugin core.

**Key features:**

* Nine audit modules covering security, performance, and code quality.
* Weighted overall grade (A–F) and per-module scores.
* Non-blocking background scans via Action Scheduler.
* Prioritized findings with plain-language descriptions and concrete recommendations.
* Read-only by default — the auditor never mutates site data during a scan.
* Extensible through `wp_security/modules` and `wp_security/checks/{module}` filters.

== Installation ==

1. Upload the `wp-security` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WP Security** in the admin menu to run your first scan.

== Changelog ==

= 0.1.0 =
* Initial scaffolding release.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
