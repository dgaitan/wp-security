<?php

/**
 * PHPStan type stubs for third-party plugin classes this plugin optionally
 * integrates with (Contact Form 7, Gravity Forms) — never bundled, never
 * autoloaded by this plugin's own Composer setup. All real calls into these
 * classes are guarded by class_exists()/isActive() at runtime, so the class
 * may not exist on a given install; these stubs exist purely so PHPStan can
 * type-check the call sites, matching signatures verified against the
 * installed Contact Form 7 6.1.6 source (see ContactForm7Provider) and
 * Gravity Forms' published GFAPI documentation (see GravityFormsProvider).
 *
 * Never require'd/included outside PHPStan — see phpstan.neon.dist's
 * `stubFiles`, which loads this for type resolution only.
 */

class WPCF7_ContactForm {
	public function id(): int {}

	/** @return array<int, WPCF7_FormTag> */
	public function scan_form_tags(): array {}

	/**
	 * @param string|array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function submit( $options = '' ): array {}
}

class WPCF7_FormTag {
	public string $type     = '';
	public string $basetype = '';
	public string $name     = '';
	/** @var array<int, string> */
	public array $values = [];
}

function wpcf7_contact_form( int $id ): ?WPCF7_ContactForm {}

class GFAPI {
	/** @return array<string, mixed>|null */
	public static function get_form( int $id ) {}

	/** @return array<int, array<string, mixed>> */
	public static function get_forms( bool $active = true ): array {}

	/**
	 * @param array<int|string, mixed> $inputValues
	 * @return array<string, mixed>|WP_Error
	 */
	public static function submit_form( int $formId, array $inputValues ) {}
}
