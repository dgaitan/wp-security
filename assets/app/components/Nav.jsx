import { NavLink } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MODULES } from '../api/modules';

const linkStyle = ( { isActive } ) => ( {
	display: 'flex',
	alignItems: 'center',
	gap: '6px',
	padding: '8px 12px',
	textDecoration: 'none',
	borderRadius: '3px',
	fontWeight: isActive ? 600 : 400,
	color: isActive ? '#2271b1' : '#1d2327',
	background: isActive ? '#f0f6fc' : 'transparent',
} );

export function Nav() {
	return (
		<nav
			aria-label={ __( 'WP Security sections', 'wp-security' ) }
			style={ {
				width: '200px',
				flexShrink: 0,
				borderRight: '1px solid #dcdcde',
				padding: '16px 0',
			} }
		>
			<ul style={ { listStyle: 'none', margin: 0, padding: 0 } }>
				<li>
					<NavLink to="/" end style={ linkStyle } aria-label={ __( 'Dashboard', 'wp-security' ) }>
						{ __( 'Dashboard', 'wp-security' ) }
					</NavLink>
				</li>
				{ MODULES.map( ( mod ) => (
					<li key={ mod.id }>
						<NavLink
							to={ mod.path }
							style={ linkStyle }
							aria-label={ mod.label }
						>
							{ mod.label }
						</NavLink>
					</li>
				) ) }
			</ul>
		</nav>
	);
}
