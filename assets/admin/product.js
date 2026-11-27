/**
 * The product page's image (F1, Task 14B). "Update Image" opens WordPress's media library for one image, which fills
 * the image link, the media item and the preview. Apply checks a pasted link as Lasso's upload_thumbnail does ("Image
 * URL is required.", "Invalid image url.", no data: links) and shows it. Neither stores anything: Save Changes does
 * (Task 15; F1 requirements D2), where Lasso's Apply wrote the link at once.
 */
( function () {
	'use strict';

	/**
	 * Lasso 370's Share kit (Task 14D): each button copies its snippet from the page as it is now and says "Copied!" for
	 * 1.5 seconds; its name stays the action. The shareable URL is the public link under the heading (F24 R14), the
	 * destination URL the Primary Destination URL as typed.
	 *
	 * @param {HTMLElement} kit The Share kit card.
	 */
	function shareKit( kit ) {
		var snippet = function ( target ) {
			var destination = document.getElementById( 'affibloom-url' );
			var disclosure = document.getElementById( 'affibloom-disclosure' );
			if ( 'public' === target ) {
				var line = document.querySelector( '.affibloom-product-url' );
				return line ? line.getAttribute( 'href' ) || '' : '';
			}
			if ( 'destination' === target ) {
				return destination ? destination.value.trim() : '';
			}
			if ( 'shortcode' === target || 'block' === target ) {
				return kit.dataset[ target ] || '';
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
			window.affibloomCopy( text, function () {
				label.textContent = kit.dataset.copied;
				window.setTimeout( function () {
					label.textContent = original;
				}, 1500 );
			} );
		} );
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
			window.affibloomEditorPlaceholder( editor );
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

	/**
	 * Permalink (F24 R8, D10; Task CL3): as Lasso's, what is typed becomes its slug form (anything that is not a letter
	 * or digit one hyphen, no hyphens at the ends once the field is left); while Link Cloaking is on, the line under the heading shows the
	 * address it will have, prefix included. The line's link changes only with the save.
	 *
	 * @param {HTMLFormElement} form The product form.
	 */
	function permalink( form ) {
		var input = document.getElementById( 'affibloom-permalink' );
		var cloak = document.getElementById( 'affibloom-cloaking' );
		var line = document.querySelector( '.affibloom-product-url' );
		if ( ! input || ! line || ! form.dataset.cloakBase ) {
			return;
		}
		var show = function () {
			if ( cloak && cloak.checked && '' !== input.value ) {
				line.textContent = form.dataset.cloakBase + input.value.toLowerCase().replace( /-+$/, '' ) + '/';
			}
		};
		// While typing, a separator becomes a hyphen at once, so "Air Cat" can be typed; leaving the field takes the
		// hyphens off its ends (Lasso trims on every key, which swallows a space before it can become a hyphen).
		var tidy = function ( ends ) {
			var slug = input.value.replace( /[\W_]+/g, '-' ).replace( ends ? /^-+|-+$/g : /^-+/g, '' );
			if ( slug !== input.value ) {
				input.value = slug;
			}
			show();
		};
		input.addEventListener( 'input', function () {
			tidy( false );
		} );
		input.addEventListener( 'change', function () {
			tidy( true );
		} );
		if ( cloak ) {
			cloak.addEventListener( 'change', show );
		}
	}

	/**
	 * The head follows the form while it is edited (the editor's Quick Detail, Task 23G): the name as it is typed and the
	 * picture as it is chosen reach the title and the image tile at once, as they reach the live preview. A save still
	 * writes the stored values over them (product-save.js).
	 *
	 * @param {HTMLFormElement} form The product form.
	 */
	function headEcho( form ) {
		var title = document.getElementById( 'affibloom-product-title' );
		var name = document.getElementById( 'affibloom-name' );
		var image = document.querySelector( '[data-head-image]' );
		var none = document.querySelector( '[data-head-image-none]' );
		var link = document.querySelector( '[data-image-url]' );

		if ( title && name ) {
			name.addEventListener( 'input', function () {
				var typed = name.value.trim();
				title.textContent = '' !== typed ? typed : form.dataset.emptyName;
				title.title = title.textContent; // The head cuts a long name to two lines (MD5).
			} );
		}

		// Every way to change the picture (Apply, the media library, Amazon's Refresh) ends in this event.
		if ( image && none && link ) {
			document.addEventListener( 'affibloom:image-changed', function () {
				var src = link.value.trim();
				image.hidden = '' === src;
				none.hidden = '' !== src;
				if ( '' !== src ) {
					image.src = src;
				}
			} );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var features = document.querySelector( '[data-features]' );
		var productForm = document.getElementById( 'affibloom-product-form' );
		if ( productForm ) {
			permalink( productForm );
			headEcho( productForm );
		}
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
		var yes = offer && offer.querySelector( '[data-tracking-save]' );
		if ( no ) {
			no.hidden = false;
			no.addEventListener( 'click', function () {
				offer.hidden = true;
			} );
		}
		// "Yes" saves the link's tag as the site's tracking ID over REST and stays on the page (deviation 143): the
		// offer goes once it is saved; a tag the Amazon tab's rule refuses keeps it, with the reason as the toast.
		if ( yes && window.wp && window.wp.apiFetch ) {
			yes.addEventListener( 'click', function () {
				yes.disabled = true;
				window.wp.apiFetch( { path: '/affibloom/v1/settings/tracking-id', method: 'POST', data: { tracking_id: yes.dataset.trackingSave } } )
					.then( function ( answer ) {
						window.affibloomToast( answer.data.message, !! answer.data.failed );
						// Saved: the site has a tracking ID now, so the offer is gone, as on the next load.
						if ( ! answer.data.failed ) {
							offer.remove();
						}
					} )
					.catch( function () {
						window.affibloomToast( yes.dataset.failedText, true );
					} )
					.finally( function () {
						yes.disabled = false;
					} );
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
			// The live preview (Task 14E) redraws with the new picture.
			frame.dispatchEvent( new window.CustomEvent( 'affibloom:image-changed', { bubbles: true } ) );
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

	/**
	 * The live preview (F5 R100-R103, F1 B2-B4; Task 14E): the product's real box, drawn by the preview route from what
	 * Save would send (product-save.js), in the image frame. Every change redraws it (the latest answer wins), a theme
	 * change included, without saving; a click on the box's picture opens the media library, as "Update Image" does.
	 *
	 * @param {HTMLFormElement} form  The product form.
	 * @param {HTMLElement}     frame The image frame.
	 */
	function boxPreview( form, frame ) {
		var latest = 0;
		var timer = null;
		var choose = frame.querySelector( '[data-image-choose]' );

		var draw = function () {
			var ask = ++latest;
			var body = window.affibloomProductPayload ? window.affibloomProductPayload() : {};
			frame.classList.add( 'is-loading' );
			window.wp.apiFetch( { path: '/affibloom/v1/products/' + form.dataset.productId + '/preview', method: 'POST', data: body } ).then( function ( answer ) {
				if ( ask !== latest ) {
					return;
				}
				Array.prototype.forEach.call( frame.querySelectorAll( '[data-image-preview], [data-image-empty], .affibloom-image-frame__note' ), function ( element ) {
					element.hidden = true;
				} );
				frame.classList.add( 'has-box' );
				window.affibloomBoxFrame( frame, answer, frame.dataset.previewTitle, function () {
					frame.classList.remove( 'is-loading' );
				}, choose ? function () {
					choose.click();
				} : null );
			} ).catch( function () {
				if ( ask === latest ) {
					frame.classList.remove( 'is-loading' );
				}
			} );
		};

		var soon = function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( draw, 250 );
		};

		form.addEventListener( 'input', soon );
		form.addEventListener( 'change', soon );
		frame.addEventListener( 'affibloom:image-changed', soon );
		if ( window.tinymce ) {
			var follow = function ( editor ) {
				editor.on( 'keyup change', soon );
			};
			window.tinymce.editors.forEach( follow );
			window.tinymce.on( 'AddEditor', function ( event ) {
				follow( event.editor );
			} );
		}
		// After product-save.js has set up the payload.
		soon();
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'affibloom-product-form' );
		var frame = document.querySelector( '[data-image-frame]' );
		if ( form && frame && form.dataset.productId && window.wp && window.wp.apiFetch && window.affibloomBoxFrame ) {
			boxPreview( form, frame );
		} else if ( frame ) {
			// The frame is printed loading; with nothing to draw it, the image and the note show.
			frame.classList.remove( 'is-loading' );
		}
	} );
} )();
