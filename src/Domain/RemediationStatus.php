<?php

declare( strict_types=1 );

namespace WPSecurity\Domain;

/**
 * The outcome status of a single RemediationAction::apply() run, or of a
 * queued Action Scheduler job before it has executed.
 *
 * QUEUED — enqueued via Action Scheduler, not yet executed.
 * SUCCESS — the action completed and changed site state as intended.
 * FAILED  — the action could not complete (see RemediationResult::message).
 * SKIPPED — the action did not need to run, or no eligible target was found.
 */
enum RemediationStatus: string {
	case QUEUED  = 'queued';
	case SUCCESS = 'success';
	case FAILED  = 'failed';
	case SKIPPED = 'skipped';
}
