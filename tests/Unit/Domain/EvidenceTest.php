<?php
/**
 * Unit tests for the Evidence builder/normalizer.
 *
 * Feature: Evidence value object — Core domain
 *   Background:
 *     Given the WP Security plugin is installed
 *     And the autoloader is registered
 *
 *   Scenario: A snake_case key is humanized with acronym awareness
 *     Given the key "php_in_uploads"
 *     When add() is called
 *     Then the label is "PHP in Uploads"
 *
 *   Scenario: A unit-suffixed key gets a parenthetical unit
 *     Given the key "ttfb_ms" with a numeric value
 *     When add() is called
 *     Then the label is "TTFB (ms)" and the type is "scalar"
 *
 *   Scenario: A null or empty-array value is typed as empty
 *     Given a null value and an empty-array value
 *     When add() is called for each
 *     Then both entries have type "empty" and value null
 *
 *   Scenario: A boolean value is typed as boolean
 *     Given a boolean value
 *     When add() is called
 *     Then the type is "boolean" and the value is preserved
 *
 *   Scenario: A list of scalars is typed as a list
 *     Given a list of plugin slugs
 *     When add() is called
 *     Then the type is "list" and the value is the same list
 *
 *   Scenario: A list of records is typed as a table with humanized columns
 *     Given a list of associative arrays sharing the keys id/fixed_in
 *     When add() is called
 *     Then the type is "table" and columns carry humanized labels for each key
 *
 *   Scenario: A nested associative array is typed as a group
 *     Given an associative array value that is not a list
 *     When add() is called
 *     Then the type is "group" and the value is a recursively-normalized item list
 *
 *   Scenario: Evidence::from() accepts a raw legacy assoc array
 *     Given a plain array such as ['max_age' => 100]
 *     When Evidence::from() is called
 *     Then it returns an Evidence with one inferred entry
 *
 *   Scenario: Evidence::from() accepts an already-normalized item list
 *     Given the array form produced by toArray()
 *     When Evidence::from() is called
 *     Then it reconstructs the same entries without re-inferring types
 *
 *   Scenario: Evidence::from() passes an Evidence instance through unchanged
 *     Given an existing Evidence instance
 *     When Evidence::from() is called with it
 *     Then the exact same instance is returned
 *
 * @package WPSecurity\Tests
 */

declare( strict_types=1 );

namespace WPSecurity\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPSecurity\Domain\Evidence;

final class EvidenceTest extends TestCase {

	public function test_humanizes_acronym_key(): void {
		$evidence = ( new Evidence() )->add( 'php_in_uploads', [ 'a.php' ] );

		$this->assertSame( 'PHP in Uploads', $evidence->toArray()[0]['label'] );
	}

	public function test_humanizes_unit_suffixed_key(): void {
		$evidence = ( new Evidence() )->add( 'ttfb_ms', 187 );
		$item     = $evidence->toArray()[0];

		$this->assertSame( 'TTFB (ms)', $item['label'] );
		$this->assertSame( 'scalar', $item['type'] );
		$this->assertSame( 187, $item['value'] );
	}

	public function test_null_and_empty_array_are_typed_empty(): void {
		$evidence = ( new Evidence() )
			->add( 'null_value', null )
			->add( 'empty_value', [] );

		$items = $evidence->toArray();

		$this->assertSame( 'empty', $items[0]['type'] );
		$this->assertNull( $items[0]['value'] );
		$this->assertSame( 'empty', $items[1]['type'] );
		$this->assertNull( $items[1]['value'] );
	}

	public function test_boolean_value_is_typed_boolean(): void {
		$evidence = ( new Evidence() )->add( 'wp_debug', true );
		$item     = $evidence->toArray()[0];

		$this->assertSame( 'boolean', $item['type'] );
		$this->assertTrue( $item['value'] );
	}

	public function test_list_of_scalars_is_typed_list(): void {
		$slugs    = [ 'woocommerce/woocommerce.php', 'wp-all-export/wp-all-export.php' ];
		$evidence = ( new Evidence() )->add( 'plugins_needing_update', $slugs );
		$item     = $evidence->toArray()[0];

		$this->assertSame( 'list', $item['type'] );
		$this->assertSame( $slugs, $item['value'] );
		$this->assertSame( 'Plugins Needing Update', $item['label'] );
	}

	public function test_list_of_records_is_typed_table_with_humanized_columns(): void {
		$rows = [
			[
				'id'       => 1,
				'fixed_in' => '6.4.1',
			],
			[
				'id'       => 2,
				'fixed_in' => '2.1.0',
			],
		];

		$evidence = ( new Evidence() )->add( 'advisories', $rows );
		$item     = $evidence->toArray()[0];

		$this->assertSame( 'table', $item['type'] );
		$this->assertSame(
			[
				[
					'key'   => 'id',
					'label' => 'ID',
				],
				[
					'key'   => 'fixed_in',
					'label' => 'Fixed in',
				],
			],
			$item['value']['columns']
		);
		$this->assertSame( $rows, $item['value']['rows'] );
	}

	public function test_nested_assoc_array_is_typed_group(): void {
		$evidence = ( new Evidence() )->add(
			'certificate',
			[
				'subject_cn'  => 'example.test',
				'self_signed' => false,
			]
		);
		$item     = $evidence->toArray()[0];

		$this->assertSame( 'group', $item['type'] );
		$this->assertCount( 2, $item['value'] );
		$this->assertSame( 'subject_cn', $item['value'][0]['key'] );
		$this->assertSame( 'scalar', $item['value'][0]['type'] );
		$this->assertSame( 'self_signed', $item['value'][1]['key'] );
		$this->assertSame( 'boolean', $item['value'][1]['type'] );
	}

	public function test_from_infers_types_from_raw_legacy_array(): void {
		$evidence = Evidence::from( [ 'max_age' => 100 ] );
		$item     = $evidence->toArray()[0];

		$this->assertSame( 'max_age', $item['key'] );
		$this->assertSame( 'scalar', $item['type'] );
		$this->assertSame( 100, $item['value'] );
	}

	public function test_from_reconstructs_already_normalized_item_list(): void {
		$original = ( new Evidence() )->add( 'max_age', 100 );

		$rebuilt = Evidence::from( $original->toArray() );

		$this->assertSame( $original->toArray(), $rebuilt->toArray() );
	}

	public function test_from_passes_through_an_evidence_instance(): void {
		$original = ( new Evidence() )->add( 'max_age', 100 );

		$this->assertSame( $original, Evidence::from( $original ) );
	}

	public function test_from_null_returns_empty_evidence(): void {
		$this->assertTrue( Evidence::from( null )->isEmpty() );
	}

	public function test_get_and_has_look_up_raw_values(): void {
		$evidence = ( new Evidence() )->add( 'max_age', 100 );

		$this->assertTrue( $evidence->has( 'max_age' ) );
		$this->assertFalse( $evidence->has( 'missing' ) );
		$this->assertSame( 100, $evidence->get( 'max_age' ) );
		$this->assertNull( $evidence->get( 'missing' ) );
	}
}
