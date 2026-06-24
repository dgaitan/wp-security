import { __ } from '@wordpress/i18n';

export function CoreIntegrity() {
	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'WordPress Health', 'wp-security' ) }</h1>
			<p className="description">{ __( 'WordPress Health checks are coming in Sprint 5.', 'wp-security' ) }</p>
		</div>
	);
}
