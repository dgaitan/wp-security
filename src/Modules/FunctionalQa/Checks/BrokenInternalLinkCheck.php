<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Checks;

class BrokenInternalLinkCheck extends AbstractBrokenResourceCheck {

	public function id(): string {
		return 'functional_qa.broken_internal_links';
	}

	public function label(): string {
		return __( 'Broken Internal Links', 'wp-security' );
	}

	protected function contextKey(): string {
		return 'broken_internal_links';
	}

	protected function resourceNoun(): string {
		return __( 'link(s)', 'wp-security' );
	}
}
