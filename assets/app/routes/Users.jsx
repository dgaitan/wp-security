import { __ } from '@wordpress/i18n';

export function Users() {
	return (
		<div className="wrap" style={ { padding: '24px' } }>
			<h1>{ __( 'Users', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Users checks are coming in Sprint 6.', 'wp-security' ) }</p>
		</div>
	);
}
