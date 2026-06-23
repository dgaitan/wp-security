<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * The computed score for a module or for the whole site.
 *
 * Score is a value object: it carries a numeric value (0–100) and exposes
 * helpers for grade mapping and display.  Produced by ScoringService; never
 * constructed by checks.
 */
final class Score {

    public function __construct(
        public readonly int    $value,
        public readonly string $moduleId,
    ) {
        if ( $this->value < 0 || $this->value > 100 ) {
            throw new \InvalidArgumentException(
                "Score value must be between 0 and 100; got {$this->value}."
            );
        }
    }

    /**
     * Letter grade matching the spec:
     *   A ≥ 90 · B ≥ 80 · C ≥ 70 · D ≥ 60 · F < 60
     */
    public function grade(): string {
        return match ( true ) {
            $this->value >= 90 => 'A',
            $this->value >= 80 => 'B',
            $this->value >= 70 => 'C',
            $this->value >= 60 => 'D',
            default            => 'F',
        };
    }

    /**
     * CSS class name useful for colour-coding in the React UI.
     */
    public function gradeClass(): string {
        return 'grade-' . strtolower( $this->grade() );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'module_id' => $this->moduleId,
            'value'     => $this->value,
            'grade'     => $this->grade(),
        ];
    }
}
