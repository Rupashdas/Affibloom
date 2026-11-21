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
 * fields (they save themselves, D3). A save that turns the product into an Amazon one, or the reverse, reloads the page
 * with the toast, because the Amazon-only rows differ (Task 15B, D18). A Primary URL another product already has is
 * not saved: the dialog turns into Lasso's "This Product Already Exists", whose Confirm retires this product in favour
 * of that one and opens its page (Task 15C, S10, D14); Cancel (or Escape) closes it and keeps the typed link.
 *
 * Unsaved changes (Task 15D, B8, S16; U7 parity): the form's state is kept as a snapshot when the page has loaded and
 * after each save. A click on a link that would leave the page while it differs opens Lasso's "Unsaved Changes" (Leave
 * Without Saving goes; Save Changes saves, then goes; × stays), and reloading or closing the tab gets the browser's
 * own question. Custom field rows save themselves (D3), so they are not in the snapshot.
 *
 * The save bar (Meadow, Task MD5): the form carries data-dirty="true" while the guard would count a change, so the bar
 * says "Unsaved changes"; it is checked after every edit (fields, the rich editors, the image, a button or feature
 * added, moved or removed) and cleared by a save.
 */
( function () {
	'use strict';

	var TEXTS = [ 'name', 'url', 'button_text', 'badge', 'disclosure', 'theme', 'image_url', 'permalink' ];
	// is_opportunity: Detect Opportunities (F22 R38, D9; Task O5), the switch's field name as Lasso's.
	var SWITCHES = [ 'new_tab', 'nofollow', 'sponsored', 'show_disclosure', 'prime', 'show_price', 'show_features', 'link_cloaking', 'is_opportunity' ];
	var toast = window.affibloomToast;
	var dialog = window.affibloomSaveDialog;
	var errors = window.affibloomFieldErrors;

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
		var modal = dialog.modal();
		var unsaved = document.querySelector( '[data-unsaved-modal]' );
		var baseline = null;
		var leaving = false;
		var afterSave = null;
		var title = document.getElementById( 'affibloom-product-title' );
		var linkLine = document.querySelector( '.affibloom-product-url' );
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
			// The group picker (Task G3): IDs of groups, names for new ones; the server stores exactly these.
			var picker = form.querySelector( '[data-group-picker]' );
			if ( picker && picker.affibloomPicker ) {
				data.groups = picker.affibloomPicker.values();
			}
			data.image_id = parseInt( field( 'image_id' ).value, 10 ) || 0;
			data.primary_position = parseInt( field( 'primary_position' ).value, 10 ) || 0;
			// The Features list (C1Q4): every bullet's text, in order; the server drops empty ones.
			data.features = Array.prototype.map.call( form.querySelectorAll( '[data-feature-text]' ), function ( input ) {
				return input.value;
			} );
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

		// The live preview (Task 14E) draws exactly what Save would send.
		window.affibloomProductPayload = collect;

		// The form as it would be sent; equal to the baseline means nothing to lose.
		var snapshot = function () {
			return JSON.stringify( collect() );
		};
		var hasChanges = function () {
			return null !== baseline && snapshot() !== baseline;
		};
		// The save bar's "Unsaved changes" reads this (admin.css).
		var markDirty = function () {
			if ( hasChanges() ) {
				form.dataset.dirty = 'true';
			} else {
				delete form.dataset.dirty;
			}
		};
		// After the event's own handlers have run: a button or a feature row added, moved or removed changes the form then.
		var markSoon = function () {
			window.setTimeout( markDirty, 0 );
		};
		var keepBaseline = function () {
			baseline = snapshot();
			markDirty();
		};
		// Fields a control stored by itself (Refresh, C1D): what they hold now is what is stored, so only they move to the
		// baseline; any other change still counts.
		form.addEventListener( 'affibloom:fields-saved', function ( event ) {
			if ( null === baseline ) {
				return;
			}
			var kept = JSON.parse( baseline );
			var now = collect();
			event.detail.names.forEach( function ( name ) {
				if ( Object.prototype.hasOwnProperty.call( now, name ) ) {
					kept[ name ] = now[ name ];
				}
			} );
			baseline = JSON.stringify( kept );
			markDirty();
		} );
		[ 'input', 'change', 'click', 'drop', 'keyup' ].forEach( function ( type ) {
			form.addEventListener( type, markSoon );
		} );
		document.addEventListener( 'affibloom:image-changed', markSoon );
		if ( window.tinymce ) {
			var follow = function ( editor ) {
				editor.on( 'input keyup change undo redo', markSoon );
			};
			window.tinymce.editors.forEach( follow );
			window.tinymce.on( 'AddEditor', function ( event ) {
				follow( event.editor );
			} );
		}
		var go = function ( href ) {
			leaving = true;
			window.location.assign( href );
		};

		var clearErrors = function () {
			errors.clear( form );
		};

		// The page as stored (S15): the heading, the name, the links, the image and the price. The line under the heading
		// is the public link (F24 R9, R14), and the Permalink the slug WordPress stored (made unique).
		var showStored = function ( data, sent ) {
			title.textContent = '' !== data.name ? data.name : form.dataset.emptyName;
			title.title = title.textContent; // The head cuts a long name to two lines (MD5).
			field( 'name' ).value = data.name;
			field( 'url' ).value = data.url;
			if ( field( 'permalink' ) ) {
				field( 'permalink' ).value = data.slug;
			}
			linkLine.textContent = data.public_link;
			linkLine.href = data.public_link;
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
			// The head's image tile (MD5) shows the stored picture.
			var headImage = document.querySelector( '[data-head-image]' );
			var headNone = document.querySelector( '[data-head-image-none]' );
			if ( headImage && headNone ) {
				headImage.hidden = '' === data.image_url;
				headNone.hidden = '' !== data.image_url;
				if ( '' !== data.image_url ) {
					headImage.src = data.image_url;
				}
			}
			// New names are groups now: the chips show the stored ones, with their IDs.
			var picked = form.querySelector( '[data-group-picker]' );
			if ( picked && picked.affibloomPicker && data.groups ) {
				picked.affibloomPicker.set( data.groups );
			}
			rich.forEach( function ( editor ) {
				editor.saved( Object.prototype.hasOwnProperty.call( sent, editor.name ) ? data[ editor.name ] : null );
			} );
			savedSwitches = switches();
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
			dialog.open( switches() !== savedSwitches ? modal.dataset.textToggles : modal.dataset.textDefault );

			wp.apiFetch( { path: '/affibloom/v1/products/' + form.dataset.productId, method: 'PATCH', data: sent } )
				.then( function ( response ) {
					// The editor's Quick Detail (F4, Task 23G) inserts the product once it is saved.
					form.dispatchEvent( new window.CustomEvent( 'affibloom:product-saved', { bubbles: true, detail: response.data } ) );
					if ( response.data.type !== form.dataset.productType ) {
						var reload = new URL( window.location.href );
						reload.searchParams.delete( 'is_duplicate' );
						reload.searchParams.set( 'affibloom_notice', 'product-saved' );
						go( afterSave || reload.toString() );
						return;
					}
					dialog.close( function () {
						showStored( response.data, sent );
						keepBaseline();
						toast( form.dataset.savedText, false );
						// F24 R5, N7: a post or page with the slug takes its address; the save stands, the editor is told.
						if ( response.warnings && response.warnings.length ) {
							toast( response.warnings[ 0 ].text, true );
						}
						// "Unsaved Changes" → Save Changes: once saved, go where the link led (Lasso's pending action).
						if ( afterSave ) {
							go( afterSave );
						}
					} );
				} )
				.catch( function ( error ) {
					// The Quick Detail's + Add stops waiting; the page shows why, as always.
					form.dispatchEvent( new window.CustomEvent( 'affibloom:product-save-failed', { bubbles: true } ) );
					afterSave = null; // A refused or offered save stays on the page.
					if ( error && 'affibloom_duplicate' === error.code && error.data && error.data.to ) {
						// "This Product Already Exists" (admin.js): Cancel keeps the typed link; Confirm opens the kept product.
						window.affibloomRepoint.offer( parseInt( form.dataset.productId, 10 ), error.data, sent.url, {
							cancelled: function () {
								field( 'url' ).focus();
							},
							leave: go,
							failedText: form.dataset.failedText,
						} );
						return;
					}
					dialog.close( function () {
						var fields = error && error.data && error.data.fields;
						if ( fields ) {
							errors.show( form, fields );
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
					}, dialog.closeAfter );
				} );
		} );

		// The baseline is taken once the editors have loaded their content (Lasso takes it at once and on the next tick
		// for the same reason): TinyMCE starts after DOMContentLoaded.
		var loaded = function () {
			keepBaseline();
			window.setTimeout( keepBaseline, 0 );
		};
		if ( 'complete' === document.readyState ) {
			loaded();
		} else {
			window.addEventListener( 'load', loaded );
		}

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( leaving || saving || ! hasChanges() ) {
				return;
			}
			event.preventDefault();
			event.returnValue = '';
		} );

		// A link that leaves the page: not "#…", "javascript:", a new tab, a modified click, or a link inside a dialog.
		document.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[href]' );
			if ( ! link || event.defaultPrevented || event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
				return;
			}
			var href = link.getAttribute( 'href' );
			if ( '' === href || '#' === href.charAt( 0 ) || 0 === href.indexOf( 'javascript:' ) || '_blank' === link.target || link.closest( '.affibloom-modal' ) || ! hasChanges() ) {
				return;
			}
			event.preventDefault();
			unsaved.dataset.href = link.href;
			window.affibloomModal.show( unsaved, link );
		} );
		unsaved.querySelector( '[data-unsaved-leave]' ).addEventListener( 'click', function () {
			window.affibloomModal.hide();
			go( unsaved.dataset.href );
		} );
		unsaved.querySelector( '[data-unsaved-save]' ).addEventListener( 'click', function () {
			window.affibloomModal.hide();
			afterSave = unsaved.dataset.href;
			form.requestSubmit( save );
		} );

		deleteProduct( form, go );
	}

	/**
	 * Delete This Link (Task 17A; F1 requirements 11, X2-X3, D33, D37, D38): Lasso's "Delete This Link?" dialog, whose
	 * Delete removes the product for good through DELETE /affibloom/v1/products/{id} and opens the Dashboard with
	 * "Product deleted.". The page is left through go(), which quiets the leave guard: the editor has just agreed to
	 * lose the product, edits and all. A failure closes the dialog and shows the server's message in the failure toast.
	 *
	 * @param {HTMLFormElement} form The product form.
	 * @param {Function}        go   Leaves the page without the leave guard asking.
	 */
	function deleteProduct( form, go ) {
		var modal = document.getElementById( 'affibloom-delete-modal' );
		if ( ! modal ) {
			return;
		}
		var confirm = modal.querySelector( '[data-delete-confirm]' );
		var label = confirm.textContent;
		var deleting = false;
		var busy = function ( on ) {
			deleting = on;
			confirm.disabled = on;
			confirm.textContent = on ? confirm.dataset.busyLabel : label;
		};

		confirm.addEventListener( 'click', function () {
			if ( deleting ) {
				return;
			}
			busy( true );
			wp.apiFetch( { path: '/affibloom/v1/products/' + form.dataset.productId, method: 'DELETE' } )
				.then( function () {
					var dashboard = new URL( modal.dataset.dashboardUrl, window.location.href );
					dashboard.searchParams.set( 'affibloom_notice', 'deleted' );
					go( dashboard.toString() );
				} )
				.catch( function ( error ) {
					busy( false );
					window.affibloomModal.hide();
					toast( error && error.message ? error.message : modal.dataset.failedText, true );
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'affibloom-product-form' );
		if ( form && document.querySelector( '[data-save-modal]' ) && document.querySelector( '[data-unsaved-modal]' ) && window.affibloomModal ) {
			productForm( form );
		}
	} );

	// The issue bars' × (Task 31C; F11 R29, D17): the bar goes at once and stays closed for 7 days, also after a reload.
	// A refusal puts it back with the server's message.
	document.addEventListener( 'click', function ( event ) {
		var close = event.target.closest( '[data-alert-dismiss]' );
		if ( ! close ) {
			return;
		}
		var bar = close.closest( '[data-issue-bar]' );
		bar.hidden = true;
		wp.apiFetch( {
			path: '/affibloom/v1/products/' + close.getAttribute( 'data-product-id' ) + '/alerts/dismiss',
			method: 'POST',
			data: { type: close.getAttribute( 'data-alert-dismiss' ) },
		} ).catch( function ( error ) {
			bar.hidden = false;
			window.affibloomToast( error && error.message ? error.message : '', true );
		} );
	} );
} )();
