import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';

export function Server() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'server' ],
		queryFn:  () => fetchModuleFindings( 'server' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Server Health', 'wp-security' ) }</h1>

			{ isLoading && (
				<p>{ __( 'Loading server health findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-server__error">
					{ __( 'Failed to load server health findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-server__empty">
					{ __( 'No findings yet. Run a scan to audit your server configuration.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'Server Health findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
