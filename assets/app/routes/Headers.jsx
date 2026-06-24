import { __ } from '@wordpress/i18n';

export function Headers() {
	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Security Headers', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Security Headers checks are coming in Sprint 5.', 'wp-security' ) }</p>
		</div>
	);
}
