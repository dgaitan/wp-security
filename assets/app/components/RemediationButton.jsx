import { useState, useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import { applyRemediation } from '../api/remediations';
import { Button } from './Button';

/**
 * Triggers a single RemediationAction through the confirm-gated REST flow.
 *
 * Every remediation action mutates site state, so this is intentionally a
 * two-step control: clicking the button shows the action's confirmation
 * text (never applies anything yet), and only the explicit "Confirm" click
 * sends confirm:true to the server.
 *
 * @param {{
 *   actionId: string,
 *   label: string,
 *   confirmMessage: string,
 *   params?: Record<string, any>,
 *   onApplied?: (result: object) => void,
 * }} props
 */
export function RemediationButton( { actionId, label, confirmMessage, params = {}, onApplied } ) {
	const [ status, setStatus ] = useState( 'idle' ); // idle | confirming | applying | done | error
	const [ error, setError ]   = useState( null );

	const handleConfirmClick = useCallback( () => {
		setStatus( 'confirming' );
		setError( null );
	}, [] );

	const handleCancelClick = useCallback( () => {
		setStatus( 'idle' );
	}, [] );

	const handleApplyClick = useCallback( async () => {
		setStatus( 'applying' );
		setError( null );

		try {
			const result = await applyRemediation( actionId, params );

			if ( result.status === 'success' ) {
				setStatus( 'done' );
				onApplied?.( result );
			} else {
				setStatus( 'error' );
				setError( result.message || __( 'The action did not complete successfully.', 'wp-security' ) );
			}
		} catch {
			setStatus( 'error' );
			setError( __( 'Failed to apply this action. Please try again.', 'wp-security' ) );
		}
	}, [ actionId, params, onApplied ] );

	if ( status === 'confirming' ) {
		return (
			<div className="wpsec-remediation-confirm">
				<p className="wpsec-remediation-confirm__message">{ confirmMessage }</p>
				<div className="wpsec-remediation-confirm__actions">
					<Button variant="primary" onClick={ handleApplyClick }>
						{ __( 'Confirm', 'wp-security' ) }
					</Button>
					<Button variant="secondary" onClick={ handleCancelClick }>
						{ __( 'Cancel', 'wp-security' ) }
					</Button>
				</div>
			</div>
		);
	}

	const isApplying = status === 'applying';
	const isDone     = status === 'done';

	return (
		<div className="wpsec-remediation-button">
			<Button
				variant="secondary"
				onClick={ handleConfirmClick }
				disabled={ isApplying }
				aria-busy={ isApplying ? 'true' : undefined }
			>
				{ isApplying && (
					<span className="wpsec-remediation-button__spinner" aria-hidden="true" />
				) }
				{ isApplying
					? __( 'Applying…', 'wp-security' )
					: isDone
						? __( '✓ Applied', 'wp-security' )
						: label
				}
			</Button>

			{ error && (
				<p className="wpsec-remediation-button__error" role="alert">
					{ error }
				</p>
			) }
		</div>
	);
}
