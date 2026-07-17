<?php
/*
 * Feature: RemediationRegistry — Sprint 9
 *
 * Scenario: Actions register through the wp_security/remediations filter
 *   Given an action added via the wp_security/remediations filter
 *   When all() is called
 *   Then the returned array is keyed by action ID
 *
 * Scenario: A single action can be fetched by ID
 *   Given a registered action "alpha"
 *   When get("alpha") is called
 *   Then the action is returned, and get() for an unknown ID returns null
 *
 * Scenario: has() reports membership by ID
 *   Given a registered action "alpha"
 *   When has() is queried
 *   Then it returns true for "alpha" and false otherwise
 *
 * Scenario: With no filters the registry is empty
 *   Given no remediation filters registered
 *   When all() is called
 *   Then an empty array is returned
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use WPSecurity\Admin\RemediationRegistry;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;

final class RemediationRegistryTest extends TestCase {

	protected function tearDown(): void {
		remove_all_filters( 'wp_security/remediations' );
		parent::tearDown();
	}

	public function test_all_is_keyed_by_action_id(): void {
		add_filter(
			'wp_security/remediations',
			function ( array $actions ): array {
				$actions[] = $this->action( 'alpha' );
				$actions[] = $this->action( 'beta' );
				return $actions;
			}
		);

		$registry = new RemediationRegistry();
		$all      = $registry->all();

		$this->assertSame( [ 'alpha', 'beta' ], array_keys( $all ) );
		$this->assertSame( 'alpha', $all['alpha']->id() );
	}

	public function test_get_returns_action_or_null(): void {
		add_filter(
			'wp_security/remediations',
			fn ( array $actions ): array => array_merge( $actions, [ $this->action( 'alpha' ) ] )
		);

		$registry = new RemediationRegistry();

		$this->assertInstanceOf( RemediationAction::class, $registry->get( 'alpha' ) );
		$this->assertNull( $registry->get( 'missing' ) );
	}

	public function test_has_reports_membership(): void {
		add_filter(
			'wp_security/remediations',
			fn ( array $actions ): array => array_merge( $actions, [ $this->action( 'alpha' ) ] )
		);

		$registry = new RemediationRegistry();

		$this->assertTrue( $registry->has( 'alpha' ) );
		$this->assertFalse( $registry->has( 'missing' ) );
	}

	public function test_empty_without_filters(): void {
		$registry = new RemediationRegistry();

		$this->assertSame( [], $registry->all() );
	}

	/**
	 * Build a minimal RemediationAction test double with the given ID.
	 */
	private function action( string $id ): RemediationAction {
		return new class( $id ) implements RemediationAction {

			public function __construct( private string $id ) {}

			public function id(): string {
				return $this->id;
			}

			public function label(): string {
				return ucfirst( $this->id );
			}

			public function capability(): string {
				return 'manage_options';
			}

			public function describe( array $params ): string {
				return '';
			}

			public function isAvailable( Context $context, array $params ): bool {
				return true;
			}

			public function apply( Context $context, array $params ): RemediationResult {
				return RemediationResult::skipped( 'test double' );
			}
		};
	}
}
