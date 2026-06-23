<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * An immutable value object representing the result of a single Check run.
 *
 * Finding is the universal result currency of the entire plugin: the scoring
 * engine, the REST serialiser, and the React UI all consume Findings without
 * knowing anything about the check that produced them.
 *
 * Because all properties are readonly, a Finding is safe to pass across
 * layers and cache without risk of mutation.
 */
final class Finding {

    /**
     * @param string  $checkId        Stable identifier matching Check::id().
     * @param Status  $status         Outcome of the check.
     * @param Severity $severity      How serious the finding is.
     * @param string  $title          Short headline shown in the UI.
     * @param string  $description    Plain-language explanation of what was found.
     * @param string  $recommendation Concrete action the site owner should take.
     * @param array<string, mixed> $evidence Structured detail surfaced in the evidence table.
     * @param string|null $docsUrl    Link to further documentation.
     */
    public function __construct(
        public readonly string   $checkId,
        public readonly Status   $status,
        public readonly Severity $severity,
        public readonly string   $title,
        public readonly string   $description,
        public readonly string   $recommendation,
        public readonly array    $evidence = [],
        public readonly ?string  $docsUrl  = null,
    ) {}

    /**
     * Whether this finding contributes a penalty to the module score.
     */
    public function affectsScore(): bool {
        return $this->status->affectsScore();
    }

    /**
     * Score penalty for this finding (0 if it does not affect the score).
     */
    public function penalty(): int {
        return $this->affectsScore() ? $this->severity->penalty() : 0;
    }

    /**
     * Serialize to a plain array for REST responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'check_id'       => $this->checkId,
            'status'         => $this->status->value,
            'severity'       => $this->severity->value,
            'title'          => $this->title,
            'description'    => $this->description,
            'recommendation' => $this->recommendation,
            'evidence'       => $this->evidence,
            'docs_url'       => $this->docsUrl,
        ];
    }

    /**
     * Factory: create a PASS finding with minimal boilerplate.
     */
    public static function pass( string $checkId, string $title, string $description = '' ): self {
        return new self(
            checkId:        $checkId,
            status:         Status::PASS,
            severity:       Severity::INFO,
            title:          $title,
            description:    $description,
            recommendation: '',
        );
    }

    /**
     * Factory: create a SKIPPED finding when a check cannot run.
     */
    public static function skipped( string $checkId, string $title, string $reason ): self {
        return new self(
            checkId:        $checkId,
            status:         Status::SKIPPED,
            severity:       Severity::INFO,
            title:          $title,
            description:    $reason,
            recommendation: 'No action needed; the check was skipped.',
        );
    }
}
