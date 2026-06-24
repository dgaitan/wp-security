<?php

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Modules\Users;

use PHPUnit\Framework\TestCase;
use WPSecurity\Modules\Users\UsersModule;

class UsersModuleTest extends TestCase {

	private UsersModule $module;

	protected function setUp(): void {
		$this->module = new UsersModule();
	}

	public function test_id_is_users(): void {
		$this->assertSame( 'users', $this->module->id() );
	}

	public function test_label_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->label() );
	}

	public function test_icon_is_not_empty(): void {
		$this->assertNotEmpty( $this->module->icon() );
	}

	public function test_built_in_checks_are_registered(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );

		$this->assertContains( 'users.dormant_users', $ids );
		$this->assertContains( 'users.admin_count', $ids );
	}

	public function test_check_ids_are_unique(): void {
		$ids = array_map( static fn ( $c ) => $c->id(), iterator_to_array( $this->module->checks() ) );
		$this->assertSame( $ids, array_unique( $ids ) );
	}
}
