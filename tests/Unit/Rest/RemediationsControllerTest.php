<?php
/*
 * Feature: RemediationsController — Sprint 9
 *
 * Scenario: apply() without confirm:true is rejected
 *   Given a registered, available, capability-permitted action
 *   When POST /remediations/{id}/apply is called without confirm:true
 *   Then a 400 error is returned and the action never runs
 *
 * Scenario: apply() is forbidden without the action's declared capability
 *   Given the current user lacks the action's capability
 *   When POST /remediations/{id}/apply is called with confirm:true
 *   Then a 403 error is returned
 *
 * Scenario: apply() rejects an unavailable action
 *   Given isAvailable() returns false for the given params
 *   When POST /remediations/{id}/apply is called with confirm:true
 *   Then a 409 error is returned
 *
 * Scenario: A confirmed, available, permitted single apply runs and is logged
 *   Given a valid confirmed request
 *   When POST /remediations/{id}/apply is called
 *   Then the action's apply() result is returned
 *   And a row is recorded in the remediation log
 *
 * Scenario: Bulk apply (items array) returns a batch id
 *   Given a confirmed request with an items array
 *   When POST /remediations/{id}/apply is called
 *   Then the response status is 202 and the body contains a batch_id
 *
 * Scenario: An unknown action id returns 404
 *   Given no action registered under the requested id
 *   When POST /remediations/{id}/apply is called
 *   Then a 404 error is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WPSecurity\Admin\RemediationRegistry;
use WPSecurity\Contracts\Context;
use WPSecurity\Contracts\RemediationAction;
use WPSecurity\Domain\RemediationResult;
use WPSecurity\Persistence\RemediationLogRepository;
use WPSecurity\Rest\RemediationsController;
use WPSecurity\Tests\Support\FakeWpdb;
use WPSecurity\Tests\Support\MockContext;

final class RemediationsControllerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		remove_all_filters( 'wp_security/remediations' );
		$GLOBALS['wp_security_test_can'] = [];
	}

	protected function tearDown(): void {
		remove_all_filters( 'wp_security/remediations' );
		unset( $GLOBALS['wp_security_test_can'] );
		parent::tearDown();
	}

	public function test_apply_without_confirm_is_rejected(): void {
		$controller                                        = $this->controller( $this->action( 'test.action', true, RemediationResult::success( 'done' ) ) );
		$GLOBALS['wp_security_test_can']['manage_options'] = true;

		$result = $controller->apply( $this->request( 'test.action', [ 'confirm' => false ] ) );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_apply_forbidden_without_capability(): void {
		$controller = $this->controller( $this->action( 'test.action', true, RemediationResult::success( 'done' ) ) );
		// wp_security_test_can left empty — current_user_can() defaults to false.

		$result = $controller->apply( $this->request( 'test.action', [ 'confirm' => true ] ) );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_apply_rejects_unavailable_action(): void {
		$controller                                        = $this->controller( $this->action( 'test.action', false, RemediationResult::success( 'done' ) ) );
		$GLOBALS['wp_security_test_can']['manage_options'] = true;

		$result = $controller->apply( $this->request( 'test.action', [ 'confirm' => true ] ) );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_confirmed_available_apply_runs_and_is_logged(): void {
		$log        = new RemediationLogRepository( new FakeWpdb() );
		$controller = $this->controller(
			$this->action( 'test.action', true, RemediationResult::success( 'It worked.' ) ),
			$log
		);
		$GLOBALS['wp_security_test_can']['manage_options'] = true;

		$response = $controller->apply( $this->request( 'test.action', [ 'confirm' => true ] ) );

		$this->assertSame( 'success', $response->get_data()['status'] );
		$this->assertSame( 'It worked.', $response->get_data()['message'] );
		$this->assertCount( 1, $log->recent() );
	}

	public function test_bulk_apply_returns_batch_id(): void {
		$controller                                        = $this->controller( $this->action( 'test.action', true, RemediationResult::success( 'done' ) ) );
		$GLOBALS['wp_security_test_can']['manage_options'] = true;

		$response = $controller->apply(
			$this->request(
				'test.action',
				[
					'confirm' => true,
					'items'   => [ [ 'target' => 'a' ], [ 'target' => 'b' ] ],
				]
			)
		);

		$this->assertSame( 202, $response->get_status() );
		$this->assertArrayHasKey( 'batch_id', $response->get_data() );
		$this->assertSame( 2, $response->get_data()['item_count'] );
	}

	public function test_unknown_action_returns_404(): void {
		$controller = $this->controller( null );

		$result = $controller->apply( $this->request( 'missing.action', [ 'confirm' => true ] ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( [ 'status' => 404 ], $result->get_error_data() );
	}

	private function controller( ?RemediationAction $action, ?RemediationLogRepository $log = null ): RemediationsController {
		if ( null !== $action ) {
			add_filter(
				'wp_security/remediations',
				static fn ( array $actions ): array => array_merge( $actions, [ $action ] )
			);
		}

		return new RemediationsController(
			new RemediationRegistry(),
			$log ?? new RemediationLogRepository( new FakeWpdb() ),
			new MockContext()
		);
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private function request( string $id, array $params ): WP_REST_Request {
		$request = new WP_REST_Request( array_merge( [ 'id' => $id ], $params ) );
		return $request;
	}

	private function action( string $id, bool $available, RemediationResult $result ): RemediationAction {
		return new class( $id, $available, $result ) implements RemediationAction {

			public function __construct(
				private string $id,
				private bool $available,
				private RemediationResult $result,
			) {}

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
				return 'Test action';
			}

			public function isAvailable( Context $context, array $params ): bool {
				return $this->available;
			}

			public function apply( Context $context, array $params ): RemediationResult {
				return $this->result;
			}
		};
	}
}
