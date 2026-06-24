import { __ } from '@wordpress/i18n';

export function Performance() {
	return (
		<div className="wrap" style={ { padding: '24px' } }>
			<h1>{ __( 'Performance', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Performance checks are coming in Sprint 7.', 'wp-security' ) }</p>
		</div>
	);
}
