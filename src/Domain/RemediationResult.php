<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * An immutable value object representing the outcome of one
 * RemediationAction::apply() invocation.
 *
 * Deliberately separate from Finding/Status: "an update was applied" is an
 * audit-log entry, not a scored check outcome, and routing it through
 * Finding/Status would corrupt ScoringService, which treats every WARN/FAIL
 * Status as a score penalty.
 */
final class RemediationResult {

	/**
	 * @param RemediationStatus $status
	 * @param string            $message     Human-readable outcome description.
	 * @param array<string, mixed>|null $beforeState State captured before apply(), e.g. ['version' => '6.4.0'].
	 * @param array<string, mixed>|null $afterState  State captured after apply(), e.g. ['version' => '6.5.0'].
	 */
	public function __construct(
		public readonly RemediationStatus $status,
		public readonly string $message,
		public readonly ?array $beforeState = null,
		public readonly ?array $afterState = null,
	) {}

	public static function success( string $message, ?array $beforeState = null, ?array $afterState = null ): self {
		return new self( RemediationStatus::SUCCESS, $message, $beforeState, $afterState );
	}

	public static function failure( string $message, ?array $beforeState = null ): self {
		return new self( RemediationStatus::FAILED, $message, $beforeState, null );
	}

	public static function skipped( string $message ): self {
		return new self( RemediationStatus::SKIPPED, $message, null, null );
	}

	public static function queued( string $message ): self {
		return new self( RemediationStatus::QUEUED, $message, null, null );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'status'       => $this->status->value,
			'message'      => $this->message,
			'before_state' => $this->beforeState,
			'after_state'  => $this->afterState,
		];
	}
}
