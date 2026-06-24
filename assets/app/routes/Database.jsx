import { __ } from '@wordpress/i18n';

export function Database() {
	return (
		<div className="wrap" style={ { padding: '24px' } }>
			<h1>{ __( 'Database', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Database checks are coming in Sprint 6.', 'wp-security' ) }</p>
		</div>
	);
}
