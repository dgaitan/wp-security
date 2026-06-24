import { __ } from '@wordpress/i18n';

export function Server() {
	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Server Health', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Server Health checks are coming in Sprint 4.', 'wp-security' ) }</p>
		</div>
	);
}
