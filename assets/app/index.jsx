/**
 * WP Security — React SPA entry point.
 *
 * Mounts the application into <div id="wp-security-root"> which is rendered
 * by Admin\AdminPage::renderPage().
 *
 * Bootstrap data (REST root URL, nonce, capabilities) is provided by
 * wp_add_inline_script via Admin\AdminPage::enqueueAssets() and read from
 * window.wpSecurityData.
 *
 * TODO Sprint 3: implement full routing shell with React Router + react-query.
 */

import { createRoot } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			staleTime: 30_000,
			retry: 1,
		},
	},
} );

function App() {
	return (
		<QueryClientProvider client={ queryClient }>
			{ /* TODO Sprint 3: <RouterProvider router={router} /> */ }
			<div style={ { padding: '2rem', fontFamily: 'sans-serif' } }>
				<h1>WP Security</h1>
				<p>Dashboard coming in Sprint 3.</p>
			</div>
		</QueryClientProvider>
	);
}

const root = document.getElementById( 'wp-security-root' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
