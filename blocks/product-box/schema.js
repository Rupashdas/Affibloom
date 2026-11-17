/**
 * The sidebar's **Schema & Structured Data** (F4 R43-R47, Task 24B), for a Single display; closed at first. It sets the
 * block's schema attributes, which the page's JSON-LD reads (SchemaMarkup): Review schema, Pro/Con schema (shown only
 * when the product has both Pros and Cons), the price and its currency. The review's author, the rating and the Pros and
 * Cons are shown, not edited. Only one display per post may turn schema on: while another block has it, this panel is
 * disabled (R44, edge case 4). Turning Review schema on for a product without a Primary Rating gives it 3.5 first (R46).
 */
import apiFetch from '@wordpress/api-fetch';
import { InspectorControls } from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * A text field stored when it is left or on Enter; empty means the product's own value.
 *
 * @param {Object}                  props          The field.
 * @param {string}                  props.label    Its label.
 * @param {string}                  props.value    The stored value ('' none).
 * @param {string}                  props.fallback The product's value, shown when nothing is stored.
 * @param {boolean}                 props.disabled Whether it can be changed.
 * @param {(value: string) => void} props.onStore  Stores a value.
 */
function SchemaText( { label, value, fallback, disabled, onStore } ) {
	const [ draft, setDraft ] = useState( value || fallback );
	useEffect( () => setDraft( value || fallback ), [ value, fallback ] );
	const store = () => {
		const typed = draft.replace( /"/g, '' ).trim();
		const next = typed === fallback ? '' : typed;
		if ( next !== value ) {
			onStore( next );
		}
	};
	return (
		<TextControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			value={ draft }
			disabled={ disabled }
			onChange={ setDraft }
			onBlur={ store }
			onKeyDown={ ( event ) => {
				if ( 'Enter' === event.key ) {
					event.preventDefault();
					store();
				}
			} }
		/>
	);
}

export default function SchemaPanel( { attributes, setAttributes, clientId } ) {
	const single = '' === attributes.type || 'single' === attributes.type;
	const [ facts, setFacts ] = useState( null );
	const [ busy, setBusy ] = useState( false );
	const { postId, taken } = useSelect(
		( select ) => {
			const blocks = select( 'core/block-editor' );
			const ids = blocks.getBlocksByName
				? blocks.getBlocksByName( 'affibloom/product-box' )
				: [];
			return {
				postId: select( 'core/editor' ).getCurrentPostId(),
				// Another display in this post already has schema on (R44).
				taken: ids.some( ( id ) => {
					if ( id === clientId ) {
						return false;
					}
					const other = blocks.getBlockAttributes( id ) || {};
					return other.schemaReview || other.schemaProsCons;
				} ),
			};
		},
		[ clientId ]
	);

	useEffect( () => {
		if ( ! single || ! ( attributes.productId > 0 ) ) {
			return undefined;
		}
		let current = true;
		apiFetch( {
			path:
				'/affibloom/v1/editor/schema?product=' +
				attributes.productId +
				'&post=' +
				( postId || 0 ),
		} )
			.then( ( answer ) => current && setFacts( answer.data ) )
			.catch( () => current && setFacts( null ) );
		return () => {
			current = false;
		};
	}, [ single, attributes.productId, postId ] );

	if ( ! single || ! ( attributes.productId > 0 ) ) {
		return null;
	}
	const known = facts || {
		author: '',
		price: '',
		currency: '',
		rating: '',
		pros: [],
		cons: [],
	};
	const disabled = taken || busy;

	const review = ( on ) => {
		if ( ! on || '' !== known.rating ) {
			setAttributes( { schemaReview: on } );
			return;
		}
		setBusy( true );
		apiFetch( {
			path: '/affibloom/v1/editor/schema/rating',
			method: 'POST',
			data: { product: attributes.productId },
		} )
			.then( ( answer ) => {
				setFacts( { ...known, rating: answer.data.rating } );
				setAttributes( { schemaReview: true } );
			} )
			.finally( () => setBusy( false ) );
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Schema & Structured Data', 'affibloom' ) }
				initialOpen={ false }
			>
				<Notice status="info" isDismissible={ false }>
					{ __(
						"Adds Google's product structured data for this display. Only allow one shortcode set of Review and Pro/Con schema per post and just supports for Single Displays.",
						'affibloom'
					) }
				</Notice>
				{ taken && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'Another display in this post already uses schema.',
							'affibloom'
						) }
					</Notice>
				) }
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Review author', 'affibloom' ) }
					value={ known.author }
					disabled
					onChange={ () => {} }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Review schema', 'affibloom' ) }
					checked={ !! attributes.schemaReview }
					disabled={ disabled }
					onChange={ review }
				/>
				<SchemaText
					label={ __( 'Price currency', 'affibloom' ) }
					value={ attributes.schemaPriceCurrency }
					fallback={ known.currency }
					disabled={ disabled }
					onStore={ ( value ) =>
						setAttributes( { schemaPriceCurrency: value } )
					}
				/>
				<SchemaText
					label={ __( 'Price', 'affibloom' ) }
					value={ attributes.schemaPrice }
					fallback={ known.price }
					disabled={ disabled }
					onStore={ ( value ) =>
						setAttributes( { schemaPrice: value } )
					}
				/>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Rating value', 'affibloom' ) }
					value={ known.rating }
					disabled
					onChange={ () => {} }
				/>
				{ known.pros.length > 0 && known.cons.length > 0 && (
					<>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Pro/Con schema', 'affibloom' ) }
							checked={ !! attributes.schemaProsCons }
							disabled={ disabled }
							onChange={ ( on ) =>
								setAttributes( { schemaProsCons: on } )
							}
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Pros', 'affibloom' ) }
							value={ known.pros.join( '\n' ) }
							disabled
							onChange={ () => {} }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Cons', 'affibloom' ) }
							value={ known.cons.join( '\n' ) }
							disabled
							onChange={ () => {} }
						/>
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
}
