<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\MarketingAnalytics;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\MarketingAnalytics\MarketingAnalyticsModule;

final class MarketingAnalyticsModuleTest extends TestCase {

	private MarketingAnalyticsModule $module;

	protected function setUp(): void {
		$this->module = new MarketingAnalyticsModule();
	}

	public function test_id_is_marketing_analytics(): void {
		$this->assertSame( 'marketing_analytics', $this->module->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->label() );
	}

	public function test_icon_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );

		$this->assertContains( 'marketing_analytics.gtm_presence', $ids );
		$this->assertContains( 'marketing_analytics.ga4_presence', $ids );
		$this->assertContains( 'marketing_analytics.meta_pixel_presence', $ids );
		$this->assertContains( 'marketing_analytics.cookie_consent_presence', $ids );
	}

	public function test_check_ids_are_unique(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );
		$this->assertSame( $ids, array_unique( $ids ) );
	}
}
