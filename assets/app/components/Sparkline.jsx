/**
 * Minimal SVG sparkline.
 *
 * Accepts an array of numeric values and renders a polyline chart scaled
 * to fit the given width/height.  Used on the dashboard to show score
 * history.
 *
 * @param {{ data: number[], width?: number, height?: number, color?: string }} props
 */
export function Sparkline( { data, width = 160, height = 48, color = '#3b82f6' } ) {
	if ( ! data || data.length < 2 ) {
		return null;
	}

	const min   = Math.min( ...data );
	const max   = Math.max( ...data );
	const range = max === min ? 1 : max - min;

	const points = data
		.map( ( val, i ) => {
			const x = ( i / ( data.length - 1 ) ) * width;
			const y = height - ( ( val - min ) / range ) * ( height - 4 ) - 2;
			return `${ x },${ y }`;
		} )
		.join( ' ' );

	return (
		<svg
			width={ width }
			height={ height }
			viewBox={ `0 0 ${ width } ${ height }` }
			aria-hidden="true"
			className="wpsec-sparkline"
		>
			<polyline
				points={ points }
				fill="none"
				stroke={ color }
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</svg>
	);
}
