import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';

const STATUS_ICONS = {
	pass:    '✓',
	fail:    '✗',
	warn:    '!',
	info:    'i',
	skipped: '–',
};

function FindingItem( { finding } ) {
	return (
		<li className="wpsec-finding">
			<div className="wpsec-finding__header">
				<span
					className="wpsec-finding__status-icon"
					data-status={ finding.status }
					aria-hidden="true"
				>
					{ STATUS_ICONS[ finding.status ] ?? '?' }
				</span>
				<span
					className="wpsec-finding__badge"
					data-severity={ finding.severity }
				>
					{ finding.severity }
				</span>
				<span className="wpsec-finding__title">{ finding.title }</span>
			</div>
			{ finding.description && (
				<p className="wpsec-finding__description">{ finding.description }</p>
			) }
			{ finding.recommendation && (
				<p className="wpsec-finding__recommendation">{ finding.recommendation }</p>
			) }
		</li>
	);
}

export function CoreIntegrity() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'core_integrity' ],
		queryFn:  () => fetchModuleFindings( 'core_integrity' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'WordPress Health', 'wp-security' ) }</h1>

			{ isLoading && (
				<p>{ __( 'Loading WordPress health findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-findings__error">
					{ __( 'Failed to load WordPress health findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-findings__empty">
					{ __( 'No findings yet. Run a scan to audit your WordPress installation.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'WordPress Health findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
