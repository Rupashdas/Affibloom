/**
 * The product page's image (F1, Task 14B). "Update Image" opens WordPress's media library for one image, which fills
 * the image link, the media item and the preview. Apply checks a pasted link as Lasso's upload_thumbnail does ("Image
 * URL is required.", "Invalid image url.", no data: links) and shows it. Neither stores anything: Save Changes does
 * (Task 15; F1 requirements D2), where Lasso's Apply wrote the link at once.
 */
( function () {
	'use strict';

	/**
	 * Copies text to the clipboard, with the old selection trick where the clipboard API is not allowed (Lasso's
	 * lassoShareKitFallbackCopy).
	 *
	 * @param {string}   text The text.
	 * @param {Function} done Called once it is copied.
	 */
	function copy( text, done ) {
		var fallback = function () {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.setAttribute( 'readonly', '' );
			area.style.position = 'absolute';
			area.style.left = '-9999px';
			document.body.appendChild( area );
			area.select();
			document.execCommand( 'copy' );
			area.remove();
			done();
		};
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done ).catch( fallback );
		} else {
			fallback();
		}
	}

	/**
	 * Lasso 370's Share kit (Task 14D): each button copies its snippet from the page as it is now and says "Copied!" for
	 * 1.5 seconds; its name stays the action. The shareable URL is the destination until link cloaking exists (D5).
	 *
	 * @param {HTMLElement} kit The Share kit card.
	 */
	function shareKit( kit ) {
		var snippet = function ( target ) {
			var destination = document.getElementById( 'affibloom-url' );
			var disclosure = document.getElementById( 'affibloom-disclosure' );
			if ( 'public' === target || 'destination' === target ) {
				return destination ? destination.value.trim() : '';
			}
			if ( 'disclosure' === target && disclosure ) {
				return '' !== disclosure.value.trim() ? disclosure.value.trim() : disclosure.getAttribute( 'placeholder' ) || '';
			}
			return '';
		};
		kit.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-share]' );
			var text = button ? snippet( button.dataset.share ) : '';
			if ( ! button || button.disabled || '' === text ) {
				return;
			}
			var label = button.querySelector( '[data-share-label]' );
			var original = label.textContent;
			copy( text, function () {
				label.textContent = kit.dataset.copied;
				window.setTimeout( function () {
					label.textContent = original;
				}, 1500 );
			} );
		} );
	}

	/**
	 * Lasso's description and price boxes say "Enter a description" and "$99.99" while empty (Task 2X1). TinyMCE 4.9 has
	 * no placeholder, so each editor's setting `placeholder` (ProductEditors) is copied to the editor body as
	 * `data-placeholder`, and the class `affibloom-empty` is on the body while the editor holds nothing; the style that
	 * draws the words is the editor's own (also ProductEditors), so they never become content.
	 *
	 * @param {Object} editor The TinyMCE editor.
	 */
	function placeholder( editor ) {
		var update = function () {
			var body = editor.getBody();
			if ( ! body ) {
				return;
			}
			body.setAttribute( 'data-placeholder', editor.getParam( 'placeholder', '' ) );
			body.classList.toggle( 'affibloom-empty', editor.dom.isEmpty( body ) );
		};
		editor.on( 'init SetContent keyup input change NodeChange undo redo', update );
		if ( editor.initialized ) {
			update();
		}
	}

	/**
	 * The page's editors get the inputs' focus border. Their text is inside an iframe, and a focused iframe does not
	 * count for :focus-within on its parents, so the editor's own focus and blur set a class on its .affibloom-editor box.
	 *
	 * @param {Object} editor The TinyMCE editor.
	 */
	function focusBorder( editor ) {
		var box = editor.getElement().closest( '.affibloom-editor' );
		if ( ! box ) {
			return;
		}
		editor.on( 'focus', function () {
			box.classList.add( 'affibloom-editor--focus' );
		} );
		editor.on( 'blur', function () {
			box.classList.remove( 'affibloom-editor--focus' );
		} );
	}

	/**
	 * One of the page's editors: its placeholder, if it has one, and its focus border.
	 *
	 * @param {Object} editor The TinyMCE editor.
	 */
	function setUpEditor( editor ) {
		if ( editor.getParam( 'placeholder' ) ) {
			placeholder( editor );
		}
		focusBorder( editor );
	}

	/**
	 * The Features list (C1Q4): Add Feature, and on each bullet a grip it is dragged by (drag-rows.js; Arrow Up / Down
	 * from the keyboard; the user, 2026-11-16) and Remove. After every change the labels are renumbered ("Feature 2",
	 * "Reorder Feature 2") and an input event tells the unsaved-changes guard. list.affibloomFeatures.set( texts ) replaces the bullets (Refresh).
	 *
	 * @param {HTMLElement} field The field ([data-features]).
	 */
	function featureList( field ) {
		var list = field.querySelector( '[data-feature-list]' );
		var template = field.querySelector( '[data-feature-template]' );
		var label = function ( pattern, number ) {
			return pattern.replace( '%d', String( number ) );
		};
		var renumber = function () {
			var rows = list.querySelectorAll( '[data-feature]' );
			Array.prototype.forEach.call( rows, function ( row, index ) {
				var number = index + 1;
				row.querySelector( '[data-feature-text]' ).setAttribute( 'aria-label', label( list.dataset.label, number ) );
				row.querySelector( '[data-grip]' ).setAttribute( 'aria-label', label( list.dataset.gripLabel, number ) );
				row.querySelector( '[data-feature-remove]' ).setAttribute( 'aria-label', label( list.dataset.remove, number ) );
			} );
		};
		var changed = function () {
			renumber();
			list.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		};
		var add = function ( text ) {
			var row = template.content.firstElementChild.cloneNode( true );
			row.querySelector( '[data-feature-text]' ).value = text;
			list.appendChild( row );
			return row;
		};

		field.querySelector( '[data-feature-add]' ).addEventListener( 'click', function () {
			var row = add( '' );
			changed();
			row.querySelector( '[data-feature-text]' ).focus();
		} );
		list.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-feature-remove]' );
			var row = button ? button.closest( '[data-feature]' ) : null;
			if ( ! row ) {
				return;
			}
			// Focus stays in the list: the next bullet, else the one before, else Add Feature.
			var next = row.nextElementSibling || row.previousElementSibling;
			row.remove();
			changed();
			( next ? next.querySelector( '[data-feature-text]' ) : field.querySelector( '[data-feature-add]' ) ).focus();
		} );
		window.affibloomDragRows( list, {
			row: '[data-feature]',
			movable: function () {
				return Array.prototype.slice.call( list.querySelectorAll( '[data-feature]' ) );
			},
			dropped: changed,
		} );
		list.affibloomFeatures = {
			set: function ( texts ) {
				list.innerHTML = '';
				texts.forEach( add );
				renumber();
			},
		};
		renumber();
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var features = document.querySelector( '[data-features]' );
		if ( features ) {
			featureList( features );
		}

		var kit = document.querySelector( '[data-share-kit]' );
		if ( window.tinymce ) {
			// An editor may already exist when this runs, or arrive after it.
			window.tinymce.editors.forEach( setUpEditor );
			window.tinymce.on( 'AddEditor', function ( event ) {
				setUpEditor( event.editor );
			} );
		}
		if ( kit ) {
			shareKit( kit );
		}

		// "No" on the tracking-ID offer hides it for now, as Lasso's does; it shows again on the next visit.
		var offer = document.querySelector( '[data-tracking-notice]' );
		var no = offer && offer.querySelector( '[data-tracking-dismiss]' );
		if ( no ) {
			no.hidden = false;
			no.addEventListener( 'click', function () {
				offer.hidden = true;
			} );
		}

		var frame = document.querySelector( '[data-image-frame]' );
		var link = document.querySelector( '[data-image-url]' );
		if ( ! frame || ! link ) {
			return;
		}
		var preview = frame.querySelector( '[data-image-preview]' );
		var empty = frame.querySelector( '[data-image-empty]' );
		var choose = frame.querySelector( '[data-image-choose]' );
		var media = document.querySelector( '[data-image-id]' );
		var error = document.querySelector( '[data-image-error]' );
		var apply = document.querySelector( '[data-image-apply]' );

		var show = function ( src ) {
			preview.src = src;
			preview.hidden = false;
			empty.hidden = true;
		};

		var fail = function ( message ) {
			error.textContent = message;
			error.hidden = '' === message;
			link.classList.toggle( 'is-invalid', '' !== message );
			if ( '' === message ) {
				link.removeAttribute( 'aria-invalid' );
			} else {
				link.setAttribute( 'aria-invalid', 'true' );
			}
		};

		apply.addEventListener( 'click', function () {
			var value = link.value.trim();
			var parsed = null;
			if ( '' === value ) {
				fail( apply.dataset.required );
				return;
			}
			try {
				parsed = new URL( value );
			} catch ( problem ) {
				parsed = null;
			}
			if ( ! parsed || ( 'http:' !== parsed.protocol && 'https:' !== parsed.protocol ) ) {
				fail( apply.dataset.invalid );
				return;
			}
			fail( '' );
			media.value = '0'; // A pasted link is not a media library item.
			show( value );
		} );

		link.addEventListener( 'input', function () {
			fail( '' );
		} );

		// Lasso's Refresh (C1D, R35): asks Amazon now. The answer is already stored, so it fills the image link, the
		// preview, the read-only price and a placeholder name, and the unsaved-changes guard counts none of them.
		var refresh = document.querySelector( '[data-image-refresh]' );
		if ( refresh && window.wp && window.wp.apiFetch ) {
			refresh.addEventListener( 'click', function () {
				refresh.disabled = true;
				refresh.classList.add( 'is-busy' );
				fail( '' );
				window.wp.apiFetch( { path: '/affibloom/v1/products/' + refresh.dataset.product + '/amazon/refresh', method: 'POST' } )
					.then( function ( answer ) {
						var name = document.getElementById( 'affibloom-name' );
						var price = document.getElementById( 'affibloom-price' );
						var title = document.getElementById( 'affibloom-product-title' );
						link.value = answer.data.image_url;
						media.value = '0';
						show( answer.data.image_url );
						var saved = [ 'image_url', 'image_id' ];
						// Amazon's bullets replace the Features list, stored at once (C1Q4).
						var list = document.querySelector( '[data-feature-list]' );
						if ( list && list.affibloomFeatures && answer.data.features && answer.data.features.length ) {
							list.affibloomFeatures.set( answer.data.features );
							saved.push( 'features' );
						}
						// Shown until reload: the product page's price is C1F's (it shows Amazon's stored price, Phase 3).
						if ( price ) {
							price.value = answer.data.price;
						}
						// The server renamed a placeholder name (PlaceholderName, R35). The field takes the new name only while
						// it still holds the name the page loaded with, so a name typed and not yet saved is kept.
						if ( answer.data.renamed && name && name.value === name.defaultValue ) {
							name.value = answer.data.name;
							name.defaultValue = answer.data.name;
							if ( title ) {
								title.textContent = answer.data.name;
							}
							saved.push( 'name' );
						}
						link.form.dispatchEvent( new CustomEvent( 'affibloom:fields-saved', { detail: { names: saved } } ) );
					} )
					.catch( function ( error ) {
						fail( error && error.message && error.code && 0 === error.code.indexOf( 'affibloom_' ) ? error.message : refresh.dataset.failed );
					} )
					.finally( function () {
						refresh.disabled = false;
						refresh.classList.remove( 'is-busy' );
					} );
			} );
		}

		if ( window.affibloomMedia.available() ) {
			choose.hidden = false;
			choose.addEventListener( 'click', function () {
				// Lasso's media frame: "Select an Image", images only, one, "Select Image" (admin.js).
				window.affibloomMedia.pick( { title: choose.dataset.mediaTitle, button: choose.dataset.mediaButton }, function ( image ) {
					link.value = image.url;
					media.value = String( image.id );
					fail( '' );
					show( image.url );
					link.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				} );
			} );
		}
	} );
} )();
