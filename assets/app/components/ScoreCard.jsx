import { __ } from '@wordpress/i18n';

const gradeColor = ( grade ) => {
	switch ( grade ) {
		case 'A':
			return '#00a32a';
		case 'B':
			return '#46b450';
		case 'C':
			return '#dba617';
		case 'D':
			return '#d63638';
		default:
			return '#8c8f94';
	}
};

/**
 * Displays a per-module score card.
 *
 * @param {{ label: string, score: number|null, grade: string|null, path: string }} props
 */
export function ScoreCard( { label, score, grade, path } ) {
	const displayScore = score !== null && score !== undefined ? score : '--';
	const displayGrade = grade ?? '--';
	const color = grade ? gradeColor( grade ) : '#8c8f94';

	return (
		<a
			href={ `#${ path }` }
			style={ {
				display: 'block',
				textDecoration: 'none',
				color: 'inherit',
				border: '1px solid #dcdcde',
				borderRadius: '4px',
				padding: '20px',
				background: '#fff',
			} }
			aria-label={ `${ label } — ${ __( 'score', 'wp-security' ) }: ${ displayScore }` }
		>
			<div style={ { fontSize: '13px', color: '#646970', marginBottom: '8px' } }>{ label }</div>
			<div style={ { display: 'flex', alignItems: 'baseline', gap: '8px' } }>
				<span style={ { fontSize: '32px', fontWeight: 700, color } }>{ displayScore }</span>
				<span style={ { fontSize: '18px', fontWeight: 600, color } }>{ displayGrade }</span>
			</div>
		</a>
	);
}
