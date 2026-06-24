import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Dns() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'DNS', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="dns" />
			</header>

			<ModuleFindings 
				moduleId="dns" 
				loadingMessage={ __( 'Loading DNS findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load DNS findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your DNS records.', 'wp-security' ) } 
				ariaLabel={ __( 'DNS findings', 'wp-security' ) } />
		</div>
	);
}
