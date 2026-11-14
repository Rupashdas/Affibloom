/**
 * The group picker (F23 D8; Task G3): chips for the chosen groups, a box that filters the site's groups, Enter or the
 * Create option for a new name, × or Backspace to drop one. Used on the product page, the Dashboard's quick edit and the
 * bulk Add to Group. The markup is partials/group-picker.php; the site's groups are the page's
 * `affibloom-group-choices` JSON, which this file keeps up to date when a group is made here.
 *
 * Each picker answers `values()` with what the server's `groups` field takes: a group's ID (a number) for a group that
 * exists, the typed name (a string) for a new one. It announces every change with a bubbling `affibloom:change`.
 */
( function () {
	'use strict';

	var store = document.getElementById( 'affibloom-group-choices' );
	var choices = [];
	try {
		choices = store ? JSON.parse( store.textContent ) : [];
	} catch ( error ) {
		choices = [];
	}

	function same( a, b ) {
		return a.trim().toLowerCase() === b.trim().toLowerCase();
	}

	function fill( template, name ) {
		return template.replace( '%s', name );
	}

	function setup( root ) {
		if ( root.affibloomPicker ) {
			return;
		}
		var chips = root.querySelector( '[data-picker-chips]' );
		var entry = chips.querySelector( '.affibloom-picker__entry' );
		var input = root.querySelector( '.affibloom-picker__input' );
		var list = root.querySelector( '.affibloom-picker__list' );
		var options = [];
		var active = -1;
		var seq = 0;

		function chipList() {
			return Array.prototype.slice.call( chips.querySelectorAll( '.affibloom-picker__chip' ) );
		}

		function changed() {
			input.placeholder = 0 === chipList().length ? root.dataset.placeholder : '';
			root.dispatchEvent( new CustomEvent( 'affibloom:change', { bubbles: true } ) );
		}

		function has( name ) {
			return chipList().some( function ( chip ) {
				return same( chip.dataset.name, name );
			} );
		}

		function addChip( value, name ) {
			var chip = document.createElement( 'li' );
			chip.className = 'affibloom-picker__chip';
			chip.dataset.value = String( value );
			chip.dataset.name = name;
			var text = document.createElement( 'span' );
			text.textContent = name;
			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.className = 'affibloom-picker__remove';
			remove.setAttribute( 'aria-label', fill( root.dataset.removeLabel, name ) );
			remove.innerHTML = '<svg class="affibloom-icon" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M5.5 5.5l9 9M14.5 5.5l-9 9"/></svg>';
			chip.appendChild( text );
			chip.appendChild( remove );
			chips.insertBefore( chip, entry );
		}

		function close() {
			list.hidden = true;
			input.setAttribute( 'aria-expanded', 'false' );
			input.removeAttribute( 'aria-activedescendant' );
			active = -1;
		}

		function highlight( index ) {
			active = index;
			Array.prototype.forEach.call( list.children, function ( item, at ) {
				item.setAttribute( 'aria-selected', at === index ? 'true' : 'false' );
				item.classList.toggle( 'is-active', at === index );
			} );
			if ( index >= 0 ) {
				input.setAttribute( 'aria-activedescendant', list.children[ index ].id );
				list.children[ index ].scrollIntoView( { block: 'nearest' } );
			} else {
				input.removeAttribute( 'aria-activedescendant' );
			}
		}

		function pick( option ) {
			addChip( option.value, option.name );
			input.value = '';
			changed();
			render();
		}

		/** The listbox for what is typed: groups not yet chosen, then Create when the text is no group's name. */
		function render() {
			var typed = input.value.trim();
			options = choices.filter( function ( choice ) {
				return ! has( choice.name ) && ( '' === typed || choice.name.toLowerCase().indexOf( typed.toLowerCase() ) !== -1 );
			} ).map( function ( choice ) {
				return { value: choice.id, name: choice.name, create: false };
			} );
			var exists = choices.some( function ( choice ) {
				return same( choice.name, typed );
			} ) || has( typed );
			if ( '' !== typed && ! exists ) {
				options.push( { value: typed, name: typed, create: true } );
			}
			seq += 1;
			list.innerHTML = '';
			options.forEach( function ( option, at ) {
				var item = document.createElement( 'li' );
				item.id = list.id + '-' + seq + '-' + at;
				item.setAttribute( 'role', 'option' );
				item.setAttribute( 'aria-selected', 'false' );
				item.className = 'affibloom-picker__option';
				item.textContent = option.create ? fill( root.dataset.createLabel, option.name ) : option.name;
				// mousedown, not click: the box must not lose the focus first.
				item.addEventListener( 'mousedown', function ( event ) {
					event.preventDefault();
					pick( option );
				} );
				list.appendChild( item );
			} );
			list.hidden = 0 === options.length;
			input.setAttribute( 'aria-expanded', options.length > 0 ? 'true' : 'false' );
			highlight( -1 );
		}

		input.addEventListener( 'input', render );
		input.addEventListener( 'focus', render );
		input.addEventListener( 'blur', close );
		input.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowDown' === event.key || 'ArrowUp' === event.key ) {
				event.preventDefault();
				if ( list.hidden ) {
					render();
				}
				if ( options.length > 0 ) {
					highlight( ( active + ( 'ArrowDown' === event.key ? 1 : -1 ) + options.length ) % options.length );
				}
			} else if ( 'Enter' === event.key ) {
				// Enter picks the highlighted choice, or the typed name; it never submits the form around the picker.
				event.preventDefault();
				var typed = input.value.trim();
				if ( active >= 0 ) {
					pick( options[ active ] );
				} else if ( '' !== typed ) {
					var found = choices.filter( function ( choice ) {
						return same( choice.name, typed );
					} )[ 0 ];
					if ( ! has( typed ) ) {
						pick( found ? { value: found.id, name: found.name } : { value: typed, name: typed } );
					} else {
						input.value = '';
						render();
					}
				}
			} else if ( 'Escape' === event.key && ! list.hidden ) {
				event.stopPropagation();
				close();
			} else if ( 'Backspace' === event.key && '' === input.value ) {
				var last = chipList().pop();
				if ( last ) {
					last.remove();
					changed();
					render();
				}
			}
		} );
		chips.addEventListener( 'click', function ( event ) {
			var remove = event.target.closest( '.affibloom-picker__remove' );
			if ( remove ) {
				remove.closest( '.affibloom-picker__chip' ).remove();
				changed();
				input.focus();
			} else if ( event.target === chips || event.target === entry ) {
				input.focus();
			}
		} );

		root.affibloomPicker = {
			/** The chosen groups as the server takes them: an ID for a group that exists, else the name. */
			values: function () {
				return chipList().map( function ( chip ) {
					return /^\d+$/.test( chip.dataset.value ) ? parseInt( chip.dataset.value, 10 ) : chip.dataset.value;
				} );
			},
			/** Shows exactly these groups ({ id, name }), as the server stored them, and learns the new ones. */
			set: function ( groups ) {
				chipList().forEach( function ( chip ) {
					chip.remove();
				} );
				groups.forEach( function ( group ) {
					addChip( group.id, group.name );
					if ( ! choices.some( function ( choice ) {
						return choice.id === group.id;
					} ) ) {
						choices.push( { id: group.id, name: group.name } );
					}
				} );
				input.placeholder = 0 === groups.length ? root.dataset.placeholder : '';
			},
			clear: function () {
				chipList().forEach( function ( chip ) {
					chip.remove();
				} );
				input.value = '';
				input.placeholder = root.dataset.placeholder;
			},
		};
	}

	window.affibloomGroupPicker = {
		init: function ( scope ) {
			Array.prototype.forEach.call( ( scope || document ).querySelectorAll( '[data-group-picker]' ), setup );
		},
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		window.affibloomGroupPicker.init( document );
	} );
	// Content added after load (a quick edit's form) announces itself, as the help tips do.
	document.addEventListener( 'affibloom:enhance', function ( event ) {
		window.affibloomGroupPicker.init( event.target );
	} );
}() );
