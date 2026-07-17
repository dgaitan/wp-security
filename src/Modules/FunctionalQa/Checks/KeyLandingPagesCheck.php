<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Checks;

class KeyLandingPagesCheck extends AbstractUrlListCheck {

	public function id(): string {
		return 'functional_qa.key_landing_pages';
	}

	public function label(): string {
		return __( 'Key Landing Pages', 'wp-security' );
	}

	protected function contextKey(): string {
		return 'key_landing_page_statuses';
	}

	protected function emptyMessage(): string {
		return 'No key landing page URLs are configured in settings.';
	}
}
