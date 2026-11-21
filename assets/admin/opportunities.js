/* global wp */
/**
 * The Opportunities screens (F22 D3, D8; Tasks O1-O3): the list redrawn in place by in-place-list.js (search, the
 * sortable headers, pages, the product filter it keeps, and Content's filter bar, whose every change reloads page 1,
 * R25), the Type icon's preview (location-preview.js), and the intro box's ×, which closes it for good
 * (POST /opportunities/intros/{box}); a failed save puts the box back.
 *
 * Monetize (R64-R72; Task O10): see monetizing() below.
 *
 * Dismiss (R44-R46, D11; Task O7): a row's × opens "Dismiss This Opportunity"; Dismiss posts
 * /opportunities/locations/{row}/dismiss, the row fades and the list is drawn again (its count and pager follow, and the
 * focus lands on the list), and the toast "Opportunity dismissed." offers Undo, which deletes the flag and draws the
 * list again. A failure closes the dialog and says so.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-opportunities-list' );
	if ( region && window.affibloomInPlaceList ) {
		var args = {};
		try {
			args = JSON.parse( region.getAttribute( 'data-list-args' ) || '{}' );
		} catch ( ignored ) {
			args = {};
		}
		var filters = document.getElementById( 'affibloom-content-filters' );
		var list = window.affibloomInPlaceList( {
			region: region,
			search: document.getElementById( 'affibloom-opportunities-search' ),
			status: document.getElementById( 'affibloom-list-status' ),
			errorTemplate: document.getElementById( 'affibloom-list-error' ),
			path: region.getAttribute( 'data-list-path' ),
			args: filters
				? function () {
					var values = {};
					new FormData( filters ).forEach( function ( value, key ) {
						if ( 'page' !== key && 'tab' !== key ) {
							values[ key ] = value;
						}
					} );
					return values;
				}
				: args,
		} );
		if ( filters ) {
			contentExport( region, filters );
			filters.addEventListener( 'change', function () {
				list.reload();
			} );
			filters.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				list.reload();
			} );
		}
		if ( window.affibloomLocationPreview ) {
			window.affibloomLocationPreview( region );
		}
		dismissals( region, list );
		monetizing( region, list );
	}

	/**
	 * Content's Export CSV (R57-R59; Task O9): after each list the button follows the answer's count (off, with
	 * Lasso's title, when the file would be empty); a click downloads the file for the list on screen: the address
	 * bar's search and sort (the in-place list keeps it current) and the filter bar's values.
	 *
	 * @param {HTMLElement}     listRegion The list region.
	 * @param {HTMLFormElement} filterBar  The filter bar.
	 */
	function contentExport( listRegion, filterBar ) {
		var button = filterBar.querySelector( '[data-export-csv]' );
		if ( ! button ) {
			return;
		}
		listRegion.addEventListener( 'affibloom:list-loaded', function ( event ) {
			if ( 'number' !== typeof event.detail.exports ) {
				return;
			}
			button.disabled = 0 === event.detail.exports;
			button.title = button.getAttribute( button.disabled ? 'data-title-off' : 'data-title-on' );
		} );
		button.addEventListener( 'click', function () {
			var shown = new URLSearchParams( window.location.search );
			var args = new URLSearchParams();
			new FormData( filterBar ).forEach( function ( value, key ) {
				if ( 'page' !== key && 'tab' !== key ) {
					args.set( key, String( value ) );
				}
			} );
			args.set( 'search', shown.get( 's' ) || '' );
			if ( shown.get( 'orderby' ) ) {
				args.set( 'orderby', shown.get( 'orderby' ) );
				args.set( 'order', shown.get( 'order' ) || '' );
			}
			window.affibloomCsvDownload( button, button.getAttribute( 'data-export-path' ) + '?' + args.toString() );
		} );
	}

	/**
	 * Monetize (R64-R72, N1-A, N2; Task O10). A switch turned on opens "Monetize": a product's Monetize stores the
	 * assignment (PUT …/assignment) and the list is drawn again (the link leaves the opportunity lists; on a post's
	 * links it shows "Monetized with …"); Create a Link hands over to Add a Link (the address filled in for an Amazon
	 * link), whose new product is then chosen. Closing either dialog without a choice turns the switch off. A switch
	 * turned off clears the assignment (DELETE …/assignment).
	 *
	 * @param {HTMLElement} listRegion The list region.
	 * @param {Object}      inPlace    The in-place list ({ refresh() }).
	 */
	function monetizing( listRegion, inPlace ) {
		var modal = document.getElementById( 'affibloom-monetize' );
		var adder = document.getElementById( 'affibloom-add-modal' );
		if ( ! modal ) {
			return;
		}
		var input = modal.querySelector( '#affibloom-monetize-search' );
		var placeholder = input.getAttribute( 'placeholder' );
		var pending = null;
		var settled = false;
		var handing = false;
		var path = function ( id ) {
			return '/affibloom/v1/opportunities/locations/' + encodeURIComponent( id ) + '/assignment';
		};
		var finder = window.affibloomProductSearch( {
			modal: modal,
			input: input,
			results: modal.querySelector( '[data-monetize-results]' ),
			pages: modal.querySelector( '[data-monetize-pages]' ),
			error: modal.querySelector( '[data-monetize-error]' ),
			perPage: 6,
			button: function () {
				return { label: modal.dataset.chooseLabel, disabled: false };
			},
			choose: function ( item, button ) {
				button.disabled = true;
				assign( item.id, function () {
					button.disabled = false;
					finder.problem( modal.dataset.failedText );
				} );
			},
			// "Search URLs" becomes "Search N URLs" once the library's size is known (R65).
			loaded: function ( data, term ) {
				if ( '' === term ) {
					input.setAttribute( 'placeholder', modal.dataset.searchMany.replace( '%s', Number( data.total ).toLocaleString() ) );
				}
			},
		} );

		function assign( product, failed ) {
			var chosen = pending;
			wp.apiFetch( { path: path( chosen.row ), method: 'PUT', data: { product: product } } ).then( function () {
				settled = true;
				window.affibloomModal.hide();
				var line = listRegion.querySelector( 'tr[data-location-id="' + chosen.row + '"]' );
				if ( line ) {
					line.classList.add( 'is-monetized' );
				}
				window.affibloomToast( modal.dataset.doneText, false );
				window.setTimeout( inPlace.refresh, 400 );
			} ).catch( function ( error ) {
				if ( failed ) {
					failed( error );
				} else {
					chosen.input.checked = false;
					window.affibloomToast( ( error && error.message ) || modal.dataset.failedText, true );
				}
			} );
		}

		listRegion.addEventListener( 'change', function ( event ) {
			var toggle = event.target.closest( '[data-monetize]' );
			if ( ! toggle ) {
				return;
			}
			var row = toggle.getAttribute( 'data-monetize' );
			if ( toggle.checked ) {
				pending = { row: row, input: toggle, amazon: toggle.hasAttribute( 'data-monetize-amazon' ), url: toggle.getAttribute( 'data-monetize-url' ) };
				settled = false;
				window.affibloomModal.show( modal, toggle );
				return;
			}
			wp.apiFetch( { path: path( row ), method: 'DELETE' } ).then( function () {
				window.affibloomToast( modal.dataset.offText, false );
				inPlace.refresh();
			} ).catch( function () {
				toggle.checked = true;
				window.affibloomToast( modal.dataset.offFailed, true );
			} );
		} );

		modal.addEventListener( 'affibloom:open', function () {
			input.value = '';
			finder.search( true );
		} );
		modal.addEventListener( 'affibloom:close', function () {
			if ( pending && ! settled && ! handing ) {
				pending.input.checked = false;
			}
		} );

		// Create a Link (R65, N2): Add a Link in its monetize mode; the new product is assigned to the row.
		modal.querySelector( '[data-monetize-create]' ).addEventListener( 'click', function () {
			if ( ! adder || ! pending ) {
				return;
			}
			handing = true;
			window.affibloomModal.hide();
			handing = false;
			adder.dataset.prefill = pending.amazon ? pending.url : '';
			pending.input.setAttribute( 'data-affibloom-mode', 'monetize' );
			window.affibloomModal.show( adder, pending.input );
			pending.input.removeAttribute( 'data-affibloom-mode' );
		} );
		if ( adder ) {
			adder.addEventListener( 'affibloom:link-created', function ( event ) {
				if ( pending && ! settled ) {
					assign( event.detail.id );
				}
			} );
			adder.addEventListener( 'affibloom:close', function () {
				// Closed without a new product: the switch goes off, as when "Monetize" closes.
				window.setTimeout( function () {
					if ( pending && ! settled && adder.hidden && modal.hidden ) {
						pending.input.checked = false;
					}
				}, 0 );
			} );
		}
	}

	/**
	 * The rows' × and its dialog.
	 *
	 * @param {HTMLElement} listRegion The list region.
	 * @param {Object}      inPlace    The in-place list ({ refresh() }).
	 */
	function dismissals( listRegion, inPlace ) {
		var modal = document.getElementById( 'affibloom-dismiss-opportunity' );
		if ( ! modal ) {
			return;
		}
		var confirm = modal.querySelector( '[data-dismiss-confirm]' );
		var label = confirm.textContent;
		var row = '';
		var path = function ( id ) {
			return '/affibloom/v1/opportunities/locations/' + encodeURIComponent( id ) + '/dismiss';
		};

		listRegion.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-dismiss-opportunity]' );
			if ( button ) {
				row = button.getAttribute( 'data-dismiss-opportunity' );
			}
		} );

		var undo = function ( id ) {
			wp.apiFetch( { path: path( id ), method: 'DELETE' } ).then( function () {
				inPlace.refresh();
			} ).catch( function () {
				window.affibloomToast( modal.dataset.undoFailed, true );
			} );
		};

		confirm.addEventListener( 'click', function () {
			if ( ! row || confirm.disabled ) {
				return;
			}
			var id = row;
			confirm.disabled = true;
			confirm.textContent = confirm.dataset.busyLabel;
			wp.apiFetch( { path: path( id ), method: 'POST' } ).then( function () {
				window.affibloomModal.hide();
				var line = listRegion.querySelector( 'tr[data-location-id="' + id + '"]' );
				if ( line ) {
					line.classList.add( 'is-dismissed' );
				}
				window.setTimeout( inPlace.refresh, 200 );
				window.affibloomToast( modal.dataset.doneText, false, {
					label: modal.dataset.undoLabel,
					run: function () {
						undo( id );
					},
				} );
			} ).catch( function () {
				window.affibloomModal.hide();
				window.affibloomToast( modal.dataset.failedText, true );
			} ).then( function () {
				confirm.disabled = false;
				confirm.textContent = label;
				row = '';
			} );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var close = event.target.closest( '[data-intro-close]' );
		if ( ! close ) {
			return;
		}
		var box = close.closest( '[data-intro]' );
		box.hidden = true;
		wp.apiFetch( { path: '/affibloom/v1/opportunities/intros/' + close.getAttribute( 'data-intro-close' ), method: 'POST' } ).catch( function () {
			box.hidden = false;
		} );
	} );
}() );
