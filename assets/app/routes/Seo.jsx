
import { __ } from '@wordpress/i18n';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { ModuleFindings } from '../components/ModuleFindings';

export function Seo() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'SEO', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="seo" />
			</header>


			<ModuleFindings 
				moduleId="seo" 
				loadingMessage={ __( 'Loading SEO findings…', 'wp-security' ) } 
				errorMessage={ __( 'Failed to load SEO findings. Please try again.', 'wp-security' ) } 
				emptyMessage={ __( 'No findings yet. Run a scan to audit SEO hygiene.', 'wp-security' ) } 
				ariaLabel={ __( 'SEO findings', 'wp-security' ) } />
		</div>
	);
}
