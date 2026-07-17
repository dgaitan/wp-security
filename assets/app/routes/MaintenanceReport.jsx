import { __ } from '@wordpress/i18n';
import { useQuery } from '@tanstack/react-query';
import { fetchMaintenanceReport } from '../api/maintenanceReport';
import { buildMarkdown, downloadMarkdown } from '../utils/exportMarkdown';
import { RemediationLogTable } from '../components/RemediationLogTable';
import { BacklogList } from '../components/BacklogList';
import { MaintenanceNotesForm } from '../components/MaintenanceNotesForm';
import { Button } from '../components/Button';

export function MaintenanceReport() {
	const { data, isLoading, isError } = useQuery( {
		queryKey: [ 'maintenance-report' ],
		queryFn:  fetchMaintenanceReport,
	} );

	function handleExport() {
		const markdown = buildMarkdown( data );
		const date = new Date().toISOString().slice( 0, 10 );
		downloadMarkdown( `wp-security-maintenance-report-${ date }.md`, markdown );
	}

	if ( isLoading ) {
		return (
			<div className="wpsec-section wrap">
				<p>{ __( 'Loading maintenance report…', 'wp-security' ) }</p>
			</div>
		);
	}

	if ( isError ) {
		return (
			<div className="wpsec-section wrap">
				<div className="notice notice-error">
					<p>{ __( 'Could not load the maintenance report. Please reload the page.', 'wp-security' ) }</p>
				</div>
			</div>
		);
	}

	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Maintenance Report', 'wp-security' ) }</h1>
				<Button variant="secondary" onClick={ handleExport }>
					{ __( 'Export as Markdown', 'wp-security' ) }
				</Button>
			</header>

			<section className="wpsec-maintenance-report__section">
				<h2>{ __( 'Updates Applied', 'wp-security' ) }</h2>
				<RemediationLogTable entries={ data?.remediations } />
			</section>

			<section className="wpsec-maintenance-report__section">
				<h2>{ __( 'Open Findings Backlog', 'wp-security' ) }</h2>
				<BacklogList findings={ data?.backlog } />
			</section>

			<section className="wpsec-maintenance-report__section">
				<h2>{ __( 'Notes', 'wp-security' ) }</h2>
				<MaintenanceNotesForm notes={ data?.notes } />
			</section>
		</div>
	);
}
