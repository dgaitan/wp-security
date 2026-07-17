/**
 * Client-side Markdown export for the Maintenance Report — no server
 * endpoint, no temp-file lifecycle. Formats whatever's already in the React
 * Query cache and triggers a browser download via a Blob + synthetic
 * <a download> click.
 */

function escapeCell( value ) {
	if ( value === null || value === undefined || value === '' ) {
		return '—';
	}
	return String( value ).replace( /\|/g, '\\|' ).replace( /\r?\n/g, ' ' );
}

function table( headers, rows ) {
	if ( rows.length === 0 ) {
		return '_None._\n';
	}

	const headerLine = `| ${ headers.join( ' | ' ) } |`;
	const dividerLine = `| ${ headers.map( () => '---' ).join( ' | ' ) } |`;
	const rowLines = rows.map( ( row ) => `| ${ row.map( escapeCell ).join( ' | ' ) } |` );

	return [ headerLine, dividerLine, ...rowLines ].join( '\n' ) + '\n';
}

/**
 * @param {{remediations: Array<object>, backlog: Array<object>, notes: object|null}} reportData
 * @returns {string}
 */
export function buildMarkdown( reportData ) {
	const { remediations = [], backlog = [], notes = null } = reportData ?? {};
	const generatedAt = new Date().toISOString().slice( 0, 10 );

	const lines = [
		'# WP Security — Maintenance Report',
		'',
		`Generated: ${ generatedAt }`,
		'',
		'## Updates Applied',
		'',
		table(
			[ 'Date', 'Action', 'Target', 'Status', 'By' ],
			remediations.map( ( r ) => [
				r.created_at,
				r.action_id,
				r.target,
				r.status,
				r.actor_name,
			] )
		),
		'',
		'## Open Findings Backlog',
		'',
		table(
			[ 'Severity', 'Module', 'Title', 'Status' ],
			backlog.map( ( f ) => [ f.severity, f.module_id, f.title, f.status ] )
		),
		'',
		'## Notes',
		'',
		`- **Time spent:** ${ notes?.time_spent_minutes ?? '—' } minutes`,
		`- **Client notes:** ${ notes?.client_notes || '—' }`,
		`- **Follow-up notes:** ${ notes?.follow_up_notes || '—' }`,
		'',
	];

	return lines.join( '\n' );
}

/**
 * Triggers a browser download of the given content as a .md file.
 *
 * @param {string} filename
 * @param {string} content
 */
export function downloadMarkdown( filename, content ) {
	const blob = new Blob( [ content ], { type: 'text/markdown' } );
	const url = URL.createObjectURL( blob );

	const link = document.createElement( 'a' );
	link.href = url;
	link.download = filename;
	document.body.appendChild( link );
	link.click();
	document.body.removeChild( link );

	URL.revokeObjectURL( url );
}
