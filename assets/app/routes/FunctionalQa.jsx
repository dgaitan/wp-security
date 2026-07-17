import { __ } from '@wordpress/i18n';
import { ModuleFindings } from '../components/ModuleFindings';
import { ModuleScanButton } from '../components/ModuleScanButton';
import { RemediationButton } from '../components/RemediationButton';

export function FunctionalQa() {
	return (
		<div className="wpsec-section wrap">
			<header className="wpsec-page__header">
				<h1>{ __( 'Functional QA', 'wp-security' ) }</h1>
				<ModuleScanButton moduleId="functional_qa" />
			</header>

			<div className="wpsec-functional-qa__contact-form-test">
				<h2>{ __( 'Contact Form Test', 'wp-security' ) }</h2>
				<p className="description">
					{ __(
						'Submits a real test entry through your site’s detected contact form plugin (Contact Form 7, Gravity Forms), including sending a real test email. Never run automatically — admin-triggered only.',
						'wp-security'
					) }
				</p>
				<RemediationButton
					actionId="functional_qa.contact_form_test"
					label={ __( 'Test Contact Form Submission', 'wp-security' ) }
					confirmMessage={ __(
						'This will submit a real test entry through your detected contact form plugin, including sending a real test email through its configured mail template. Continue?',
						'wp-security'
					) }
				/>
			</div>

			<ModuleFindings
				moduleId="functional_qa"
				loadingMessage={ __( 'Loading functional QA findings…', 'wp-security' ) }
				errorMessage={ __( 'Failed to load functional QA findings. Please try again.', 'wp-security' ) }
				emptyMessage={ __( 'No findings yet. Run a scan to smoke-test your site.', 'wp-security' ) }
				ariaLabel={ __( 'Functional QA findings', 'wp-security' ) } />
		</div>
	);
}
