<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\PluginsThemes\Checks;

use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Flags known front-end JavaScript libraries loaded below a safe minimum
 * version, based on filename/query-string version sniffing.
 *
 * Outdated bundled/CDN-loaded JS libraries are the same causal category as
 * outdated plugins from the site owner's perspective — a directly
 * exploitable, publicly-disclosed CVE in code the site is running — which
 * is why this lives alongside PluginUpdatesCheck rather than in the Headers
 * module, even though it reads the same page_asset_tags context key as
 * SriCheck.
 *
 * The reference list of known libraries lives in Data/known-js-libraries.json
 * (not in this class) so it can be reviewed/extended as a data-maintenance
 * task instead of a code change. Each entry:
 *   library          — display name shown in the Finding.
 *   name_pattern     — regex; matches when the library appears in a script URL at all.
 *   version_pattern  — regex with one capture group for a semver-like version.
 *   min_safe_version — versions below this (via version_compare) are flagged.
 *   reference        — the CVE/advisory backing the min_safe_version threshold.
 * Order matters: entries are matched top-to-bottom and the first match wins,
 * so more specific patterns (e.g. "jQuery UI") must precede broader ones
 * whose name_pattern would also match them (e.g. "jQuery", since a
 * "jquery-ui-1.13.2.min.js" URL also contains the substring "jquery").
 *
 * This is a best-effort heuristic: minified or fingerprinted bundles with no
 * version string in the URL cannot be matched and are not treated as
 * failures, to avoid false positives.
 */
class OutdatedJsLibraryCheck implements Check {

	/** @var array<int, array{library: string, name_pattern: string, version_pattern: string, min_safe_version: string, reference: string}>|null */
	private static ?array $knownLibraries = null;

	public function id(): string {
		return 'plugins_themes.outdated_js_libraries';
	}

	public function label(): string {
		return __( 'Outdated JavaScript Libraries', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$tags = $context->get( 'page_asset_tags' );

		if ( ! is_array( $tags ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not fetch homepage HTML to inspect loaded scripts.'
			);
		}

		$knownLibraries = $this->loadKnownLibraries();

		if ( null === $knownLibraries ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'Could not load the known-JavaScript-library reference data.'
			);
		}

		$scriptTags     = array_filter(
			$tags,
			static fn ( array $tag ): bool => 'script' === ( $tag['type'] ?? '' )
		);
		$outdated       = [];
		$versionUnknown = [];

		foreach ( $scriptTags as $tag ) {
			$url = (string) ( $tag['url'] ?? '' );

			foreach ( $knownLibraries as $library ) {
				if ( 1 !== preg_match( $library['name_pattern'], $url ) ) {
					continue;
				}

				if ( 1 !== preg_match( $library['version_pattern'], $url, $matches ) ) {
					$versionUnknown[] = [
						'library' => $library['library'],
						'url'     => $url,
					];
					continue 2;
				}

				$version = $matches[1];

				if ( version_compare( $version, $library['min_safe_version'], '<' ) ) {
					$outdated[] = [
						'library'   => $library['library'],
						'version'   => $version,
						'url'       => $url,
						'reference' => $library['reference'],
					];
				}

				continue 2;
			}
		}

		if ( [] !== $outdated ) {
			$names = implode(
				', ',
				array_map(
					static fn ( array $item ): string => sprintf( '%s %s', $item['library'], $item['version'] ),
					$outdated
				)
			);

			return new Finding(
				checkId:        $this->id(),
				status:         Status::WARN,
				severity:       Severity::HIGH,
				title:          $this->label(),
				description:    sprintf(
					/* translators: %s: comma-separated list of outdated library names/versions */
					__( 'Outdated JavaScript libraries with known vulnerabilities were detected: %s.', 'wp-security' ),
					$names
				),
				recommendation: __( 'Update the flagged JavaScript libraries to a patched version.', 'wp-security' ),
				evidence:       [
					'outdated'        => $outdated,
					'version_unknown' => $versionUnknown,
				],
			);
		}

		if ( [] !== $versionUnknown ) {
			return new Finding(
				checkId:        $this->id(),
				status:         Status::PASS,
				severity:       Severity::INFO,
				title:          $this->label(),
				description:    __( 'No known-vulnerable JavaScript library versions were detected. Some recognized libraries had no parseable version string and could not be fully verified.', 'wp-security' ),
				recommendation: '',
				evidence:       [ 'version_unknown' => $versionUnknown ],
			);
		}

		return Finding::pass(
			$this->id(),
			$this->label(),
			__( 'No known-vulnerable JavaScript libraries were detected.', 'wp-security' )
		);
	}

	/**
	 * Loads and caches the known-library reference data from
	 * Data/known-js-libraries.json via wp_json_file_decode() — the same
	 * core WordPress function theme.json/block.json use to load bundled
	 * JSON, which keeps this out of the raw-filesystem-call sniffs that
	 * apply to direct file_get_contents()/fopen() usage in plugin code.
	 *
	 * Cached on the class (not the instance) since PluginsThemesModule
	 * creates a fresh Check instance per scan, but the underlying file
	 * never changes within a single PHP request.
	 *
	 * @return array<int, array{library: string, name_pattern: string, version_pattern: string, min_safe_version: string, reference: string}>|null
	 */
	private function loadKnownLibraries(): ?array {
		if ( null !== self::$knownLibraries ) {
			return self::$knownLibraries;
		}

		$decoded = wp_json_file_decode(
			__DIR__ . '/../Data/known-js-libraries.json',
			[ 'associative' => true ]
		);

		if ( ! is_array( $decoded ) ) {
			return null;
		}

		/** @var array<int, array{library: string, name_pattern: string, version_pattern: string, min_safe_version: string, reference: string}> $decoded */
		self::$knownLibraries = $decoded;

		return self::$knownLibraries;
	}
}
