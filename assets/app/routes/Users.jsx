import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Users() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Users', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="users" />
			</header>

			<ModuleFindings 
				moduleId="users" 
				loadingMessage={ __( 'Loading Users findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load Users findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit your user accounts.', 'wp-security' ) } 
				ariaLabel={ __( 'Users findings', 'wp-security' ) } />
		</div>
	);
}
