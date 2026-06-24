<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\CoreIntegrity\Checks;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;
use WPSecurity\Contracts\Check;
use WPSecurity\Contracts\Context;
use WPSecurity\Domain\Finding;
use WPSecurity\Domain\Severity;
use WPSecurity\Domain\Status;

/**
 * Scans wp-content for suspicious files using a blacklist (known web-shell names /
 * dangerous extensions / upload-directory PHP files) and a theme whitelist
 * (any file extension not on the allow-list is flagged).
 *
 * Scope:
 *   - wp-content/uploads/  — PHP files are never legitimate here.
 *   - wp-content/** (all)  — blacklisted filenames or extensions.
 *   - wp-content/themes/** — files with extensions outside the expected set.
 */
class SuspiciousFilesCheck implements Check {

	/**
	 * Maximum recursion depth inside wp-content to prevent scanning
	 * deeply nested but legitimate cache or vendor trees.
	 */
	private const MAX_DEPTH = 6;

	/**
	 * Exact lower-cased filenames known to belong to web shells or
	 * sensitive backups that should not be web-accessible.
	 *
	 * @var list<string>
	 */
	private const BLACKLIST_NAMES = [
		// PHP web shells.
		'c99.php',
		'r57.php',
		'b374k.php',
		'weevely.php',
		'shell.php',
		'webshell.php',
		'phpshell.php',
		'cmd.php',
		'cmdshell.php',
		'command.php',
		'backdoor.php',
		'exploit.php',
		'eval.php',
		'bypass.php',
		'indoxploit.php',
		'alfa.php',
		'alfanew.php',
		'priv8.php',
		'wso.php',
		// Configuration backups that expose credentials if downloaded.
		'wp-config.bak',
		'wp-config.php.bak',
		'wp-config.old',
		// Database exports that should never be web-accessible.
		'dump.sql',
		'db.sql',
		'backup.sql',
	];

	/**
	 * File extensions that can execute server-side or be misused as shells.
	 *
	 * @var list<string>
	 */
	private const BLACKLIST_EXTENSIONS = [
		'phtml',
		'shtml',
		'cgi',
		'pl',
	];

	/**
	 * Extensions that are expected to appear inside theme directories.
	 * Any file with an extension NOT in this list is flagged as suspicious.
	 *
	 * @var list<string>
	 */
	private const THEME_WHITELIST_EXTENSIONS = [
		// Server-side / templating.
		'php',
		// Stylesheets.
		'css',
		'scss',
		'less',
		// Scripts.
		'js',
		'json',
		'map',
		// Documentation / localisation.
		'txt',
		'md',
		'pot',
		'po',
		'mo',
		// Raster images.
		'png',
		'jpg',
		'jpeg',
		'gif',
		'webp',
		'avif',
		// Vector / icon.
		'svg',
		'ico',
		// Web fonts.
		'woff',
		'woff2',
		'ttf',
		'eot',
		'otf',
		// Markup / feeds.
		'html',
		'htm',
		'xml',
		// Lock files (composer.lock, package-lock.json).
		'lock',
	];

	public function id(): string {
		return 'core_integrity.suspicious_files';
	}

	public function label(): string {
		return __( 'Suspicious Files in wp-content', 'wp-security' );
	}

	public function run( Context $context ): Finding {
		$contentPath = rtrim( $context->contentPath(), '/\\' ) . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $contentPath ) ) {
			return Finding::skipped(
				$this->id(),
				$this->label(),
				'The wp-content directory could not be found or is not accessible.'
			);
		}

		$found = $this->scan( $contentPath );

		$total = count( $found['php_in_uploads'] )
			+ count( $found['blacklisted'] )
			+ count( $found['double_extension'] )
			+ count( $found['theme_violations'] );

		if ( 0 === $total ) {
			return Finding::pass(
				$this->id(),
				$this->label(),
				__( 'No suspicious files were detected in wp-content.', 'wp-security' )
			);
		}

		$severity = $this->resolveSeverity( $found );
		$evidence = $this->buildEvidence( $found );

		return new Finding(
			checkId:        $this->id(),
			status:         Status::FAIL,
			severity:       $severity,
			title:          $this->label(),
			description:    sprintf(
				/* translators: %d: number of suspicious files detected */
				_n(
					'%d suspicious file was detected in wp-content.',
					'%d suspicious files were detected in wp-content.',
					$total,
					'wp-security'
				),
				$total
			),
			recommendation: __( 'Review and remove any unrecognised files. PHP files in the uploads directory are a common web-shell vector. Remove any wp-config backups immediately — they expose database credentials if downloaded. Restore modified theme files from a trusted backup.', 'wp-security' ),
			evidence:       $evidence,
		);
	}

	/**
	 * Walk wp-content and categorise suspicious files.
	 *
	 * @return array{php_in_uploads: list<string>, blacklisted: list<string>, double_extension: list<string>, theme_violations: list<string>}
	 */
	private function scan( string $contentPath ): array {
		$phpInUploads    = [];
		$blacklisted     = [];
		$doubleExtension = [];
		$themeViolations = [];

		$uploadsPrefix = $contentPath . 'uploads' . DIRECTORY_SEPARATOR;
		$themesPrefix  = $contentPath . 'themes' . DIRECTORY_SEPARATOR;

		try {
			/** @var RecursiveIteratorIterator<\SplFileInfo> $iterator */
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator(
					$contentPath,
					RecursiveDirectoryIterator::SKIP_DOTS
				),
				RecursiveIteratorIterator::SELF_FIRST,
				RecursiveIteratorIterator::CATCH_GET_CHILD
			);
			$iterator->setMaxDepth( self::MAX_DEPTH );

			foreach ( $iterator as $fileInfo ) {
				if ( $fileInfo->isDir() ) {
					continue;
				}

				$path      = $fileInfo->getPathname();
				$filename  = strtolower( $fileInfo->getFilename() );
				$ext       = strtolower( $fileInfo->getExtension() );
				$relPath   = substr( $path, strlen( $contentPath ) );
				$inUploads = str_starts_with( $path, $uploadsPrefix );
				$inThemes  = str_starts_with( $path, $themesPrefix );

				// PHP files inside uploads/ are never legitimate.
				if ( $inUploads && 'php' === $ext ) {
					$phpInUploads[] = $relPath;
					continue;
				}

				// Known malicious or sensitive filename.
				if ( in_array( $filename, self::BLACKLIST_NAMES, true ) ) {
					$blacklisted[] = $relPath;
					continue;
				}

				// Blacklisted file extension (e.g. .phtml, .shtml).
				if ( in_array( $ext, self::BLACKLIST_EXTENSIONS, true ) ) {
					$blacklisted[] = $relPath;
					continue;
				}

				// Double-extension attack (e.g. image.php.jpg — bypasses naive MIME checks).
				if ( 1 === preg_match( '/\.php\./i', $fileInfo->getFilename() ) ) {
					$doubleExtension[] = $relPath;
					continue;
				}

				// Files in theme directories with an unexpected extension.
				if ( $inThemes && '' !== $ext && ! in_array( $ext, self::THEME_WHITELIST_EXTENSIONS, true ) ) {
					$themeViolations[] = $relPath;
				}
			}
		} catch ( UnexpectedValueException ) {
			// Directory not readable — skip silently rather than surfacing a PHP error.
		}

		return [
			'php_in_uploads'   => $phpInUploads,
			'blacklisted'      => $blacklisted,
			'double_extension' => $doubleExtension,
			'theme_violations' => $themeViolations,
		];
	}

	/**
	 * Determine overall severity from the worst category found.
	 *
	 * @param array{php_in_uploads: list<string>, blacklisted: list<string>, double_extension: list<string>, theme_violations: list<string>} $found
	 */
	private function resolveSeverity( array $found ): Severity {
		if ( [] !== $found['php_in_uploads'] || [] !== $found['blacklisted'] ) {
			return Severity::CRITICAL;
		}

		if ( [] !== $found['double_extension'] ) {
			return Severity::HIGH;
		}

		return Severity::MEDIUM;
	}

	/**
	 * Build the evidence array, omitting empty categories.
	 *
	 * @param array{php_in_uploads: list<string>, blacklisted: list<string>, double_extension: list<string>, theme_violations: list<string>} $found
	 * @return array<string, list<string>>
	 */
	private function buildEvidence( array $found ): array {
		$evidence = [];

		if ( [] !== $found['php_in_uploads'] ) {
			$evidence['php_in_uploads'] = $found['php_in_uploads'];
		}

		if ( [] !== $found['blacklisted'] ) {
			$evidence['blacklisted'] = $found['blacklisted'];
		}

		if ( [] !== $found['double_extension'] ) {
			$evidence['double_extension'] = $found['double_extension'];
		}

		if ( [] !== $found['theme_violations'] ) {
			$evidence['theme_violations'] = $found['theme_violations'];
		}

		return $evidence;
	}
}
