/* global wp */
/**
 * Save Changes on the product page (F1, Task 15A; requirements 9 S1, S14, S15, D10, D11, D19).
 *
 * Save Changes, or Enter in a text field, sends the form's fields to PATCH /affibloom/v1/products/{id} while Lasso's
 * "Updating Your Site" dialog shows its bar and line. The description and the price are sent only when their editor
 * was changed: WordPress's editor rewrites some stored markup when it opens it (rgb() colours, &nbsp;), so an
 * untouched one must not be sent back (D19); an Amazon product's read-only price is never sent. On success the page
 * shows what was stored (S15) and "Product saved." (U6); a refused field gets Lasso's sentence under it and the
 * failure toast. Not sent: Permalink, Groups, Detect Opportunities and Link Cloaking (placeholders, D4), the custom
 * fields (they save themselves, D3) and, until Task 15B, the Primary URL.
 */
( function () {
	'use strict';

	var TEXTS = [ 'name', 'button_text', 'badge', 'disclosure', 'theme', 'image_url' ];
	var SWITCHES = [ 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'prime', 'show_price' ];
	// Lasso's dialog closes a second after the answer, then the message shows (url-details.php 1980-1985).
	var CLOSE_AFTER = 1000;

	/**
	 * Shows a toast like the server's (partials/toast.php), from the page's template.
	 *
	 * @param {string}  message The words.
	 * @param {boolean} failed  The failure variant, with a cross.
	 */
	function toast( message, failed ) {
		var template = document.getElementById( 'affibloom-toast-template' );
		var host = document.querySelector( '.affibloom-admin' );
		if ( ! template || ! host ) {
			return;
		}
		var element = template.content.firstElementChild.cloneNode( true );
		element.querySelector( '.affibloom-toast__message' ).textContent = message;
		element.querySelector( '[data-toast-icon="success"]' ).hidden = failed;
		element.querySelector( '[data-toast-icon="error"]' ).hidden = ! failed;
		element.classList.toggle( 'affibloom-toast--error', failed );
		host.appendChild( element );
		window.setTimeout( function () {
			element.remove();
		}, 4500 );
	}

	/**
	 * One rich editor (description or price): what it holds, and whether the editor changed it since the last save.
	 *
	 * @param {HTMLFormElement} form The product form.
	 * @param {string}          name The textarea's name.
	 * @param {string}          id   The editor's id (wp_editor's).
	 */
	function richField( form, name, id ) {
		var area = form.querySelector( 'textarea[name="' + name + '"]' );
		if ( ! area ) {
			return null; // An Amazon product's price is a read-only input: never sent.
		}
		var loaded = area.value;
		var editor = function () {
			var instance = window.tinymce ? window.tinymce.get( id ) : null;
			return instance && ! instance.isHidden() ? instance : null;
		};
		return {
			name: name,
			// TinyMCE's own "changed by the editor" flag: comparing its content with what was loaded would count the
			// markup it rewrote on opening as a change.
			changed: function () {
				var instance = editor();
				return instance ? instance.isDirty() : area.value !== loaded;
			},
			value: function () {
				var instance = editor();
				return instance ? instance.getContent() : area.value;
			},
			saved: function ( stored ) {
				var instance = editor();
				if ( 'string' === typeof stored && instance ) {
					instance.setContent( stored );
				} else if ( 'string' === typeof stored ) {
					area.value = stored;
				}
				if ( instance ) {
					instance.setDirty( false );
				}
				loaded = area.value;
			},
		};
	}

	/**
	 * @param {HTMLFormElement} form The product form.
	 */
	function productForm( form ) {
		var save = document.getElementById( 'affibloom-save' );
		var modal = document.querySelector( '[data-save-modal]' );
		var bar = modal.querySelector( '[data-save-bar]' );
		var line = modal.querySelector( '[data-save-text]' );
		var title = document.getElementById( 'affibloom-product-title' );
		var rich = [ richField( form, 'description', 'affibloom_description' ), richField( form, 'price', 'affibloom_price' ) ].filter( Boolean );
		var saving = false;

		var field = function ( name ) {
			var element = form.elements.namedItem( name );
			return element && ! element.disabled ? element : null;
		};

		// The primary button's two switches as last saved: Lasso's "Saving your changes." is for when they changed.
		var switches = function () {
			return [ 'new_tab', 'nofollow' ].map( function ( name ) {
				var element = field( name );
				return element ? element.checked : null;
			} ).join();
		};
		var savedSwitches = switches();

		var extraBlocks = function () {
			return Array.prototype.slice.call( form.querySelectorAll( '[data-extra-button]' ) );
		};
		var part = function ( block, name ) {
			return block.querySelector( '[name$="[' + name + ']"]' );
		};
		var isEmpty = function ( block ) {
			return '' === part( block, 'url' ).value.trim() && '' === part( block, 'text' ).value.trim();
		};

		var collect = function () {
			var data = {};
			TEXTS.forEach( function ( name ) {
				var element = field( name );
				if ( element ) {
					data[ name ] = element.value;
				}
			} );
			SWITCHES.forEach( function ( name ) {
				var element = field( name );
				if ( element && 'checkbox' === element.type ) {
					data[ name ] = element.checked;
				}
			} );
			data.image_id = parseInt( field( 'image_id' ).value, 10 ) || 0;
			data.primary_position = parseInt( field( 'primary_position' ).value, 10 ) || 0;
			data.buttons = extraBlocks().map( function ( block ) {
				return {
					url: part( block, 'url' ).value,
					text: part( block, 'text' ).value,
					new_tab: part( block, 'new_tab' ).checked,
					nofollow: part( block, 'nofollow' ).checked,
				};
			} );
			rich.forEach( function ( editor ) {
				if ( editor.changed() ) {
					data[ editor.name ] = editor.value();
				}
			} );
			return data;
		};

		var clearErrors = function () {
			Array.prototype.forEach.call( form.querySelectorAll( '[data-save-error]' ), function ( note ) {
				var input = document.getElementById( note.dataset.saveError );
				if ( input ) {
					input.classList.remove( 'is-invalid' );
					input.removeAttribute( 'aria-invalid' );
					input.setAttribute( 'aria-describedby', ( input.getAttribute( 'aria-describedby' ) || '' ).replace( ' ' + note.id, '' ) );
				}
				note.remove();
			} );
		};

		// Lasso's sentence under each refused field, in the design system's field error (D11).
		var showErrors = function ( fields ) {
			var first = null;
			Object.keys( fields ).forEach( function ( name ) {
				var input = field( name );
				if ( ! input || ! input.id ) {
					return;
				}
				var note = document.createElement( 'p' );
				note.className = 'affibloom-field__error';
				note.id = input.id + '-save-error';
				note.dataset.saveError = input.id;
				note.textContent = fields[ name ];
				( input.closest( '.affibloom-field' ) || input.parentNode ).appendChild( note );
				input.classList.add( 'is-invalid' );
				input.setAttribute( 'aria-invalid', 'true' );
				input.setAttribute( 'aria-describedby', ( ( input.getAttribute( 'aria-describedby' ) || '' ) + ' ' + note.id ).trim() );
				first = first || input;
			} );
			if ( first ) {
				first.focus();
			}
		};

		// The page as stored (S15): the heading, the name, every button link, the image and the price.
		var showStored = function ( data, sent ) {
			title.textContent = '' !== data.name ? data.name : form.dataset.emptyName;
			field( 'name' ).value = data.name;
			// Blocks left empty were not stored (Buttons' rule); the others line up with the stored list.
			extraBlocks().filter( function ( block ) {
				return ! isEmpty( block );
			} ).forEach( function ( block, index ) {
				if ( data.buttons[ index ] ) {
					part( block, 'url' ).value = data.buttons[ index ].url;
				}
			} );
			field( 'image_url' ).value = data.image_url;
			field( 'image_id' ).value = String( data.image_id );
			rich.forEach( function ( editor ) {
				editor.saved( Object.prototype.hasOwnProperty.call( sent, editor.name ) ? data[ editor.name ] : null );
			} );
			savedSwitches = switches();
		};

		var openDialog = function () {
			line.textContent = switches() !== savedSwitches ? modal.dataset.textToggles : modal.dataset.textDefault;
			bar.style.width = '0';
			modal.hidden = false;
			document.body.classList.add( 'affibloom-has-modal' );
			modal.querySelector( '[role="dialog"]' ).focus();
			window.requestAnimationFrame( function () {
				bar.style.width = '90%';
			} );
		};

		var closeDialog = function ( then ) {
			bar.style.width = '100%';
			window.setTimeout( function () {
				modal.hidden = true;
				document.body.classList.remove( 'affibloom-has-modal' );
				then();
			}, CLOSE_AFTER );
		};

		form.addEventListener( 'input', function ( event ) {
			// A refused field's note goes as soon as it is edited.
			var note = event.target.id ? document.getElementById( event.target.id + '-save-error' ) : null;
			if ( note ) {
				clearErrors();
			}
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( saving ) {
				return;
			}
			saving = true;
			save.disabled = true;
			clearErrors();
			var sent = collect();
			openDialog();

			wp.apiFetch( { path: '/affibloom/v1/products/' + form.dataset.productId, method: 'PATCH', data: sent } )
				.then( function ( response ) {
					closeDialog( function () {
						showStored( response.data, sent );
						toast( form.dataset.savedText, false );
					} );
				} )
				.catch( function ( error ) {
					closeDialog( function () {
						var fields = error && error.data && error.data.fields;
						if ( fields ) {
							showErrors( fields );
							toast( form.dataset.failedText, true );
						} else {
							toast( error && error.message ? error.message : form.dataset.failedText, true );
						}
					} );
				} )
				.finally( function () {
					window.setTimeout( function () {
						saving = false;
						save.disabled = false;
					}, CLOSE_AFTER );
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'affibloom-product-form' );
		if ( form && document.querySelector( '[data-save-modal]' ) ) {
			productForm( form );
		}
	} );
} )();
