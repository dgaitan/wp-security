import { __ } from '@wordpress/i18n';
import { FindingItem } from './FindingItem';

/**
 * Renders the open (WARN/FAIL) findings backlog across all modules for the
 * Maintenance Report — reuses FindingItem, the same renderer ModuleFindings
 * uses per-module, since backlog rows are shaped identically to findings.
 *
 * @param {{ findings: Array<object> }} props
 */
export function BacklogList( { findings } ) {
	if ( ! findings || findings.length === 0 ) {
		return (
			<p className="wpsec-maintenance-report__empty">
				{ __( 'No open findings — nothing in the backlog.', 'wp-security' ) }
			</p>
		);
	}

	return (
		<ul
			className="wpsec-findings-list"
			aria-label={ __( 'Open findings backlog', 'wp-security' ) }
		>
			{ findings.map( ( finding ) => (
				<FindingItem key={ `${ finding.module_id }-${ finding.check_id }` } finding={ finding } />
			) ) }
		</ul>
	);
}
