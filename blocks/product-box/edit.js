/**
 * The block in the editor (F4, Tasks 23A, 23C): Lasso's panel (R5) in Meadow's look (ADR-8). Empty, it asks for a
 * display and offers **Add a Display**, which opens the display modal at "Choose a Display Type" (R9). While the
 * modal's script loads the button reads "Loading…" and is disabled; if it failed, "Retry Add a Display" loads it again
 * (R6). With a display, the block shows its preview, drawn by the server as the published page will draw it
 * (POST /editor/preview, R32-R33), and the button becomes "Select a New Display" (R4).
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Button, Spinner } from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import CustomizeDisplay from './customize';
import SchemaPanel from './schema';
import ShortcodeBox from './shortcode-box';
import { FlowerIcon } from './flower';
import { loadModal, useModalState } from './modal-loader';
import { queuePreview } from './preview-queue';

/**
 * Every attribute at its default: a new display replaces the old one and its overrides, as Lasso's insert does. An
 * object default (the overrides' {}) is left unset: a new object is not the schema's, so WordPress would store it.
 */
const DEFAULTS = Object.fromEntries(
	Object.entries( metadata.attributes ).map( ( [ name, spec ] ) => [
		name,
		typeof spec.default === 'object' ? undefined : spec.default,
	] )
);

/**
 * Edit Display's tab (R51, D9): one at a time, by its name; when it has been closed, the editor's next focus draws every
 * block's preview again, so a change made there shows here.
 */
let editTab = null;
const REDRAW = 'affibloom:redraw-previews';
window.addEventListener( 'focus', () => {
	if ( editTab && editTab.closed ) {
		editTab = null;
		window.dispatchEvent( new window.CustomEvent( REDRAW ) );
	}
} );

/**
 * Opens a display's product page in its tab.
 *
 * @param {string} url The page.
 */
const openEditTab = ( url ) => {
	editTab = window.open( url, 'affibloom-edit-display' );
};

/**
 * Whether the block names a display: a product, or a group for a grid, list or gallery (Tasks 23C, 23D).
 *
 * @param {Object} attributes The block's attributes.
 * @return {boolean} True when there is something to preview.
 */
const namesDisplay = ( attributes ) =>
	attributes.productId > 0 || attributes.groupId > 0;

/**
 * The display's preview from the server, with its shortcode text (Task 24C); the latest answer wins. A new token asks
 * again with the same attributes (Update Display, R7).
 *
 * The request waits its turn in the queue (Task 24E, R52).
 *
 * @param {string} key        The block's client ID.
 * @param {Object} attributes The block's attributes.
 * @param {number} token      Bumped to draw again.
 * @return {{state: string, html: string, shortcode: string}} 'idle', 'loading', 'ready' or 'error', the HTML, the text.
 */
function usePreview( key, attributes, token ) {
	const [ preview, setPreview ] = useState( {
		state: 'idle',
		html: '',
		shortcode: '',
	} );
	const asking = JSON.stringify( attributes );
	useEffect( () => {
		const asked = JSON.parse( asking );
		if ( ! namesDisplay( asked ) ) {
			setPreview( { state: 'idle', html: '', shortcode: '' } );
			return undefined;
		}
		let current = true;
		setPreview( ( previous ) => ( { ...previous, state: 'loading' } ) );
		queuePreview( key, asked )
			.then( ( answer ) => {
				if ( current ) {
					setPreview( {
						state: 'ready',
						html: answer.data.html,
						shortcode: answer.data.shortcode,
						edit_url: answer.data.edit_url,
					} );
					// Others that read the drawn displays, such as Rank Math's analysis (Task ED3), redo it.
					doAction( 'affibloom.previewRendered', key );
				}
			} )
			.catch( ( failure ) => {
				if ( current && ! ( failure && failure.superseded ) ) {
					setPreview( ( previous ) => ( {
						...previous,
						state: 'error',
						html: '',
					} ) );
				}
			} );
		return () => {
			current = false;
		};
	}, [ key, asking, token ] );
	return preview;
}

export default function Edit( { attributes, setAttributes, clientId } ) {
	const state = useModalState();
	const button = useRef();
	const blockProps = useBlockProps( { className: 'affibloom-block' } );
	const [ token, setToken ] = useState( 0 );
	const [ broken, setBroken ] = useState( false );
	const preview = usePreview( clientId, attributes, token );
	useEffect( () => {
		const redraw = () => setToken( ( current ) => current + 1 );
		window.addEventListener( REDRAW, redraw );
		return () => window.removeEventListener( REDRAW, redraw );
	}, [] );
	const hasDisplay = namesDisplay( attributes );

	const ready = 'ready' === state;
	const failed = 'error' === state;
	let label = __( 'Loading…', 'affibloom' );
	if ( ready ) {
		label = hasDisplay
			? __( 'Select a New Display', 'affibloom' )
			: __( 'Add a Display', 'affibloom' );
	} else if ( failed ) {
		label = __( 'Retry Add a Display', 'affibloom' );
	}

	const onClick = () => {
		if ( failed ) {
			loadModal();
			return;
		}
		window.affibloomDisplayModal.open( {
			opener: button.current,
			// A step's Add (23B-23F) hands the display's attributes here.
			insert: ( chosen ) => setAttributes( { ...DEFAULTS, ...chosen } ),
		} );
	};

	return (
		<div { ...blockProps }>
			{ hasDisplay && (
				<CustomizeDisplay
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			) }
			{ hasDisplay && (
				<SchemaPanel
					attributes={ attributes }
					setAttributes={ setAttributes }
					clientId={ clientId }
				/>
			) }
			<div
				className={
					hasDisplay
						? 'affibloom-block__panel has-display'
						: 'affibloom-block__panel'
				}
			>
				{ hasDisplay && (
					<div className="affibloom-block__stage">
						{ 'loading' === preview.state && ! preview.html && (
							<div
								className="affibloom-block__skeleton"
								aria-hidden="true"
							/>
						) }
						{ '' !== preview.html && ! broken && (
							<RawHTML className="affibloom-block__preview">
								{ preview.html }
							</RawHTML>
						) }
						{ 'ready' === preview.state && '' === preview.html && (
							<p className="affibloom-block__prompt">
								{ __(
									'This display has nothing to show: its product is gone or not published.',
									'affibloom'
								) }
							</p>
						) }
						{ 'error' === preview.state && (
							<p className="affibloom-block__prompt">
								{ __(
									'The preview could not be loaded.',
									'affibloom'
								) }
							</p>
						) }
					</div>
				) }
				{ ! hasDisplay && (
					<>
						<span className="affibloom-block__mark">
							{ FlowerIcon }
						</span>
						<p className="affibloom-block__prompt">
							{ __( 'Choose a Link to display.', 'affibloom' ) }
						</p>
					</>
				) }
				<div className="affibloom-block__actions">
					{ hasDisplay && '' !== preview.shortcode && (
						<>
							<ShortcodeBox
								key={ token }
								shortcode={ preview.shortcode }
								onRead={ ( display ) =>
									setAttributes( { ...DEFAULTS, ...display } )
								}
								onBroken={ setBroken }
							/>
							{ /* Lasso's Update Display (R7): the preview drawn again. */ }
							<Button
								variant="secondary"
								className="affibloom-block__update"
								onClick={ () => {
									setBroken( false );
									setToken( token + 1 );
								} }
							>
								{ __( 'Update Display', 'affibloom' ) }
							</Button>
						</>
					) }
					{ hasDisplay && preview.edit_url && (
						<Button
							variant="secondary"
							className="affibloom-block__update"
							onClick={ () => openEditTab( preview.edit_url ) }
						>
							{ __( 'Edit Display', 'affibloom' ) }
						</Button>
					) }
					<Button
						ref={ button }
						variant={ hasDisplay ? 'secondary' : 'primary' }
						className={
							failed
								? 'affibloom-block__add is-retry'
								: 'affibloom-block__add'
						}
						disabled={ ! ready && ! failed }
						onClick={ onClick }
					>
						{ ! ready && ! failed && <Spinner /> }
						{ label }
					</Button>
				</div>
			</div>
		</div>
	);
}
