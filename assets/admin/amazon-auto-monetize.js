/**
 * Settings → Amazon, "Enable Amazon Auto-Monetization" (F35 R22, D17; Task AM3), as Lasso's settings.js guards it:
 * switching it on with no tracking ID says "Tracking ID must be set to use Auto-Amazon." and turns it back off; with one,
 * Allow Multiple Tracking IDs is switched on if it was off, then "Enable Auto-Amazon" asks: Create a Backup First turns
 * it back off (and Multiple IDs back as it was) and opens WordPress's backup guide; Enable keeps it on. Closing the
 * dialog any other way counts as not enabling. Emptying the tracking ID while it is on runs the check again.
 * Save Changes stores the switch; the server starts or stops the import (AmazonImportCron).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var toggle = document.getElementById( 'affibloom-setting-auto_monetize_amazon' );
		var dialog = document.getElementById( 'affibloom-amazon-enable' );
		var tag = document.getElementById( 'affibloom-setting-tracking_id' );
		var multiple = document.getElementById( 'affibloom-setting-multiple_tracking_ids' );
		var error = document.getElementById( 'affibloom-amazon-auto-error' );
		if ( ! toggle || ! dialog || ! tag || ! error ) {
			return;
		}
		var turnedMultipleOn = false;
		var enabled = false;

		var say = function ( text ) {
			error.textContent = text;
			error.hidden = '' === text;
		};
		var flip = function ( field, on ) {
			field.checked = on;
			field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		};
		var hasTag = function () {
			return '' !== tag.value.trim();
		};
		// Back off, and Multiple IDs as it was before this switch turned it on.
		var undo = function () {
			toggle.checked = false;
			if ( turnedMultipleOn && multiple ) {
				flip( multiple, false );
			}
			turnedMultipleOn = false;
		};

		toggle.addEventListener( 'change', function () {
			if ( ! toggle.checked ) {
				say( '' );
				return;
			}
			if ( ! hasTag() ) {
				toggle.checked = false;
				say( error.getAttribute( 'data-needs-tag' ) );
				return;
			}
			say( '' );
			turnedMultipleOn = false;
			if ( multiple && ! multiple.checked ) {
				flip( multiple, true );
				turnedMultipleOn = true;
			}
			enabled = false;
			window.affibloomModal.show( dialog, toggle );
		} );

		dialog.querySelector( '[data-amazon-enable]' ).addEventListener( 'click', function () {
			enabled = hasTag();
			if ( ! enabled ) {
				undo();
			}
			window.affibloomModal.hide();
		} );
		dialog.querySelector( '[data-amazon-backup]' ).addEventListener( 'click', function () {
			undo();
			window.affibloomModal.hide();
			window.open( dialog.getAttribute( 'data-backup-url' ), '_blank', 'noopener' );
		} );
		dialog.addEventListener( 'affibloom:close', function () {
			if ( ! enabled && toggle.checked ) {
				undo();
			}
			// The form saw the switch move; the guard and Save follow the field's value.
			toggle.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );

		tag.addEventListener( 'input', function () {
			if ( toggle.checked && ! hasTag() ) {
				toggle.checked = false;
				say( error.getAttribute( 'data-needs-tag' ) );
				toggle.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			}
		} );
	} );
}() );
