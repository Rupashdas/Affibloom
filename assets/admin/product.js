/* global wp */
/**
 * The product page's image (F1, Task 14B). "Update Image" opens WordPress's media library for one image, which fills
 * the image link, the media item and the preview. Apply checks a pasted link as Lasso's upload_thumbnail does ("Image
 * URL is required.", "Invalid image url.", no data: links) and shows it. Neither stores anything: Save Changes does
 * (Task 15; F1 requirements D2), where Lasso's Apply wrote the link at once.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
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
