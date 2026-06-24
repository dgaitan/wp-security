
import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Performance() {

	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Performance', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="performance" />
			</header>

			<ModuleFindings 
				moduleId="performance" 
				loadingMessage={ __( 'Loading performance findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load performance findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit performance.', 'wp-security' ) } 
				ariaLabel={ __( 'Performance findings', 'wp-security' ) } />
		</div>
	);
}
