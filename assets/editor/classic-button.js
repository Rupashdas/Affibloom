/* global tinymce */
/**
 * The Classic editor's **Add A Display** (F4 R60-R63, Task ED1): a TinyMCE button that loads the display modal's
 * script once (as the block does: "Loading Display..." disabled, "Retry Add A Display" after a failure), opens the same
 * modal, and inserts the chosen display's shortcode at the cursor. The shortcode is the one the block would show (POST
 * /editor/preview answers it); the Classic editor shows no preview (R63).
 */
( function () {
	'use strict';

	const config = window.affibloomEditor || {};
	const words = config.classic || {};
	let state = window.affibloomDisplayModal ? 'ready' : 'idle';
	const buttons = [];

	const show = function () {
		buttons.forEach( function ( button ) {
			let text = words.loading;
			if ( 'ready' === state ) {
				text = words.add;
			} else if ( 'error' === state ) {
				text = words.retry;
			}
			button.text( text );
			button.disabled( 'loading' === state || 'idle' === state );
		} );
	};

	const load = function () {
		if ( 'loading' === state || 'ready' === state ) {
			return;
		}
		state = 'loading';
		show();
		const script = document.createElement( 'script' );
		script.src = config.modalScript;
		script.onload = function () {
			state = window.affibloomDisplayModal ? 'ready' : 'error';
			show();
		};
		script.onerror = function () {
			script.remove();
			state = 'error';
			show();
		};
		document.head.appendChild( script );
	};

	tinymce.PluginManager.add( 'affibloom_display', function ( editor ) {
		editor.addButton( 'affibloom_display', {
			text: words.loading,
			icon: false,
			tooltip: words.add,
			onPostRender() {
				buttons.push( this );
				load();
				show();
			},
			onclick() {
				if ( 'error' === state ) {
					load();
					return;
				}
				if ( 'ready' !== state ) {
					return;
				}
				window.affibloomDisplayModal.open( {
					opener: null,
					// The chosen display as its shortcode, at the cursor (R63).
					insert( attributes ) {
						window.wp
							.apiFetch( {
								path: '/affibloom/v1/editor/preview',
								method: 'POST',
								data: { attributes },
							} )
							.then( function ( answer ) {
								if ( answer.data.shortcode ) {
									editor.insertContent(
										answer.data.shortcode
									);
								}
							} );
					},
				} );
			},
		} );
	} );
} )();
