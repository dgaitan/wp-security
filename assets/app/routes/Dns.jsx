import { __ } from '@wordpress/i18n';

export function Dns() {
	return (
		<div className="wrap" style={ { padding: '24px' } }>
			<h1>{ __( 'DNS', 'wp-security' ) }</h1>
			<p className="description">{ __( 'DNS checks are coming in Sprint 5.', 'wp-security' ) }</p>
		</div>
	);
}
