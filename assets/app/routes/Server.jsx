import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Server() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Server Health', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="server" />
			</header>

			<ModuleFindings 
				moduleId="server" 
				loadingMessage={ __( 'Loading server health findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load server health findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your server configuration.', 'wp-security' ) } 
				ariaLabel={ __( 'Server Health findings', 'wp-security' ) } />
		</div>
	);
}
