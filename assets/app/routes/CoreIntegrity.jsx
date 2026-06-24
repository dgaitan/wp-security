import { __ } from '@wordpress/i18n';
import { ModuleFindings } from '../components/ModuleFindings';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function CoreIntegrity() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'WordPress Health', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="core_integrity" />
			</header>

			<ModuleFindings 
				moduleId="core_integrity" 
				loadingMessage={ __( 'Loading WordPress health findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load WordPress health findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your WordPress installation.', 'wp-security' ) } 
				ariaLabel={ __( 'WordPress Health findings', 'wp-security' ) } />
		</div>
	);
}
