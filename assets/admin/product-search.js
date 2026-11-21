/* global wp */
/**
 * A dialog's product search (Lasso's link-monetize modal, in its group and monetize modes): the results of
 * GET /products?search=&page= as rows (thumbnail, name, URL, one action button), Previous and Next, "No links found.",
 * and the shapes of the rows while it loads. Taken out of group-members.js ("Add to Group", Task G4) on its second use,
 * the Monetize dialog (Task O10).
 *
 * window.affibloomProductSearch( options ) returns { search( fromStart ) }:
 * - options.modal: the dialog; its data-prev-label, data-next-label, data-none-text and data-failed-text give the words.
 * - options.input, options.results, options.pages, options.error: its search box, list, pager and error line.
 * - options.args(): more query arguments (the group); options.perPage: rows a page (the route's default when absent).
 * - options.button( item ): { label, disabled } for a row's action; options.choose( item, button ): its click.
 * - options.loaded( data ): called with each answer (total, page, pages).
 * - options.path: another list route with the same answer (Add group to Table reads /groups; Task T5);
 *   options.detail( item ): a row's second line (the address by default); options.picture: false for rows without one.
 */
( function () {
	'use strict';

	window.affibloomProductSearch = function ( options ) {
		var modal = options.modal;
		var term = '';
		var page = 1;
		var requests = 0;
		var timer = null;

		function problem( text ) {
			options.error.hidden = '' === text;
			options.error.textContent = text;
		}

		function resultRow( item ) {
			var li = document.createElement( 'li' );
			li.className = 'affibloom-add-results__item';
			li.dataset.productId = String( item.id );
			var picture = document.createElement( 'span' );
			picture.className = 'affibloom-add-results__image';
			picture.hidden = false === options.picture;
			if ( item.image_url ) {
				var img = document.createElement( 'img' );
				img.className = 'affibloom-thumb';
				img.src = item.image_url;
				img.alt = '';
				img.width = 40;
				img.height = 40;
				picture.appendChild( img );
			}
			var text = document.createElement( 'span' );
			text.className = 'affibloom-add-results__text';
			var name = document.createElement( 'strong' );
			name.textContent = item.name;
			var url = document.createElement( 'span' );
			url.textContent = options.detail ? options.detail( item ) : item.url;
			text.appendChild( name );
			text.appendChild( url );
			var state = options.button( item );
			var action = document.createElement( 'button' );
			action.type = 'button';
			action.className = 'affibloom-button affibloom-button--small affibloom-button--secondary';
			action.textContent = state.label;
			action.disabled = state.disabled;
			action.setAttribute( 'aria-label', action.textContent + ': ' + item.name );
			action.addEventListener( 'click', function () {
				problem( '' );
				options.choose( item, action );
			} );
			li.appendChild( picture );
			li.appendChild( text );
			li.appendChild( action );
			return li;
		}

		function pager( data ) {
			var pages = options.pages;
			pages.innerHTML = '';
			pages.hidden = data.pages < 2;
			if ( data.pages < 2 ) {
				return;
			}
			[ [ -1, modal.dataset.prevLabel, data.page > 1 ], [ 1, modal.dataset.nextLabel, data.page < data.pages ] ].forEach( function ( step ) {
				var button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'affibloom-button affibloom-button--small affibloom-button--secondary';
				button.textContent = step[ 1 ];
				button.disabled = ! step[ 2 ];
				button.addEventListener( 'click', function () {
					page = data.page + step[ 0 ];
					search();
				} );
				pages.appendChild( button );
			} );
			var where = document.createElement( 'span' );
			where.textContent = data.page + ' / ' + data.pages;
			pages.insertBefore( where, pages.lastChild );
		}

		function search() {
			var mine = ++requests;
			var results = options.results;
			var args = new URLSearchParams( options.args ? options.args() : {} );
			args.set( 'search', term );
			args.set( 'page', String( page ) );
			if ( options.perPage ) {
				args.set( 'per_page', String( options.perPage ) );
			}
			// Until the answer comes, the results show the rows' shapes (design system, "Skeleton").
			results.innerHTML = '';
			window.affibloomSkeleton( results, 3, true );
			wp.apiFetch( { path: ( options.path || '/affibloom/v1/products' ) + '?' + args.toString() } ).then( function ( response ) {
				if ( mine !== requests ) {
					return;
				}
				window.affibloomSkeleton.done( results );
				var data = response.data;
				results.innerHTML = '';
				data.items.forEach( function ( item ) {
					results.appendChild( resultRow( item ) );
				} );
				if ( 0 === data.items.length ) {
					var none = document.createElement( 'li' );
					none.className = 'affibloom-add-results__none';
					none.textContent = modal.dataset.noneText;
					results.appendChild( none );
				}
				pager( data );
				if ( options.loaded ) {
					options.loaded( data, term );
				}
			} ).catch( function () {
				if ( mine === requests ) {
					window.affibloomSkeleton.done( results );
					problem( modal.dataset.failedText );
				}
			} );
		}

		options.input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				term = options.input.value;
				page = 1;
				search();
			}, 250 );
		} );
		options.input.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				window.clearTimeout( timer );
				term = options.input.value;
				page = 1;
				search();
			}
		} );

		return {
			search: function ( fromStart ) {
				problem( '' );
				if ( fromStart ) {
					term = options.input.value;
					page = 1;
				}
				search();
			},
			problem: problem,
		};
	};
}() );
