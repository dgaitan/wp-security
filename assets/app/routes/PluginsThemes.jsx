import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from '../components/FindingItem';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function PluginsThemes() {
	const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', 'plugins_themes' ],
		queryFn:  () => fetchModuleFindings( 'plugins_themes' ),
	} );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Plugins & Themes', 'wp-security' ) }</h1>
			<ModuleScanButton moduleId="plugins_themes" />

			{ isLoading && (
				<p>{ __( 'Loading Plugins & Themes findings…', 'wp-security' ) }</p>
			) }

			{ isError && (
				<p className="wpsec-findings__error">
					{ __( 'Failed to load Plugins & Themes findings. Please try again.', 'wp-security' ) }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-findings__empty">
					{ __( 'No findings yet. Run a scan to audit your plugins and themes.', 'wp-security' ) }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ __( 'Plugins & Themes findings', 'wp-security' ) }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
		</div>
	);
}
