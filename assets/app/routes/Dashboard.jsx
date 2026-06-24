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
			<div className="wrap" style={ { padding: '24px' } }>
				<h1 className="wp-heading-inline">{ __( 'WP Security', 'wp-security' ) }</h1>
				<p>{ __( 'Loading dashboard…', 'wp-security' ) }</p>
			</div>
		);
	}

	if ( isError ) {
		return (
			<div className="wrap" style={ { padding: '24px' } }>
				<h1 className="wp-heading-inline">{ __( 'WP Security', 'wp-security' ) }</h1>
				<div
					className="notice notice-error"
					role="alert"
					style={ { marginTop: '16px' } }
				>
					<p>{ __( 'Unable to load dashboard data. Please try again.', 'wp-security' ) }</p>
				</div>
			</div>
		);
	}

	const moduleScores = data?.module_scores ?? {};
	const overallScore = data?.overall_score ?? null;

	return (
		<div className="wrap" style={ { padding: '24px' } }>
			<h1 className="wp-heading-inline">{ __( 'WP Security', 'wp-security' ) }</h1>

			{ data?.last_scan_at && (
				<p style={ { color: '#646970', fontSize: '13px', marginTop: '8px' } }>
					{ __( 'Last scan:', 'wp-security' ) }{ ' ' }
					<time dateTime={ data.last_scan_at }>{ data.last_scan_at }</time>
				</p>
			) }

			{ overallScore !== null && (
				<div
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						gap: '8px',
						marginBottom: '24px',
					} }
				>
					<span style={ { fontWeight: 600 } }>{ __( 'Overall Score:', 'wp-security' ) }</span>
					<span style={ { fontSize: '24px', fontWeight: 700 } }>{ overallScore }</span>
				</div>
			) }

			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))',
					gap: '16px',
					marginTop: '24px',
				} }
			>
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
