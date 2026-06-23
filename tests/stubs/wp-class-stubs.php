<?php

/**
 * Runtime class stubs for unit tests.
 *
 * These minimal re-implementations of WordPress classes let the persistence and
 * REST layers be exercised without a WordPress installation.  This file is loaded
 * ONLY by the PHPUnit bootstrap — PHPStan resolves the same classes from
 * szepeviktor/phpstan-wordpress, so defining them here would clash with the
 * analyser.  Keeping them out of the shared stubs avoids that conflict.
 *
 * The `wpdb` defined here is a no-op base; tests pass WPSecurity\Tests\Support\
 * FakeWpdb (which extends it) for an in-memory database.
 */

declare( strict_types=1 );

if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {

		public string $prefix = 'wp_';

		public int $insert_id = 0;

		public string $last_error = '';

		public function get_charset_collate(): string {
			return '';
		}

		/**
		 * @param mixed ...$args
		 */
		public function prepare( string $query, ...$args ): string {
			return $query;
		}

		/**
		 * @param array<string, mixed>     $data
		 * @param array<int, string>|string|null $format
		 * @return int|false
		 */
		public function insert( string $table, array $data, $format = null ) {
			return 1;
		}

		/**
		 * @param array<string, mixed> $data
		 * @param array<string, mixed> $where
		 * @param array<int, string>|string|null $format
		 * @param array<int, string>|string|null $where_format
		 * @return int|false
		 */
		public function update( string $table, array $data, array $where, $format = null, $where_format = null ) {
			return 1;
		}

		/**
		 * @return int|false
		 */
		public function query( string $query ) {
			return 1;
		}

		/**
		 * @return array<string, mixed>|object|null
		 */
		public function get_row( string $query, string $output = OBJECT, int $y = 0 ) {
			return null;
		}

		/**
		 * @return array<int, mixed>
		 */
		public function get_results( string $query, string $output = OBJECT ): array {
			return [];
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		/** @var array<string, array<int, string>> */
		private array $errors = [];

		/** @var array<string, mixed> */
		private array $error_data = [];

		/**
		 * @param mixed $data
		 */
		public function __construct( string $code = '', string $message = '', $data = null ) {
			if ( '' !== $code ) {
				$this->errors[ $code ][] = $message;
				if ( null !== $data ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		public function get_error_message(): string {
			$code = (string) array_key_first( $this->errors );
			return $this->errors[ $code ][0] ?? '';
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			$code = (string) array_key_first( $this->error_data );
			return $this->error_data[ $code ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {

		/** @var array<string, mixed> */
		private array $params = [];

		/**
		 * @param array<string, mixed> $params
		 */
		public function __construct( array $params = [] ) {
			$this->params = $params;
		}

		/**
		 * @param mixed $value
		 */
		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		/**
		 * @return mixed
		 */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {

		/** @var mixed */
		private $data;

		private int $status;

		/**
		 * @param mixed $data
		 */
		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}
