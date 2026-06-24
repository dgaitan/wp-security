import { NavLink } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { MODULES } from '../api/modules';

const navLinkClass = ( { isActive } ) =>
	`wpsec-nav__link${ isActive ? ' wpsec-nav__link--active' : '' }`;

export function Nav() {
	return (
		<nav
			className="wpsec-nav"
			aria-label={ __( 'WP Security sections', 'wp-security' ) }
		>
			<ul className="wpsec-nav__list">
				<li className="wpsec-nav__item">
					<NavLink
						to="/"
						end
						className={ navLinkClass }
						aria-label={ __( 'Dashboard', 'wp-security' ) }
					>
						{ __( 'Dashboard', 'wp-security' ) }
					</NavLink>
				</li>
				{ MODULES.map( ( mod ) => (
					<li key={ mod.id } className="wpsec-nav__item">
						<NavLink
							to={ mod.path }
							className={ navLinkClass }
							aria-label={ mod.label }
						>
							{ mod.label }
						</NavLink>
					</li>
				) ) }
				<li className="wpsec-nav__item wpsec-nav__item--settings">
					<NavLink
						to="/settings"
						className={ navLinkClass }
						aria-label={ __( 'Settings', 'wp-security' ) }
					>
						{ __( 'Settings', 'wp-security' ) }
					</NavLink>
				</li>
			</ul>
		</nav>
	);
}
