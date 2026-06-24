import { useState, useEffect, useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import { useQueryClient } from '@tanstack/react-query';
import { startScan, getScanStatus } from '../api/scans';
import { Button } from './Button';

const POLL_INTERVAL_MS = 3000;
const DONE_RESET_MS    = 3000;

/**
 * "Run Scan" button for a single module page.
 *
 * On completion the findings query for this module (and the dashboard) are
 * invalidated so React Query re-fetches the latest data automatically.
 *
 * @param {{ moduleId: string }} props
 */
export function ModuleScanButton( { moduleId } ) {
	const [ status, setStatus ] = useState( 'idle' ); // idle | scanning | done | error
	const [ runId, setRunId ]   = useState( null );
	const [ error, setError ]   = useState( null );

	const queryClient = useQueryClient();

	// Poll the running scan until it finishes.
	useEffect( () => {
		if ( status !== 'scanning' || ! runId ) return;

		const interval = setInterval( async () => {
			try {
				const result = await getScanStatus( runId );

				if ( result.status === 'complete' ) {
					// Invalidate findings for this module and the dashboard scores.
					queryClient.invalidateQueries( {
						queryKey: [ 'module-findings', moduleId ],
					} );
					queryClient.invalidateQueries( { queryKey: [ 'dashboard' ] } );

					setStatus( 'done' );
					setRunId( null );

					setTimeout( () => setStatus( 'idle' ), DONE_RESET_MS );
				} else if ( result.status === 'failed' ) {
					setStatus( 'error' );
					setRunId( null );
					setError( __( 'Scan failed. Please try again.', 'wp-security' ) );
				}
			} catch {
				setStatus( 'error' );
				setRunId( null );
				setError( __( 'Failed to check scan status. Please try again.', 'wp-security' ) );
			}
		}, POLL_INTERVAL_MS );

		return () => clearInterval( interval );
	}, [ status, runId, moduleId, queryClient ] );

	const handleClick = useCallback( async () => {
		if ( status === 'scanning' ) return;

		setStatus( 'scanning' );
		setRunId( null );
		setError( null );

		try {
			const result = await startScan( moduleId );
			setRunId( result.run_id );
		} catch {
			setStatus( 'error' );
			setError( __( 'Failed to start scan. Please try again.', 'wp-security' ) );
		}
	}, [ status, moduleId ] );

	const isScanning = status === 'scanning';
	const isDone     = status === 'done';

	return (
		<div className="wpsec-scan-bar">
			<Button
				onClick={ handleClick }
				disabled={ isScanning }
				aria-busy={ isScanning ? 'true' : undefined }
			>
				{ isScanning && (
					<span className="wpsec-scan-bar__spinner" aria-hidden="true" />
				) }
				{ isScanning
					? __( 'Scanning…', 'wp-security' )
					: isDone
						? __( '✓ Scan Complete', 'wp-security' )
						: __( 'Run Scan', 'wp-security' )
				}
			</Button>

			{ error && (
				<p className="wpsec-scan-bar__error" role="alert">
					{ error }
				</p>
			) }
		</div>
	);
}
