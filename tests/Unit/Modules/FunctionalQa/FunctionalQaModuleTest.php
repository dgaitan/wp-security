<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\FunctionalQa;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\FunctionalQa\FunctionalQaModule;

final class FunctionalQaModuleTest extends TestCase {

	private FunctionalQaModule $module;

	protected function setUp(): void {
		$this->module = new FunctionalQaModule();
	}

	public function test_id_is_functional_qa(): void {
		$this->assertSame( 'functional_qa', $this->module->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->label() );
	}

	public function test_icon_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );

		$this->assertContains( 'functional_qa.homepage_reachability', $ids );
		$this->assertContains( 'functional_qa.primary_navigation', $ids );
		$this->assertContains( 'functional_qa.footer_links', $ids );
		$this->assertContains( 'functional_qa.primary_cta', $ids );
		$this->assertContains( 'functional_qa.key_landing_pages', $ids );
		$this->assertContains( 'functional_qa.search_function', $ids );
		$this->assertContains( 'functional_qa.broken_internal_links', $ids );
		$this->assertContains( 'functional_qa.media_loading', $ids );
	}

	public function test_check_ids_are_unique(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );
		$this->assertSame( $ids, array_unique( $ids ) );
	}
}
