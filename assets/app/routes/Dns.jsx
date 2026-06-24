import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function Dns() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'dns' ],
		queryFn:  () => fetchModuleFindings( 'dns' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'DNS', 'wp-security' ) }</h1>
			<ModuleScanButton moduleId="dns" />

			{ isLoading && (
				<p>{ __( 'Loading DNS findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-findings__error">
					{ __( 'Failed to load DNS findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-findings__empty">
					{ __( 'No findings yet. Run a scan to audit your DNS records.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'DNS findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
