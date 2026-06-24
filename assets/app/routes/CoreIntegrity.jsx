import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function CoreIntegrity() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'core_integrity' ],
		queryFn:  () => fetchModuleFindings( 'core_integrity' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'WordPress Health', 'wp-security' ) }</h1>
			<ModuleScanButton moduleId="core_integrity" />

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
