import { __ } from '@wordpress/i18n';

const STATUS_ICONS = {
	pass:    '✓',
	fail:    '✗',
	warn:    '!',
	info:    'i',
	skipped: '–',
};

function cellText( cell ) {
	if ( cell === null || cell === undefined || cell === '' ) {
		return '—';
	}
	if ( typeof cell === 'object' ) {
		return JSON.stringify( cell );
	}
	return String( cell );
}

function EvidenceValue( { type, value } ) {
	switch ( type ) {
		case 'empty':
			return (
				<span className="wpsec-finding__evidence-empty">
					{ __( 'None', 'wp-security' ) }
				</span>
			);

		case 'boolean':
			return (
				<span
					className="wpsec-finding__evidence-boolean"
					data-value={ value ? 'yes' : 'no' }
				>
					{ value ? __( 'Yes', 'wp-security' ) : __( 'No', 'wp-security' ) }
				</span>
			);

		case 'list':
			if ( ! Array.isArray( value ) || value.length === 0 ) {
				return (
					<span className="wpsec-finding__evidence-empty">
						{ __( 'None', 'wp-security' ) }
					</span>
				);
			}
			return (
				<ul className="wpsec-finding__evidence-list">
					{ value.map( ( item, index ) => (
						// eslint-disable-next-line react/no-array-index-key
						<li key={ index }>{ cellText( item ) }</li>
					) ) }
				</ul>
			);

		case 'table':
			if ( ! value || ! Array.isArray( value.rows ) || value.rows.length === 0 ) {
				return (
					<span className="wpsec-finding__evidence-empty">
						{ __( 'None', 'wp-security' ) }
					</span>
				);
			}
			return (
				<table className="wpsec-finding__evidence-subtable">
					<thead>
						<tr>
							{ value.columns.map( ( column ) => (
								<th key={ column.key }>{ column.label }</th>
							) ) }
						</tr>
					</thead>
					<tbody>
						{ value.rows.map( ( row, index ) => (
							// eslint-disable-next-line react/no-array-index-key
							<tr key={ index }>
								{ value.columns.map( ( column ) => (
									<td key={ column.key }>{ cellText( row[ column.key ] ) }</td>
								) ) }
							</tr>
						) ) }
					</tbody>
				</table>
			);

		case 'group':
			if ( ! Array.isArray( value ) || value.length === 0 ) {
				return (
					<span className="wpsec-finding__evidence-empty">
						{ __( 'None', 'wp-security' ) }
					</span>
				);
			}
			return (
				<dl className="wpsec-finding__evidence-group">
					{ value.map( ( item ) => (
						<div className="wpsec-finding__evidence-group-row" key={ item.key }>
							<dt>{ item.label }</dt>
							<dd>
								<EvidenceValue type={ item.type } value={ item.value } />
							</dd>
						</div>
					) ) }
				</dl>
			);

		case 'scalar':
		default:
			return <span>{ cellText( value ) }</span>;
	}
}

function EvidenceTable( { evidence } ) {
	if ( ! Array.isArray( evidence ) || evidence.length === 0 ) {
		return null;
	}

	return (
		<details className="wpsec-finding__evidence">
			<summary className="wpsec-finding__evidence-toggle">
				{ __( 'Evidence', 'wp-security' ) }
			</summary>
			<table className="wpsec-finding__evidence-table">
				<tbody>
					{ evidence.map( ( item ) => (
						<tr key={ item.key } className="wpsec-finding__evidence-row">
							<th className="wpsec-finding__evidence-key">{ item.label }</th>
							<td className="wpsec-finding__evidence-value">
								<EvidenceValue type={ item.type } value={ item.value } />
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
		Array.isArray( finding.evidence ) && finding.evidence.length > 0;

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
