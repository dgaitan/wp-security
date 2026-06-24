import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function Seo() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'seo' ],
		queryFn:  () => fetchModuleFindings( 'seo' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'SEO', 'wp-security' ) }</h1>
			<ModuleScanButton moduleId="seo" />

			{ isLoading && (
				<p>{ __( 'Loading SEO findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-seo__error">
					{ __( 'Failed to load SEO findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-seo__empty">
					{ __( 'No findings yet. Run a scan to audit SEO hygiene.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'SEO findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
