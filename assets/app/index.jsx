/**
 * WP Security — React SPA entry point.
 *
 * Mounts the application into <div id="wp-security-root"> rendered by
 * Admin\AdminPage::renderPage(). React Router v6 handles all section routing
 * client-side using a hash router (safe inside wp-admin's URL structure).
 *
 * Bootstrap data (REST root URL, nonce) is provided by wp_add_inline_script
 * and read from window.wpSecurityData. @wordpress/api-fetch is configured with
 * the nonce once on mount so all subsequent queries are authenticated.
 */

import { createRoot } from '@wordpress/element';
import { createHashRouter, RouterProvider, Outlet } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { Nav } from './components/Nav';
import { Dashboard } from './routes/Dashboard';
import { Server } from './routes/Server';
import { Headers } from './routes/Headers';
import { Dns } from './routes/Dns';
import { CoreIntegrity } from './routes/CoreIntegrity';
import { PluginsThemes } from './routes/PluginsThemes';
import { Database } from './routes/Database';
import { Users } from './routes/Users';
import { Performance } from './routes/Performance';
import { Accessibility } from './routes/Accessibility';
import { Seo } from './routes/Seo';
import './styles/index.scss';

// Configure api-fetch with the nonce from the inline bootstrap data.
if ( window.wpSecurityData?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.wpSecurityData.nonce ) );
}
if ( window.wpSecurityData?.restRoot ) {
	apiFetch.use( apiFetch.createRootURLMiddleware( window.wpSecurityData.restRoot ) );
}

const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			staleTime: 30_000,
			retry: 1,
		},
	},
} );

function Layout() {
	return (
		<QueryClientProvider client={ queryClient }>
			<div className="wpsec-layout">
				<Nav />
				<main className="wpsec-main">
					<Outlet />
				</main>
			</div>
		</QueryClientProvider>
	);
}

const router = createHashRouter( [
	{
		path: '/',
		element: <Layout />,
		children: [
			{ index: true, element: <Dashboard /> },
			{ path: 'server', element: <Server /> },
			{ path: 'headers', element: <Headers /> },
			{ path: 'dns', element: <Dns /> },
			{ path: 'core-integrity', element: <CoreIntegrity /> },
			{ path: 'plugins-themes', element: <PluginsThemes /> },
			{ path: 'database', element: <Database /> },
			{ path: 'users', element: <Users /> },
			{ path: 'performance', element: <Performance /> },
			{ path: 'accessibility', element: <Accessibility /> },
			{ path: 'seo', element: <Seo /> },
		],
	},
] );

const root = document.getElementById( 'wp-security-root' );
if ( root ) {
	createRoot( root ).render( <RouterProvider router={ router } /> );
}
