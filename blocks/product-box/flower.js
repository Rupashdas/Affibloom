/**
 * Affibloom's flower mark, drawn as the admin header's (partials/header.php): five petals round a white centre with a
 * dot, in Meadow's green. For the inserter and the block's panel.
 */
import { Circle, G, SVG } from '@wordpress/primitives';

const PETALS = [ 0, 72, 144, 216, 288 ];
const GREEN = '#1E6B47';

export const FlowerIcon = (
	<SVG
		viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
		focusable="false"
	>
		<G transform="translate(16 16)" fill={ GREEN } fillOpacity="0.86">
			{ PETALS.map( ( angle ) => (
				<ellipse
					key={ angle }
					cy="-7.4"
					rx="4.7"
					ry="7.4"
					transform={ `rotate(${ angle })` }
				/>
			) ) }
		</G>
		<Circle cx="16" cy="16" r="3.6" fill="#fff" />
		<Circle cx="16" cy="16" r="1.5" fill={ GREEN } />
	</SVG>
);
