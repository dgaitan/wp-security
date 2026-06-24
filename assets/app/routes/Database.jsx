import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Database() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Database', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="database" />
			</header>

			<ModuleFindings 
				moduleId="database" 
				loadingMessage={ __( 'Loading database findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load database findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your database.', 'wp-security' ) } 
				ariaLabel={ __( 'Database findings', 'wp-security' ) } />
		</div>
	);
}
