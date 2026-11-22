/**
 * The sidebar's **Customize Display** (F4 R35-R42, Task 24A): the fields of the block's display type, with Lasso's
 * labels and help texts (EditorAssets prints them: OverrideTexts). A text field is stored when it is left or on Enter,
 * so the preview redraws then (R42); a toggle at once. A cleared field drops its override; a toggle stores only its
 * non-default value: `hide` for price, field and rating, `enable` for disclosure and data-nosnippet (R39). Double quotes
 * are removed and an anchor id's spaces become `-` (R40); the server cleans the same way (OverrideCatalog). A badge or
 * button text has an icon button before its words, which opens the table builder's icon grid (Task 24G).
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	BaseControl,
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { compose, split } from './icon-text';

/**
 * A typed value as it is stored (R40).
 *
 * @param {string} name  The attribute.
 * @param {string} value What was typed.
 * @return {string} The cleaned value.
 */
export const clean = ( name, value ) => {
	const plain = String( value ).replace( /"/g, '' ).trim();
	return 'anchor_id' === name ? plain.replace( /\s+/g, '-' ) : plain;
};

/**
 * The overrides with one field changed; an empty value is no override.
 *
 * @param {Object} overrides The block's overrides.
 * @param {string} name      The attribute.
 * @param {string} value     Its new value ('' to drop it).
 * @return {Object} The new overrides.
 */
export const withOverride = ( overrides, name, value ) => {
	const next = { ...overrides };
	if ( '' === value ) {
		delete next[ name ];
	} else {
		next[ name ] = value;
	}
	return next;
};

/**
 * A text field that keeps what is typed and stores it on leaving or on Enter.
 *
 * @param {Object}                  props         The field.
 * @param {Object}                  props.field   Its catalogue entry.
 * @param {string}                  props.value   The stored value.
 * @param {(value: string) => void} props.onStore Stores a value.
 */
function TextOverride( { field, value, onStore } ) {
	const [ draft, setDraft ] = useState( value );
	useEffect( () => setDraft( value ), [ value ] );
	const store = () => {
		const cleaned = clean( field.name, draft );
		setDraft( cleaned );
		if ( cleaned !== value ) {
			onStore( cleaned );
		}
	};
	const Control = 'textarea' === field.kind ? TextareaControl : TextControl;
	return (
		<Control
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ field.label }
			help={ field.help ? <RawHTML>{ field.help }</RawHTML> : undefined }
			value={ draft }
			onChange={ setDraft }
			onBlur={ store }
			onKeyDown={ ( event ) => {
				if ( 'Enter' === event.key && 'textarea' !== field.kind ) {
					event.preventDefault();
					store();
				}
			} }
		/>
	);
}

/**
 * A badge or button text with its icon (Task 24G): the icon button opens the icon grid, whose choice is stored at
 * once; the words are stored on leaving or on Enter, as a plain text field's.
 *
 * @param {Object}                  props         The field.
 * @param {Object}                  props.field   Its catalogue entry.
 * @param {string}                  props.value   The stored value.
 * @param {(value: string) => void} props.onStore Stores a value.
 */
function IconTextOverride( { field, value, onStore } ) {
	const config = window.affibloomEditor || {};
	const icons = config.icons || [];
	const labels = config.iconLabels || {};
	const text = split( value ) || { icon: '', words: '' };
	const [ draft, setDraft ] = useState( text.words );
	useEffect( () => setDraft( text.words ), [ text.words ] );
	const anchor = useRef();
	const id = useInstanceId( IconTextOverride, 'affibloom-icon-text' );

	const store = ( icon, words ) => {
		const cleaned = clean( field.name, words );
		setDraft( cleaned );
		const next = compose( icon, cleaned );
		if ( next !== value ) {
			onStore( next );
		}
	};
	const icon = icons.find( ( one ) => one.class === text.icon );
	let name = labels.none;
	if ( '' !== text.icon ) {
		name = icon ? icon.name : text.icon;
	}
	let glyph = 'affibloom-icon-text__glyph is-none';
	if ( '' !== text.icon ) {
		glyph =
			icon && icon.src && ! icon.missing
				? 'affibloom-icon-text__glyph'
				: 'affibloom-icon-text__glyph is-unknown';
	}
	const open = () => {
		if ( ! window.affibloomIconPopover ) {
			return;
		}
		window.affibloomIconPopover.open( {
			anchor: anchor.current,
			icons,
			current: text.icon,
			labels,
			canAdd: false,
			choose: ( chosen ) => store( chosen, draft ),
		} );
	};

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id={ id }
			label={ field.label }
			help={ field.help ? <RawHTML>{ field.help }</RawHTML> : undefined }
		>
			<div className="affibloom-icon-text">
				<button
					type="button"
					ref={ anchor }
					className="affibloom-icon-text__icon"
					aria-haspopup="dialog"
					aria-label={ ( labels.button || '%1$s: %2$s' )
						.replace( '%1$s', field.label )
						.replace( '%2$s', name ) }
					title={ name }
					onClick={ open }
				>
					<span className={ glyph }>
						{ icon && icon.src && ! icon.missing ? (
							<img
								src={ icon.src }
								alt=""
								width="16"
								height="16"
							/>
						) : null }
					</span>
				</button>
				<input
					id={ id }
					type="text"
					className="components-text-control__input is-next-40px-default-size affibloom-icon-text__words"
					value={ draft }
					onChange={ ( event ) => setDraft( event.target.value ) }
					onBlur={ () => store( text.icon, draft ) }
					onKeyDown={ ( event ) => {
						if ( 'Enter' === event.key ) {
							event.preventDefault();
							store( text.icon, draft );
						}
					} }
				/>
			</div>
		</BaseControl>
	);
}

/**
 * The panel for one block.
 *
 * @param {Object}                    props               The block.
 * @param {Object}                    props.attributes    Its attributes.
 * @param {(changes: Object) => void} props.setAttributes Stores changes.
 */
export default function CustomizeDisplay( { attributes, setAttributes } ) {
	const catalog =
		( window.affibloomEditor && window.affibloomEditor.overrides ) || {};
	const fields = catalog[ attributes.type || 'single' ] || [];
	const overrides = attributes.overrides || {};
	if ( ! fields.length ) {
		return null;
	}
	// No overrides left: the attribute is unset, so the stored block stays bare.
	const store = ( name, value ) => {
		const next = withOverride( overrides, name, value );
		setAttributes( {
			overrides: Object.keys( next ).length ? next : undefined,
		} );
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Customize Display', 'affibloom' ) }
				initialOpen
			>
				{ fields.map( ( field ) => {
					const value = overrides[ field.name ] || '';
					if ( 'toggle' === field.kind ) {
						const checked = field.on
							? 'hide' !== value
							: 'enable' === value;
						return (
							<ToggleControl
								__nextHasNoMarginBottom
								key={ field.name }
								label={ field.label }
								checked={ checked }
								onChange={ ( on ) => {
									let stored = on ? 'enable' : '';
									if ( field.on ) {
										stored = on ? '' : 'hide';
									}
									store( field.name, stored );
								} }
							/>
						);
					}
					// A text holding other HTML than an icon keeps the plain field (F28 D3).
					if ( field.icon && null !== split( value ) ) {
						return (
							<IconTextOverride
								key={ field.name }
								field={ field }
								value={ value }
								onStore={ ( stored ) =>
									store( field.name, stored )
								}
							/>
						);
					}
					return (
						<TextOverride
							key={ field.name }
							field={ field }
							value={ value }
							onStore={ ( stored ) =>
								store( field.name, stored )
							}
						/>
					);
				} ) }
			</PanelBody>
		</InspectorControls>
	);
}
