import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function Performance() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'performance' ],
		queryFn:  () => fetchModuleFindings( 'performance' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Performance', 'wp-security' ) }</h1>
			<ModuleScanButton moduleId="performance" />

			{ isLoading && (
				<p>{ __( 'Loading performance findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-performance__error">
					{ __( 'Failed to load performance findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-performance__empty">
					{ __( 'No findings yet. Run a scan to audit performance.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'Performance findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
