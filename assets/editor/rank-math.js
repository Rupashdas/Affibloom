/**
 * Rank Math's content analysis in the block editor reads Affibloom's displays (F4 R73-R74, Task ED3), as Lasso's
 * rank-math-integration.js does: a block stores only its attributes, so the analysis would miss every display's words.
 *
 * - `rank_math_content` (priority 11, after Rank Math's own): the post's content plus the HTML of every block preview in
 *   the editor's canvas, which is the page's display as the server draws it.
 * - The block's `affibloom.previewRendered` action: the analysis is refreshed once, a second after the last preview.
 *
 * EditorAssets loads it only where Rank Math's analyzer is registered.
 */
( function () {
	'use strict';

	const hooks = window.wp.hooks;
	let timer = null;

	/**
	 * The editor's canvas: its frame's document, or the page's when the editor has no frame.
	 *
	 * @return {Document} Where the blocks are drawn.
	 */
	const canvas = function () {
		const frame = document.querySelector( 'iframe[name="editor-canvas"]' );
		return ( frame && frame.contentDocument ) || document;
	};

	hooks.addFilter(
		'rank_math_content',
		'affibloom/rank-math',
		function ( content ) {
			let displays = '';
			canvas()
				.querySelectorAll( '.affibloom-block__preview' )
				.forEach( function ( preview ) {
					displays += '\n' + preview.innerHTML + '\n';
				} );
			return content + displays;
		},
		11
	);

	hooks.addAction(
		'affibloom.previewRendered',
		'affibloom/rank-math',
		function () {
			clearTimeout( timer );
			timer = setTimeout( function () {
				if ( window.rankMathEditor ) {
					window.rankMathEditor.refresh( 'content' );
				}
			}, 1000 );
		}
	);
} )();
