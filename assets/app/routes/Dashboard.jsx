import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchDashboard } from '../api/dashboard';
import { startScan, getScanStatus } from '../api/scans';
import { ScoreCard } from '../components/ScoreCard';
import { MODULES } from '../api/modules';

const POLL_INTERVAL_MS = 3000;
const TERMINAL_STATUSES = new Set( [ 'complete', 'failed', 'unknown' ] );

const IDLE_SCAN = { runId: null, status: 'idle', progress: 0, total: 0 };

export function Dashboard() {
	const queryClient = useQueryClient();
	const [ scan, setScan ] = useState( IDLE_SCAN );

	const { data, isLoading, isError } = useQuery( {
		queryKey: [ 'dashboard' ],
		queryFn:  fetchDashboard,
	} );

	const mutation = useMutation( {
		mutationFn: () => startScan( 'all' ),
		onSuccess:  ( result ) => {
			setScan( { runId: result.run_id, status: 'running', progress: 0, total: 0 } );
		},
		onError: () => {
			setScan( IDLE_SCAN );
		},
	} );

	// Poll scan status while a runId is active.
	useEffect( () => {
		if ( ! scan.runId ) {
			return;
		}

		const interval = setInterval( async () => {
			try {
				const result = await getScanStatus( scan.runId );

				setScan( ( prev ) => ( {
					...prev,
					status:   result.status,
					progress: result.progress,
					total:    result.total,
				} ) );

				if ( TERMINAL_STATUSES.has( result.status ) ) {
					clearInterval( interval );
					if ( result.status === 'complete' ) {
						queryClient.invalidateQueries( { queryKey: [ 'dashboard' ] } );
					}
					// Brief delay so the user sees "100%" before resetting.
					setTimeout( () => setScan( IDLE_SCAN ), 1200 );
				}
			} catch {
				clearInterval( interval );
				setScan( IDLE_SCAN );
			}
		}, POLL_INTERVAL_MS );

		return () => clearInterval( interval );
	}, [ scan.runId, queryClient ] );

	const isScanning  = scan.status === 'running' || mutation.isPending;
	const scanPct     = scan.total > 0 ? Math.round( ( scan.progress / scan.total ) * 100 ) : 0;

	if ( isLoading ) {
		return (
			<div className="wpsec-dashboard wrap">
				<h1 className="wp-heading-inline">{ __( 'WP Security', 'wp-security' ) }</h1>
				<p>{ __( 'Loading dashboard…', 'wp-security' ) }</p>
			</div>
		);
	}

	if ( isError ) {
		return (
			<div className="wpsec-dashboard wrap">
				<h1 className="wp-heading-inline">{ __( 'WP Security', 'wp-security' ) }</h1>
				<div className="wpsec-dashboard__notice notice notice-error" role="alert">
					<p>{ __( 'Unable to load dashboard data. Please try again.', 'wp-security' ) }</p>
				</div>
			</div>
		);
	}

	const moduleScores = data?.module_scores ?? {};
	const overallScore = data?.overall_score ?? null;

	return (
		<div className="wpsec-dashboard wrap">
			<h1 className="wp-heading-inline">{ __( 'WP Security', 'wp-security' ) }</h1>

			{ /* Scan bar */ }
			<div className="wpsec-dashboard__scan-bar">
				<button
					type="button"
					className="button button-primary wpsec-dashboard__scan-btn"
					onClick={ () => mutation.mutate() }
					disabled={ isScanning }
					aria-busy={ isScanning }
				>
					{ isScanning
						? __( 'Scanning…', 'wp-security' )
						: __( 'Run Full Scan', 'wp-security' ) }
				</button>

				{ isScanning && scan.total > 0 && (
					<span className="wpsec-dashboard__scan-status">
						{ scan.progress } / { scan.total }{ ' ' }
						{ __( 'modules complete', 'wp-security' ) }
					</span>
				) }

				{ mutation.isError && (
					<span className="wpsec-dashboard__scan-error">
						{ __( 'Scan failed to start. Please try again.', 'wp-security' ) }
					</span>
				) }

				{ isScanning && (
					<div
						className="wpsec-dashboard__scan-progress"
						role="progressbar"
						aria-valuenow={ scanPct }
						aria-valuemin={ 0 }
						aria-valuemax={ 100 }
						style={ { '--scan-pct': `${ scanPct }%` } }
					/>
				) }
			</div>

			{ data?.last_scan_at && (
				<p className="wpsec-dashboard__meta">
					{ __( 'Last scan:', 'wp-security' ) }{ ' ' }
					<time dateTime={ data.last_scan_at }>{ data.last_scan_at }</time>
				</p>
			) }

			{ overallScore !== null && (
				<div className="wpsec-dashboard__overall">
					<span className="wpsec-dashboard__overall-label">
						{ __( 'Overall Score:', 'wp-security' ) }
					</span>
					<span className="wpsec-dashboard__overall-score">{ overallScore }</span>
				</div>
			) }

			<div className="wpsec-dashboard__grid">
				{ MODULES.map( ( mod ) => {
					const entry = moduleScores[ mod.id ] ?? null;
					return (
						<ScoreCard
							key={ mod.id }
							label={ mod.label }
							score={ entry?.value ?? null }
							grade={ entry?.grade ?? null }
							path={ mod.path }
						/>
					);
				} ) }
			</div>
		</div>
	);
}
