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
 * This is a best-effort heuristic: minified or fingerprinted bundles with no
 * version string in the URL cannot be matched and are not treated as
 * failures, to avoid false positives.
 */
class OutdatedJsLibraryCheck implements Check {

	/**
	 * name_pattern detects the library is loaded at all; version_pattern
	 * additionally requires a semver-like version to appear right after the
	 * library name. Kept as two separate patterns (rather than one pattern
	 * with an optional version group) because a required-but-optional
	 * capture group would still force the whole match to fail whenever the
	 * filename has no version segment (e.g. a custom/minified build name),
	 * which would make the "recognized library, unparseable version"
	 * fallback path unreachable.
	 *
	 * @var array<int, array{library: string, name_pattern: string, version_pattern: string, min_safe_version: string, reference: string}>
	 */
	private const KNOWN_LIBRARIES = [
		[
			'library'          => 'jQuery',
			'name_pattern'     => '/jquery/i',
			'version_pattern'  => '/jquery[.\-@]?v?(\d+\.\d+\.\d+)/i',
			'min_safe_version' => '3.5.0',
			'reference'        => 'CVE-2020-11022',
		],
		[
			'library'          => 'Vue',
			'name_pattern'     => '/\bvue\b/i',
			'version_pattern'  => '/vue[.\-@]?v?(\d+\.\d+\.\d+)/i',
			'min_safe_version' => '3.0.0',
			'reference'        => 'CVE-2024-6783',
		],
	];

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

		$scriptTags     = array_filter(
			$tags,
			static fn ( array $tag ): bool => 'script' === ( $tag['type'] ?? '' )
		);
		$outdated       = [];
		$versionUnknown = [];

		foreach ( $scriptTags as $tag ) {
			$url = (string) ( $tag['url'] ?? '' );

			foreach ( self::KNOWN_LIBRARIES as $library ) {
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
}
