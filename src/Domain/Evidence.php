<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * A builder/normalizer for the structured detail attached to a Finding.
 *
 * Check authors build evidence explicitly:
 *
 *     $evidence = new Evidence();
 *     $evidence->add( 'max_age', $maxAge );
 *
 * Each call to add() inspects the given value and standardizes it into one
 * of a small set of display types (empty/boolean/scalar/list/table/group)
 * plus a humanized label, so the React admin UI can render by type instead
 * of guessing a raw PHP array's shape at display time. This is the single
 * place that decides how evidence looks — Finding::toArray(),
 * FindingRepository::mapRow(), and FindingItem.jsx all consume its output
 * rather than re-deriving it.
 */
final class Evidence implements \JsonSerializable {

	private const ACRONYMS = [
		'php',
		'url',
		'sql',
		'id',
		'cve',
		'csp',
		'sri',
		'hsts',
		'tls',
		'ssl',
		'ttfb',
		'wp',
		'http',
		'https',
		'cdn',
		'xml',
		'rss',
	];

	private const SMALL_WORDS = [ 'in', 'of', 'and', 'or', 'the', 'to', 'a', 'an' ];

	private const UNIT_DISPLAY = [
		'ms'    => 'ms',
		'mb'    => 'MB',
		'kb'    => 'KB',
		'bytes' => 'bytes',
		'count' => 'count',
	];

	/** @var array<int, array{key:string,label:string,type:string,value:mixed}> */
	private array $items = [];

	/**
	 * The original, pre-normalization value passed to add() for each key —
	 * kept separate from $items because normalization is lossy by design
	 * (e.g. a table's rows are reshaped into {columns, rows} for display,
	 * and an empty array becomes null). get() returns this raw form so
	 * Check/test code can read back exactly what was written.
	 *
	 * @var array<string, mixed>
	 */
	private array $rawValues = [];

	/**
	 * Adds one evidence entry, inspecting $value to decide its display type.
	 */
	public function add( string $key, mixed $value, ?string $label = null ): self {
		$this->items[]           = self::buildItem( $key, $value, $label );
		$this->rawValues[ $key ] = $value;
		return $this;
	}

	public function isEmpty(): bool {
		return [] === $this->items;
	}

	public function has( string $key ): bool {
		return array_key_exists( $key, $this->rawValues );
	}

	/**
	 * Raw value lookup by key — mainly useful for tests and internal Check logic.
	 */
	public function get( string $key ): mixed {
		return $this->rawValues[ $key ] ?? null;
	}

	/**
	 * @return array<int, array{key:string,label:string,type:string,value:mixed}>
	 */
	public function toArray(): array {
		return $this->items;
	}

	/**
	 * @return array<int, array{key:string,label:string,type:string,value:mixed}>
	 */
	public function jsonSerialize(): array {
		return $this->items;
	}

	/**
	 * Single normalization entry point. Accepts an Evidence instance
	 * (returned as-is), an already-normalized item list (round-tripped from
	 * storage or from another Evidence's toArray()), a raw associative array
	 * (legacy shape, or a REST-submitted evidence payload), or a bare
	 * scalar — and always returns a valid Evidence.
	 */
	public static function from( mixed $raw ): self {
		if ( $raw instanceof self ) {
			return $raw;
		}

		if ( is_array( $raw ) && self::looksNormalized( $raw ) ) {
			$evidence = new self();
			foreach ( $raw as $item ) {
				if ( ! is_array( $item ) || ! isset( $item['key'] ) ) {
					continue;
				}
				$key               = (string) $item['key'];
				$value             = $item['value'] ?? null;
				$evidence->items[] = [
					'key'   => $key,
					'label' => isset( $item['label'] ) ? (string) $item['label'] : self::humanize( $key ),
					'type'  => isset( $item['type'] ) ? (string) $item['type'] : self::detectType( $value ),
					'value' => $value,
				];
				// The true pre-normalization value isn't recoverable from an
				// already-normalized item, so get() falls back to the
				// normalized value here — acceptable since this path is only
				// hit when reconstructing evidence for display, not by Check
				// authors calling add() directly.
				$evidence->rawValues[ $key ] = $value;
			}
			return $evidence;
		}

		if ( is_array( $raw ) ) {
			$evidence = new self();
			foreach ( $raw as $key => $value ) {
				$evidence->add( (string) $key, $value );
			}
			return $evidence;
		}

		if ( null === $raw ) {
			return new self();
		}

		return ( new self() )->add( 'value', $raw );
	}

	/**
	 * A raw evidence array is "normalized" when it's a list of arrays that
	 * each already carry a 'key' entry — the shape produced by toArray()/
	 * jsonSerialize(). A Check-authored raw array is keyed by string names
	 * (e.g. 'missing_sri'), so array_is_list() is false for it.
	 *
	 * @param array<array-key, mixed> $raw
	 */
	private static function looksNormalized( array $raw ): bool {
		if ( ! array_is_list( $raw ) ) {
			return false;
		}

		if ( [] === $raw ) {
			return false;
		}

		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['key'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array{key:string,label:string,type:string,value:mixed}
	 */
	private static function buildItem( string $key, mixed $value, ?string $label ): array {
		if ( is_object( $value ) ) {
			$decoded = json_decode( (string) wp_json_encode( $value ), true );
			$value   = is_array( $decoded ) ? $decoded : (array) $value;
		}

		$type = self::detectType( $value );

		return [
			'key'   => $key,
			'label' => $label ?? self::humanize( $key ),
			'type'  => $type,
			'value' => self::normalizeValue( $value, $type ),
		];
	}

	private static function detectType( mixed $value ): string {
		if ( null === $value ) {
			return 'empty';
		}

		if ( is_bool( $value ) ) {
			return 'boolean';
		}

		if ( is_scalar( $value ) ) {
			return 'scalar';
		}

		if ( ! is_array( $value ) ) {
			return 'empty';
		}

		if ( [] === $value ) {
			return 'empty';
		}

		if ( array_is_list( $value ) ) {
			$allArrays  = true;
			$allScalars = true;

			foreach ( $value as $element ) {
				if ( is_array( $element ) ) {
					$allScalars = false;
				} else {
					$allArrays = false;
				}
			}

			if ( $allArrays ) {
				return 'table';
			}

			if ( $allScalars ) {
				return 'list';
			}

			// Mixed list of scalars and arrays — still displayable as a list,
			// with any array elements stringified defensively at render time.
			return 'list';
		}

		return 'group';
	}

	private static function normalizeValue( mixed $value, string $type ): mixed {
		return match ( $type ) {
			'empty'   => null,
			'boolean' => (bool) $value,
			'scalar'  => $value,
			'list'    => self::normalizeList( is_array( $value ) ? $value : [] ),
			'table'   => self::normalizeTable( is_array( $value ) ? $value : [] ),
			'group'   => self::normalizeGroup( is_array( $value ) ? $value : [] ),
			default   => $value,
		};
	}

	/**
	 * @param array<int, mixed> $value
	 * @return array<int, mixed>
	 */
	private static function normalizeList( array $value ): array {
		return array_values(
			array_map(
				static function ( mixed $element ): mixed {
					if ( is_scalar( $element ) || null === $element ) {
						return $element;
					}
					return wp_json_encode( $element );
				},
				$value
			)
		);
	}

	/**
	 * @param array<int, array<array-key, mixed>> $rows
	 * @return array{columns: array<int, array{key:string,label:string}>, rows: array<int, array<string, mixed>>}
	 */
	private static function normalizeTable( array $rows ): array {
		$columnKeys = [];
		foreach ( $rows as $row ) {
			foreach ( array_keys( $row ) as $columnKey ) {
				$columnKey = (string) $columnKey;
				if ( ! in_array( $columnKey, $columnKeys, true ) ) {
					$columnKeys[] = $columnKey;
				}
			}
		}

		$columns = array_map(
			static fn ( string $columnKey ): array => [
				'key'   => $columnKey,
				'label' => self::humanize( $columnKey ),
			],
			$columnKeys
		);

		$normalizedRows = array_map(
			static function ( array $row ) use ( $columnKeys ): array {
				$out = [];
				foreach ( $columnKeys as $columnKey ) {
					$cell              = $row[ $columnKey ] ?? null;
					$out[ $columnKey ] = is_scalar( $cell ) || null === $cell ? $cell : wp_json_encode( $cell );
				}
				return $out;
			},
			$rows
		);

		return [
			'columns' => $columns,
			'rows'    => array_values( $normalizedRows ),
		];
	}

	/**
	 * @param array<array-key, mixed> $value
	 * @return array<int, array{key:string,label:string,type:string,value:mixed}>
	 */
	private static function normalizeGroup( array $value ): array {
		$items = [];
		foreach ( $value as $key => $nestedValue ) {
			$items[] = self::buildItem( (string) $key, $nestedValue, null );
		}
		return $items;
	}

	/**
	 * Converts a snake_case key into an acronym-aware, unit-suffix-aware label.
	 *
	 * Examples: php_in_uploads -> "PHP in Uploads", ttfb_ms -> "TTFB (ms)",
	 * autoloaded_size_bytes -> "Autoloaded Size (bytes)".
	 */
	private static function humanize( string $key ): string {
		$words = array_values( array_filter( explode( '_', $key ), static fn ( string $word ): bool => '' !== $word ) );

		if ( [] === $words ) {
			return $key;
		}

		$unitSuffix = null;
		if ( count( $words ) > 1 ) {
			$lastLower = strtolower( (string) end( $words ) );
			if ( isset( self::UNIT_DISPLAY[ $lastLower ] ) ) {
				$unitSuffix = self::UNIT_DISPLAY[ $lastLower ];
				array_pop( $words );
			}
		}

		$formatted = implode(
			' ',
			array_map(
				static function ( string $word ): string {
					$lower = strtolower( $word );
					if ( in_array( $lower, self::ACRONYMS, true ) ) {
						return strtoupper( $word );
					}
					if ( in_array( $lower, self::SMALL_WORDS, true ) ) {
						return $lower;
					}
					return ucfirst( $lower );
				},
				$words
			)
		);

		$formatted = ucfirst( $formatted );

		return null !== $unitSuffix ? sprintf( '%s (%s)', $formatted, $unitSuffix ) : $formatted;
	}
}
