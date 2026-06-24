<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Database;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\Database\DatabaseModule;

class DatabaseModuleTest extends TestCase {

	private DatabaseModule $module;

	protected function setUp(): void {
		$this->module = new DatabaseModule();
	}

	public function test_id_is_database(): void {
		$this->assertSame( 'database', $this->module->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->label() );
	}

	public function test_icon_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );

		$this->assertContains( 'database.autoloaded_options', $ids );
		$this->assertContains( 'database.suspicious_content', $ids );
	}

	public function test_check_ids_are_unique(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );
		$this->assertSame( $ids, array_unique( $ids ) );
	}
}
