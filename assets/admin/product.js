/* global wp */
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

	document.addEventListener( 'DOMContentLoaded', function () {
		var kit = document.querySelector( '[data-share-kit]' );
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
		var picker = null;

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

		if ( window.wp && wp.media ) {
			choose.hidden = false;
			choose.addEventListener( 'click', function () {
				if ( ! picker ) {
					// Lasso's media frame: "Select an Image", images only, one, "Select Image".
					picker = wp.media( {
						title: choose.dataset.mediaTitle,
						multiple: false,
						library: { type: 'image' },
						button: { text: choose.dataset.mediaButton },
					} );
					picker.on( 'select', function () {
						var image = picker.state().get( 'selection' ).first().toJSON();
						link.value = image.url;
						media.value = String( image.id );
						fail( '' );
						show( image.url );
						link.dispatchEvent( new Event( 'input', { bubbles: true } ) );
					} );
				}
				picker.open();
			} );
		}
	} );
} )();
