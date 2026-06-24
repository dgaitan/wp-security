
import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function PluginsThemes() {

	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Plugins & Themes', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="plugins_themes" />
			</header>

			<ModuleFindings 
				moduleId="plugins_themes" 
				loadingMessage={ __( 'Loading plugins & themes findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load plugins & themes findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your plugins & themes.', 'wp-security' ) } 
				ariaLabel={ __( 'Plugins & Themes findings', 'wp-security' ) } />
		</div>
	);
}
