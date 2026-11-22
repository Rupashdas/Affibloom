/* global wp */
/**
 * The icon picker for badge and button texts in the table builder (F28's library; deviation 151) and the block's
 * Customize Display (Task 24G, with canAdd off): a popover under its
 * button with a search box and a grid of the library's icons (picture and name), "No icon" first and the current one
 * marked. Typing filters by name; the arrow keys move through the grid, Enter or a click chooses, Escape or a click
 * outside closes and gives the focus back. For those who may change settings, "Add icon" picks an SVG, PNG or WebP from
 * the media library and adds it to the library (POST /icons), then chooses it.
 *
 * window.affibloomIconPopover.open( {
 *   anchor:  the button it opens from;
 *   icons:   the library, [ { class, name, src, missing } ] (changed in place when an icon is added);
 *   current: the class chosen now ('' none);
 *   labels:  { dialog, search, none, empty, add, media, mediaButton, failed };
 *   canAdd:  whether "Add icon" shows;
 *   choose:  fn( css_class ) — '' for no icon.
 * } )
 */
( function () {
	'use strict';

	var open = null;

	var close = function ( giveBack ) {
		if ( ! open ) {
			return;
		}
		var was = open;
		open = null;
		was.popover.remove();
		document.removeEventListener( 'pointerdown', was.outside, true );
		window.removeEventListener( 'resize', was.place );
		window.removeEventListener( 'scroll', was.place, true );
		if ( giveBack ) {
			was.options.anchor.focus();
		}
	};

	var glyph = function ( icon ) {
		var span = document.createElement( 'span' );
		span.className = 'affibloom-icon-popover__picture';
		if ( icon && icon.src && ! icon.missing ) {
			var img = document.createElement( 'img' );
			img.src = icon.src;
			img.alt = '';
			img.width = 20;
			img.height = 20;
			span.appendChild( img );
		} else {
			span.classList.add( 'is-none' );
		}
		return span;
	};

	var build = function ( options ) {
		var labels = options.labels;
		var popover = document.createElement( 'div' );
		popover.className = 'affibloom-icon-popover';
		popover.setAttribute( 'role', 'dialog' );
		popover.setAttribute( 'aria-label', labels.dialog );

		var search = document.createElement( 'input' );
		search.type = 'search';
		search.className = 'affibloom-input affibloom-icon-popover__search';
		search.placeholder = labels.search;
		search.setAttribute( 'aria-label', labels.search );
		popover.appendChild( search );

		var grid = document.createElement( 'div' );
		grid.className = 'affibloom-icon-popover__grid';
		grid.setAttribute( 'role', 'listbox' );
		grid.setAttribute( 'aria-label', labels.dialog );
		popover.appendChild( grid );

		var empty = document.createElement( 'p' );
		empty.className = 'affibloom-icon-popover__empty';
		empty.textContent = labels.empty;
		empty.hidden = true;
		popover.appendChild( empty );

		var option = function ( cssClass, name, icon ) {
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'affibloom-icon-popover__option';
			button.setAttribute( 'role', 'option' );
			button.setAttribute( 'aria-selected', cssClass === options.current ? 'true' : 'false' );
			button.tabIndex = -1;
			button.dataset.icon = cssClass;
			button.dataset.name = name.toLowerCase();
			button.title = name;
			button.appendChild( glyph( icon ) );
			var text = document.createElement( 'span' );
			text.className = 'affibloom-icon-popover__name';
			text.textContent = name;
			button.appendChild( text );
			return button;
		};

		var draw = function () {
			grid.innerHTML = '';
			grid.appendChild( option( '', labels.none, null ) );
			options.icons.forEach( function ( icon ) {
				grid.appendChild( option( icon.class, icon.name, icon ) );
			} );
		};
		draw();

		var visible = function () {
			return Array.prototype.filter.call( grid.children, function ( button ) {
				return ! button.hidden;
			} );
		};
		var filter = function () {
			var words = search.value.trim().toLowerCase().split( /\s+/ ).filter( Boolean );
			Array.prototype.forEach.call( grid.children, function ( button ) {
				button.hidden = '' !== button.dataset.icon && ! words.every( function ( word ) {
					return -1 !== button.dataset.name.indexOf( word );
				} );
			} );
			empty.hidden = visible().length > 1 || 0 === words.length;
		};
		search.addEventListener( 'input', filter );

		var columns = function () {
			var shown = visible();
			if ( shown.length < 2 ) {
				return 1;
			}
			var top = shown[ 0 ].offsetTop;
			var count = 0;
			shown.forEach( function ( button ) {
				if ( button.offsetTop === top ) {
					count++;
				}
			} );
			return Math.max( 1, count );
		};
		var focusOption = function ( button ) {
			if ( button ) {
				button.focus();
			}
		};

		grid.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[role="option"]' );
			if ( button ) {
				options.choose( button.dataset.icon );
				close( true );
			}
		} );
		popover.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				event.stopPropagation();
				close( true );
				return;
			}
			var shown = visible();
			var at = shown.indexOf( document.activeElement );
			var step = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: columns(), ArrowUp: -columns() }[ event.key ];
			if ( undefined !== step ) {
				if ( document.activeElement === search && 'ArrowDown' !== event.key ) {
					return;
				}
				event.preventDefault();
				if ( -1 === at ) {
					focusOption( shown[ 0 ] );
					return;
				}
				if ( at + step < 0 ) {
					search.focus();
					return;
				}
				focusOption( shown[ Math.min( shown.length - 1, at + step ) ] );
				return;
			}
			if ( 'Enter' === event.key && document.activeElement === search ) {
				event.preventDefault();
				var first = shown.filter( function ( button ) {
					return '' !== button.dataset.icon;
				} )[ 0 ];
				if ( first && '' !== search.value.trim() ) {
					first.click();
				}
			}
		} );

		if ( options.canAdd && window.affibloomMedia && window.affibloomMedia.available() ) {
			var add = document.createElement( 'button' );
			add.type = 'button';
			add.className = 'affibloom-button affibloom-button--small affibloom-button--secondary affibloom-icon-popover__add';
			add.textContent = labels.add;
			add.addEventListener( 'click', function () {
				var keep = options;
				close( false );
				window.affibloomMedia.pick( { title: labels.media, button: labels.mediaButton, types: [ 'image/svg+xml', 'image/png', 'image/webp' ] }, function ( picked ) {
					var name = ( picked.title || picked.filename || 'Icon' ).toString().slice( 0, 40 );
					wp.apiFetch( { path: '/affibloom/v1/icons', method: 'POST', data: { name: name, attachment: picked.id } } ).then( function ( answer ) {
						if ( answer.data.failed || ! answer.data.class ) {
							window.affibloomToast( answer.data.message || labels.failed, true );
							return;
						}
						keep.icons.push( { class: answer.data.class, name: name, src: picked.url, missing: false } );
						keep.choose( answer.data.class );
						window.affibloomToast( answer.data.message, false );
					} ).catch( function () {
						window.affibloomToast( labels.failed, true );
					} );
				} );
			} );
			popover.appendChild( add );
		}

		return { popover: popover, search: search, grid: grid };
	};

	window.affibloomIconPopover = {
		open: function ( options ) {
			var again = open && open.options.anchor === options.anchor;
			close( false );
			if ( again ) {
				return;
			}
			var parts = build( options );
			// Inside the admin wrapper, for its colours and fonts; outside one (the block editor's sidebar, Task 24G) in a
			// wrapper of its own that carries them.
			var host = options.anchor.closest( '.affibloom-admin' ) || document.querySelector( '.affibloom-icon-popover-host' );
			if ( ! host ) {
				host = document.createElement( 'div' );
				host.className = 'affibloom-admin affibloom-icon-popover-host';
				document.body.appendChild( host );
			}
			host.appendChild( parts.popover );
			var place = function () {
				var box = options.anchor.getBoundingClientRect();
				var height = parts.popover.offsetHeight;
				var below = box.bottom + 6 + height <= window.innerHeight;
				parts.popover.style.top = ( below ? box.bottom + 6 : Math.max( 8, box.top - 6 - height ) ) + 'px';
				parts.popover.style.left = Math.max( 8, Math.min( box.left, window.innerWidth - parts.popover.offsetWidth - 8 ) ) + 'px';
			};
			var outside = function ( event ) {
				if ( ! parts.popover.contains( event.target ) && ! options.anchor.contains( event.target ) ) {
					close( false );
				}
			};
			open = { popover: parts.popover, options: options, place: place, outside: outside };
			place();
			document.addEventListener( 'pointerdown', outside, true );
			window.addEventListener( 'resize', place );
			window.addEventListener( 'scroll', place, true );
			var chosen = parts.grid.querySelector( '[aria-selected="true"]' );
			( chosen && options.icons.length < 12 ? chosen : parts.search ).focus();
		},
		close: function () {
			close( false );
		},
	};
}() );
