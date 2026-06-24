import { __ } from '@wordpress/i18n';

export function Accessibility() {
	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Accessibility', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Accessibility checks are coming in Sprint 7.', 'wp-security' ) }</p>
		</div>
	);
}
