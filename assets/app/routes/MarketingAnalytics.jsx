import { __ } from '@wordpress/i18n';
import { ModuleFindings } from '../components/ModuleFindings';
import { ModuleScanButton } from '../components/ModuleScanButton';

export function MarketingAnalytics() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Marketing & Analytics', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="marketing_analytics" />
			</header>

			<ModuleFindings
				moduleId="marketing_analytics"
				loadingMessage={ __( 'Loading marketing & analytics findings…', 'wp-security' ) }
				errorMessage={ __( 'Failed to load marketing & analytics findings. Please try again.', 'wp-security' ) }
				emptyMessage={ __( 'No findings yet. Run a scan to verify tracking and consent tooling.', 'wp-security' ) }
				ariaLabel={ __( 'Marketing & Analytics findings', 'wp-security' ) } />
		</div>
	);
}
