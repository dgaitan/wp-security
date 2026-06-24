import { __ } from '@wordpress/i18n';

/**
 * Displays a per-module score card.
 *
 * Grade-based colour is driven by the `data-grade` attribute + SCSS selectors
 * in `styles/_score-card.scss` — no inline styles used.
 *
 * @param {{ label: string, score: number|null, grade: string|null, path: string }} props
 */
export function ScoreCard( { label, score, grade, path } ) {
	const displayScore = score !== null && score !== undefined ? score : '--';
	const displayGrade = grade ?? '--';
	const dataGrade = grade ? grade.toLowerCase() : 'none';

	return (
		<a
			href={ `#${ path }` }
			className="wpsec-score-card"
			data-grade={ dataGrade }
			aria-label={ `${ label } — ${ __( 'score', 'wp-security' ) }: ${ displayScore }` }
		>
			<div className="wpsec-score-card__label">{ label }</div>
			<div className="wpsec-score-card__values">
				<span className="wpsec-score-card__score">{ displayScore }</span>
				<span className="wpsec-score-card__grade">{ displayGrade }</span>
			</div>
		</a>
	);
}
