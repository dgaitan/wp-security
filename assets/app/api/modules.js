/**
 * Static list of all audit modules — used to render navigation and placeholder
 * score cards before a scan has run.
 */
export const MODULES = [
	{ id: 'server', label: 'Server Health', path: '/server', icon: 'dashicons-server' },
	{ id: 'headers', label: 'Security Headers', path: '/headers', icon: 'dashicons-shield-alt' },
	{ id: 'dns', label: 'DNS', path: '/dns', icon: 'dashicons-admin-site-alt3' },
	{
		id: 'core_integrity',
		label: 'WordPress Health',
		path: '/core-integrity',
		icon: 'dashicons-wordpress-alt',
	},
	{
		id: 'plugins_themes',
		label: 'Plugins & Themes',
		path: '/plugins-themes',
		icon: 'dashicons-plugins-checked',
	},
	{ id: 'database', label: 'Database', path: '/database', icon: 'dashicons-database' },
	{ id: 'users', label: 'Users', path: '/users', icon: 'dashicons-groups' },
	{ id: 'performance', label: 'Performance', path: '/performance', icon: 'dashicons-performance' },
	{
		id: 'accessibility',
		label: 'Accessibility',
		path: '/accessibility',
		icon: 'dashicons-universal-access-alt',
	},
	{ id: 'seo', label: 'SEO', path: '/seo', icon: 'dashicons-search' },
];
