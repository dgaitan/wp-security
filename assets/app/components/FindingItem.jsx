import { __ } from '@wordpress/i18n';

const STATUS_ICONS = {
	pass:    '✓',
	fail:    '✗',
	warn:    '!',
	info:    'i',
	skipped: '–',
};

function renderEvidenceValue( value ) {
	if ( Array.isArray( value ) ) {
		return value.join( ', ' ) || __( '(empty)', 'wp-security' );
	}
	if ( value !== null && typeof value === 'object' ) {
		return JSON.stringify( value );
	}
	return String( value ?? '' );
}

function EvidenceTable( { evidence } ) {
	const entries = Object.entries( evidence );
	if ( entries.length === 0 ) {
		return null;
	}

	return (
		<details className="wpsec-finding__evidence">
			<summary className="wpsec-finding__evidence-toggle">
				{ __( 'Evidence', 'wp-security' ) }
			</summary>
			<table className="wpsec-finding__evidence-table">
				<tbody>
					{ entries.map( ( [ key, value ] ) => (
						<tr key={ key } className="wpsec-finding__evidence-row">
							<th className="wpsec-finding__evidence-key">{ key }</th>
							<td className="wpsec-finding__evidence-value">
								{ renderEvidenceValue( value ) }
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</details>
	);
}

export function FindingItem( { finding } ) {
	const hasEvidence =
		finding.evidence && Object.keys( finding.evidence ).length > 0;

	return (
		<li className="wpsec-finding">
			<div className="wpsec-finding__header">
				<span
					className="wpsec-finding__status-icon"
					data-status={ finding.status }
					aria-hidden="true"
				>
					{ STATUS_ICONS[ finding.status ] ?? '?' }
				</span>
				<span
					className="wpsec-finding__badge"
					data-severity={ finding.severity }
				>
					{ finding.severity }
				</span>
				<span className="wpsec-finding__title">{ finding.title }</span>
			</div>

			{ finding.description && (
				<p className="wpsec-finding__description">{ finding.description }</p>
			) }

			{ finding.recommendation && (
				<p className="wpsec-finding__recommendation">
					{ finding.recommendation }
				</p>
			) }

			{ hasEvidence && <EvidenceTable evidence={ finding.evidence } /> }
		</li>
	);
}
