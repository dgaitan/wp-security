<?php

declare( strict_types=1 );

namespace WPSecurity\Modules\FunctionalQa\Checks;

class PrimaryCtaCheck extends AbstractUrlListCheck {

	public function id(): string {
		return 'functional_qa.primary_cta';
	}

	public function label(): string {
		return __( 'Primary CTAs', 'wp-security' );
	}

	protected function contextKey(): string {
		return 'cta_link_statuses';
	}

	protected function emptyMessage(): string {
		return 'No primary CTA URLs are configured in settings.';
	}
}
