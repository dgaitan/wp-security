import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function Headers() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'headers' ],
		queryFn:  () => fetchModuleFindings( 'headers' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Security Headers', 'wp-security' ) }</h1>
			<ModuleScanButton moduleId="headers" />

			{ isLoading && (
				<p>{ __( 'Loading security header findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-findings__error">
					{ __( 'Failed to load security header findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-findings__empty">
					{ __( 'No findings yet. Run a scan to audit your HTTP security headers.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'Security Headers findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
