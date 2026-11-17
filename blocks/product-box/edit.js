/**
 * The block in the editor (F4, Tasks 23A, 23C): Lasso's panel (R5) in Meadow's look (ADR-8). Empty, it asks for a
 * display and offers **Add a Display**, which opens the display modal at "Choose a Display Type" (R9). While the
 * modal's script loads the button reads "Loading…" and is disabled; if it failed, "Retry Add a Display" loads it again
 * (R6). With a display, the block shows its preview, drawn by the server as the published page will draw it
 * (POST /editor/preview, R32-R33), and the button becomes "Select a New Display" (R4).
 */
import apiFetch from '@wordpress/api-fetch';
import { useBlockProps } from '@wordpress/block-editor';
import { Button, Spinner } from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import CustomizeDisplay from './customize';
import SchemaPanel from './schema';
import { FlowerIcon } from './flower';
import { loadModal, useModalState } from './modal-loader';

/** Every attribute at its default: a new display replaces the old one and its overrides, as Lasso's insert does. */
const DEFAULTS = Object.fromEntries(
	Object.entries( metadata.attributes ).map( ( [ name, spec ] ) => [
		name,
		spec.default,
	] )
);

/**
 * Whether the block names a display: a product, or a group for a grid, list or gallery (Tasks 23C, 23D).
 *
 * @param {Object} attributes The block's attributes.
 * @return {boolean} True when there is something to preview.
 */
const namesDisplay = ( attributes ) =>
	attributes.productId > 0 || attributes.groupId > 0;

/**
 * The display's preview from the server; the latest answer wins.
 *
 * @param {Object} attributes The block's attributes.
 * @return {{state: string, html: string}} 'idle', 'loading', 'ready' or 'error', and the HTML.
 */
function usePreview( attributes ) {
	const [ preview, setPreview ] = useState( { state: 'idle', html: '' } );
	const key = JSON.stringify( attributes );
	useEffect( () => {
		const asked = JSON.parse( key );
		if ( ! namesDisplay( asked ) ) {
			setPreview( { state: 'idle', html: '' } );
			return undefined;
		}
		let current = true;
		setPreview( ( previous ) => ( { ...previous, state: 'loading' } ) );
		apiFetch( {
			path: '/affibloom/v1/editor/preview',
			method: 'POST',
			data: { attributes: asked },
		} )
			.then( ( answer ) => {
				if ( current ) {
					setPreview( { state: 'ready', html: answer.data.html } );
				}
			} )
			.catch( () => {
				if ( current ) {
					setPreview( { state: 'error', html: '' } );
				}
			} );
		return () => {
			current = false;
		};
	}, [ key ] );
	return preview;
}

export default function Edit( { attributes, setAttributes, clientId } ) {
	const state = useModalState();
	const button = useRef();
	const blockProps = useBlockProps( { className: 'affibloom-block' } );
	const preview = usePreview( attributes );
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
						{ '' !== preview.html && (
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
	);
}
