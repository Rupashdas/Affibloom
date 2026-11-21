/* global wp, tinymce */
/**
 * "Edit in post" (F22 N1-B; Task O10): the editor opens with the opportunity's link selected. The link is the
 * occurrence-th link to its address in the post's content, counted in the order the index counts them (document
 * order). The block editor selects the block that holds it and scrolls to it; the classic editor selects the link.
 * window.affibloomSelectLocation = { url, occurrence } (Interface\Editor\LocationSelector).
 */
( function () {
	'use strict';

	var wanted = window.affibloomSelectLocation;
	if ( ! wanted ) {
		return;
	}

	/** How many links to the address an HTML fragment holds. */
	function count( html ) {
		var body = new window.DOMParser().parseFromString( html, 'text/html' ).body;
		return Array.prototype.filter.call( body.querySelectorAll( 'a[href]' ), function ( link ) {
			return link.getAttribute( 'href' ).trim() === wanted.url;
		} ).length;
	}

	/** The innermost block holding the wanted link, walking the blocks in order. */
	function find( blocks, state ) {
		for ( var i = 0; i < blocks.length; i++ ) {
			var block = blocks[ i ];
			if ( block.innerBlocks && block.innerBlocks.length ) {
				var inner = find( block.innerBlocks, state );
				if ( inner ) {
					return inner;
				}
				continue;
			}
			state.seen += count( wp.blocks.getBlockContent( block ) );
			if ( state.seen >= wanted.occurrence ) {
				return block;
			}
		}
		return null;
	}

	function blockEditor() {
		var unsubscribe = wp.data.subscribe( function () {
			var blocks = wp.data.select( 'core/block-editor' ).getBlocks();
			if ( ! blocks.length ) {
				return;
			}
			unsubscribe();
			var block = find( blocks, { seen: 0 } );
			if ( ! block ) {
				return;
			}
			// The editor settles its own focus (the title) after the blocks arrive, which would clear an earlier selection.
			window.setTimeout( function () {
				wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
			}, 600 );
			window.setTimeout( function () {
				var selector = '[data-block="' + block.clientId + '"]';
				var canvas = document.querySelector( 'iframe[name="editor-canvas"]' );
				var shown = document.querySelector( selector ) || ( canvas && canvas.contentDocument ? canvas.contentDocument.querySelector( selector ) : null );
				if ( shown ) {
					shown.scrollIntoView( { block: 'center' } );
				}
			}, 900 );
		} );
	}

	function classicEditor() {
		if ( 'undefined' === typeof tinymce ) {
			return;
		}
		tinymce.on( 'AddEditor', function ( event ) {
			event.editor.on( 'init', function () {
				var links = Array.prototype.filter.call( event.editor.getBody().querySelectorAll( 'a[href]' ), function ( link ) {
					return link.getAttribute( 'href' ).trim() === wanted.url;
				} );
				var link = links[ wanted.occurrence - 1 ];
				if ( link ) {
					event.editor.selection.select( link );
					link.scrollIntoView( { block: 'center' } );
				}
			} );
		} );
	}

	if ( window.wp && wp.data && wp.blocks && wp.data.select( 'core/block-editor' ) ) {
		blockEditor();
	} else {
		classicEditor();
	}
}() );
