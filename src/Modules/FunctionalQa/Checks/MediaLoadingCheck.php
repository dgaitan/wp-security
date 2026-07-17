<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Checks;

class MediaLoadingCheck extends AbstractBrokenResourceCheck {

	public function id(): string {
		return 'functional_qa.media_loading';
	}

	public function label(): string {
		return __( 'Media Loading', 'wp-security' );
	}

	protected function contextKey(): string {
		return 'broken_media';
	}

	protected function resourceNoun(): string {
		return __( 'media file(s)', 'wp-security' );
	}
}
