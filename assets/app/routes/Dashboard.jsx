import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchDashboard } from '../api/dashboard';
import { ScoreCard } from '../components/ScoreCard';
import { MODULES } from '../api/modules';

export function Dashboard() {
	const { data, isLoading, isError } = useQuery( {
		queryKey: [ 'dashboard' ],
		queryFn: fetchDashboard,
	} );

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
