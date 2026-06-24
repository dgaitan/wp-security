import { __ } from '@wordpress/i18n';

export function Seo() {
	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'SEO', 'wp-security' ) }</h1>
			<p className="description">{ __( 'SEO checks are coming in Sprint 7.', 'wp-security' ) }</p>
		</div>
	);
}
