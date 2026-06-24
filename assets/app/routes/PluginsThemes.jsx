import { __ } from '@wordpress/i18n';

export function PluginsThemes() {
	return (
		<div className="wrap" style={ { padding: '24px' } }>
			<h1>{ __( 'Plugins & Themes', 'wp-security' ) }</h1>
			<p className="description">{ __( 'Plugins & Themes checks are coming in Sprint 6.', 'wp-security' ) }</p>
		</div>
	);
}
