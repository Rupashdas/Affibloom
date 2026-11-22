/**
 * The Elementor widget "Affibloom" in Elementor's editor (F4 R67-R69, Task ED2). It runs in the editor's window; the
 * widget's panel (ElementorDisplayWidget::content_template) is drawn in the canvas frame, whose buttons it answers:
 *
 * - Add a Display / Select a New Display opens the block's display modal (loaded once, with the block's "Loading…" and
 *   "Retry Add a Display" states); its Add sets the shortcode, the type and that type's controls at their defaults
 *   through Elementor's settings command, so the panel, undo and saving see it.
 * - The preview: the shortcode read as the block's attributes (POST /editor/shortcode), then drawn through the block's
 *   preview queue (POST /editor/preview); Update Display draws it again, Edit Display opens the product page and draws
 *   it again when the editor gets the focus back.
 * - An edited override control rewrites the shortcode (R67): the shortcode is read, the edited fields set, and the
 *   preview's answer gives the new shortcode, as Lasso's customize_shortcode keeps every other attribute.
 */
import apiFetch from '@wordpress/api-fetch';
import { queuePreview } from '../../blocks/product-box/preview-queue';
import { compose, split } from '../../blocks/product-box/icon-text';

const NAME = 'affibloom';
const SHORTCODE = 'affibloom_shortcode';
const TYPE = 'affibloom_type';
const config = window.affibloomEditor || {};
const catalog = config.overrides || {};
const words = config.elementor || {};

/** The modal's script: idle, loading, ready or error. */
let modal = window.affibloomDisplayModal ? 'ready' : 'idle';
/** Per widget: the shortcode last drawn and the preview's answer, so a re-render draws it at once. */
const drawn = new Map();
/** Per widget: the shortcode being drawn now. */
const drawing = new Map();
/** Per widget: the override controls edited since the shortcode was last rewritten. */
const edited = new Map();
const timers = new Map();
const drawTimers = new Map();
/** True while this script changes settings, so its own changes are not taken for the user's edits. */
let applying = false;
let editTab = null;
let editing = '';

const canvas = () =>
	window.elementor.$previewContents && window.elementor.$previewContents[ 0 ];

const elementOf = ( id ) => {
	const doc = canvas();
	return doc
		? doc.querySelector( `.elementor-element[data-id="${ id }"]` )
		: null;
};

const settingOf = ( id, name ) => {
	const container = window.elementor.getContainer( id );
	return container ? container.settings.get( name ) || '' : '';
};

/**
 * Sets a widget's settings through Elementor's command; `external` redraws the panel's controls with the new values.
 *
 * @param {string} id       The widget.
 * @param {Object} settings The settings to set.
 */
function apply( id, settings ) {
	const container = window.elementor.getContainer( id );
	if ( ! container ) {
		return;
	}
	applying = true;
	try {
		window.$e.run( 'document/elements/settings', {
			container,
			settings,
			options: { external: true },
		} );
	} finally {
		applying = false;
	}
}

/**
 * A type's controls at their defaults: a new display replaces the old one and its overrides, as the block's insert does.
 *
 * @param {string} type The display type.
 * @return {Object} The settings.
 */
function defaults( type ) {
	const settings = {};
	( catalog[ type ] || [] ).forEach( ( field ) => {
		settings[ `${ field.name }_${ type }` ] =
			'toggle' === field.kind && field.on ? 'yes' : '';
		if ( field.icon ) {
			settings[ `${ field.name }_icon_${ type }` ] = '';
		}
	} );
	return settings;
}

/** Every Add button in the canvas, in the modal's state. */
function showModalState() {
	const doc = canvas();
	if ( ! doc ) {
		return;
	}
	doc.querySelectorAll( '[data-affibloom-action="add"]' ).forEach(
		( button ) => {
			let text = words.loading;
			if ( 'ready' === modal ) {
				text = button.dataset.affibloomLabel;
			} else if ( 'error' === modal ) {
				text = words.retry;
			}
			button.textContent = text;
			button.disabled = 'loading' === modal || 'idle' === modal;
			button.classList.toggle( 'is-retry', 'error' === modal );
		}
	);
}

/** Loads the display modal's script once; "Retry Add a Display" after a failure. */
function loadModal() {
	if ( 'loading' === modal || 'ready' === modal ) {
		return;
	}
	modal = 'loading';
	showModalState();
	const script = document.createElement( 'script' );
	script.src = config.modalScript;
	script.onload = () => {
		modal = window.affibloomDisplayModal ? 'ready' : 'error';
		showModalState();
	};
	script.onerror = () => {
		script.remove();
		modal = 'error';
		showModalState();
	};
	document.head.appendChild( script );
}

/**
 * Fills a widget's preview place with an answer, or a message.
 *
 * @param {string}  id      The widget.
 * @param {Object=} data    The preview's answer: html, edit_url.
 * @param {string=} message A message in its place.
 */
function fill( id, data, message ) {
	const element = elementOf( id );
	const stage =
		element && element.querySelector( '[data-affibloom-preview]' );
	if ( ! stage ) {
		return;
	}
	const html = data ? data.html : '';
	stage.innerHTML = '';
	if ( html ) {
		const preview = document.createElement( 'div' );
		preview.className = 'affibloom-elementor__preview';
		preview.innerHTML = html;
		stage.appendChild( preview );
	} else {
		const prompt = document.createElement( 'p' );
		prompt.className = 'affibloom-elementor__prompt';
		prompt.textContent = message || words.empty;
		stage.appendChild( prompt );
	}
	const edit = element.querySelector( '[data-affibloom-action="edit"]' );
	if ( edit ) {
		edit.hidden = ! ( data && data.edit_url );
		edit.dataset.affibloomUrl = data && data.edit_url ? data.edit_url : '';
	}
}

/**
 * The preview's place while it loads: the block's grey shape.
 *
 * @param {string} id The widget.
 */
function skeleton( id ) {
	const element = elementOf( id );
	const stage =
		element && element.querySelector( '[data-affibloom-preview]' );
	if ( stage && ! stage.firstChild ) {
		stage.innerHTML =
			'<div class="affibloom-elementor__skeleton" aria-hidden="true"></div>';
	}
}

/**
 * A widget drawn by Elementor (each change re-renders its panel): the last answer at once when its shortcode is the
 * same, else the preview a moment after the last key, so typing in the shortcode does not ask for every letter.
 *
 * @param {string} id The widget.
 */
function soon( id ) {
	const known = drawn.get( id );
	if ( known && known.shortcode === settingOf( id, SHORTCODE ) ) {
		fill( id, known.data );
		return;
	}
	skeleton( id );
	clearTimeout( drawTimers.get( id ) );
	drawTimers.set(
		id,
		setTimeout( () => draw( id, false ), 300 )
	);
}

/**
 * Reads a shortcode as the block's attributes.
 *
 * @param {string} text The shortcode.
 * @return {Promise<Object>} type, productId, groupId, overrides.
 */
const read = ( text ) =>
	apiFetch( {
		path: '/affibloom/v1/editor/shortcode',
		method: 'POST',
		data: { text },
	} ).then( ( answer ) => answer.data );

/**
 * Draws a widget's preview from its shortcode; a re-render with the same shortcode reuses the last answer.
 *
 * @param {string}  id    The widget.
 * @param {boolean} fresh Draw again even when the shortcode did not change.
 */
function draw( id, fresh ) {
	const shortcode = settingOf( id, SHORTCODE );
	if ( ! shortcode ) {
		return;
	}
	const known = drawn.get( id );
	if ( ! fresh && known && known.shortcode === shortcode ) {
		fill( id, known.data );
		return;
	}
	if ( ! fresh && drawing.get( id ) === shortcode ) {
		return;
	}
	drawing.set( id, shortcode );
	skeleton( id );
	read( shortcode )
		.then( ( attributes ) => {
			// The type's controls show (R67); the type needs no redraw.
			if ( settingOf( id, TYPE ) !== attributes.type ) {
				apply( id, { [ TYPE ]: attributes.type } );
			}
			return queuePreview( id, attributes );
		} )
		.then(
			( answer ) => {
				if ( settingOf( id, SHORTCODE ) !== shortcode ) {
					return;
				}
				drawn.set( id, { shortcode, data: answer.data } );
				fill( id, answer.data );
			},
			( error ) => {
				if ( error && error.superseded ) {
					return;
				}
				if ( settingOf( id, SHORTCODE ) === shortcode ) {
					drawn.delete( id );
					fill(
						id,
						null,
						error && 'affibloom_invalid_display' === error.code
							? error.message
							: words.failed
					);
				}
			}
		)
		.finally( () => {
			if ( drawing.get( id ) === shortcode ) {
				drawing.delete( id );
			}
		} );
}

/**
 * The modal's Add: the display's shortcode, its type and the type's controls at their defaults.
 *
 * @param {string}      id     The widget.
 * @param {HTMLElement} button The button that opened the modal, which gets the focus back.
 */
function choose( id, button ) {
	if ( 'error' === modal ) {
		loadModal();
		return;
	}
	if ( 'ready' !== modal ) {
		return;
	}
	window.affibloomDisplayModal.open( {
		opener: button,
		insert( attributes ) {
			queuePreview( id, attributes ).then(
				( answer ) => {
					const type = attributes.type || 'single';
					drawn.set( id, {
						shortcode: answer.data.shortcode,
						data: answer.data,
					} );
					apply( id, {
						...defaults( type ),
						[ SHORTCODE ]: answer.data.shortcode,
						[ TYPE ]: type,
					} );
				},
				() => {}
			);
		},
	} );
}

/**
 * A toggle's or a text's control value as the block's override.
 *
 * @param {Object} field The field (OverrideTexts::catalog).
 * @param {string} value The control's value; a switcher's is "yes" or "".
 * @return {string} The override; "" is the default.
 */
function override( field, value ) {
	if ( 'toggle' !== field.kind ) {
		return value || '';
	}
	if ( field.on ) {
		return 'yes' === value ? '' : 'hide';
	}
	return 'yes' === value ? 'enable' : '';
}

/**
 * Rewrites a widget's shortcode with its edited controls (R67).
 *
 * @param {string} id The widget.
 */
function rewrite( id ) {
	const shortcode = settingOf( id, SHORTCODE );
	const names = edited.get( id ) || new Set();
	edited.delete( id );
	if ( ! shortcode || ! names.size ) {
		return;
	}
	read( shortcode )
		.then( ( attributes ) => {
			const overrides = { ...attributes.overrides };
			( catalog[ attributes.type ] || [] ).forEach( ( field ) => {
				const name = `${ field.name }_${ attributes.type }`;
				const iconName = `${ field.name }_icon_${ attributes.type }`;
				if (
					field.icon &&
					( names.has( name ) || names.has( iconName ) )
				) {
					// Task 24G: the icon dropdown's class before the words. The controls are not read back from the
					// shortcode, so a part not edited and empty keeps what the shortcode has.
					const kept = split( overrides[ field.name ] ) || {
						icon: '',
						words: '',
					};
					let icon = settingOf( id, iconName );
					let text = settingOf( id, name );
					if ( ! names.has( iconName ) && '' === icon ) {
						icon = kept.icon;
					}
					if ( ! names.has( name ) && '' === text ) {
						text = kept.words;
					}
					overrides[ field.name ] = compose( icon, text );
				} else if ( names.has( name ) ) {
					overrides[ field.name ] = override(
						field,
						settingOf( id, name )
					);
				}
			} );
			return queuePreview( id, { ...attributes, overrides } );
		} )
		.then(
			( answer ) => {
				drawn.set( id, {
					shortcode: answer.data.shortcode,
					data: answer.data,
				} );
				apply( id, { [ SHORTCODE ]: answer.data.shortcode } );
			},
			() => {}
		);
}

/**
 * Follows a widget's settings: an override control's edit rewrites the shortcode, a few hundred ms after the last key.
 *
 * @param {Object} model The widget's model (Elementor's panel hook).
 */
function follow( model ) {
	const settings = model.get( 'settings' );
	if ( settings.affibloomFollowed ) {
		return;
	}
	settings.affibloomFollowed = true;
	settings.on( 'change', () => {
		if ( applying ) {
			return;
		}
		const names = Object.keys( settings.changed ).filter(
			( name ) => SHORTCODE !== name && TYPE !== name
		);
		if ( ! names.length ) {
			return;
		}
		const id = model.get( 'id' );
		const pending = edited.get( id ) || new Set();
		names.forEach( ( name ) => pending.add( name ) );
		edited.set( id, pending );
		clearTimeout( timers.get( id ) );
		timers.set(
			id,
			setTimeout( () => rewrite( id ), 400 )
		);
	} );
}

/**
 * The canvas's buttons: Add a Display, Update Display, Edit Display.
 *
 * @param {MouseEvent} event The click in the canvas frame.
 */
function onCanvasClick( event ) {
	const button = event.target.closest( '[data-affibloom-action]' );
	const element = button && button.closest( '.elementor-element' );
	if ( ! element ) {
		return;
	}
	event.preventDefault();
	const id = element.dataset.id;
	const action = button.dataset.affibloomAction;
	if ( 'add' === action ) {
		choose( id, button );
	} else if ( 'update' === action ) {
		draw( id, true );
	} else if ( 'edit' === action && button.dataset.affibloomUrl ) {
		editTab = window.open(
			button.dataset.affibloomUrl,
			'affibloom-edit-display'
		);
		editing = id;
	}
}

// Edit Display's tab closed: the next focus draws that widget again, so a change made there shows here.
window.addEventListener( 'focus', () => {
	if ( editTab && editTab.closed ) {
		editTab = null;
		draw( editing, true );
	}
} );

window.jQuery( window ).on( 'elementor:init', () => {
	const elementor = window.elementor;
	loadModal();
	elementor.on( 'frontend:init', () => {
		window.elementorFrontend.hooks.addAction(
			`frontend/element_ready/${ NAME }.default`,
			( $scope ) => {
				showModalState();
				soon( $scope.data( 'id' ) );
			}
		);
	} );
	// The canvas's buttons, before Elementor's own handlers take the click for selecting the widget. A document that
	// is reloaded (another page, a template) is listened to again; one listener per document.
	const listen = () => {
		const doc = canvas();
		if ( doc && ! doc.affibloomListening ) {
			doc.affibloomListening = true;
			doc.addEventListener( 'click', onCanvasClick, true );
		}
	};
	elementor.on( 'preview:loaded', listen );
	listen();
	elementor.hooks.addAction(
		`panel/open_editor/widget/${ NAME }`,
		( panel, model ) => follow( model )
	);
} );
