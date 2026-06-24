import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Headers() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Security Headers', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="headers" />
			</header>

			<ModuleFindings 
				moduleId="headers" 
				loadingMessage={ __( 'Loading security header findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load security header findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your HTTP security headers.', 'wp-security' ) } 
				ariaLabel={ __( 'Security Headers findings', 'wp-security' ) } />
		</div>
	);
}
