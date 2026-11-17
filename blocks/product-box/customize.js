/**
 * The sidebar's **Customize Display** (F4 R35-R42, Task 24A): the fields of the block's display type, with Lasso's
 * labels and help texts (EditorAssets prints them: OverrideTexts). A text field is stored when it is left or on Enter,
 * so the preview redraws then (R42); a toggle at once. A cleared field drops its override; a toggle stores only its
 * non-default value: `hide` for price, field and rating, `enable` for disclosure and data-nosnippet (R39). Double quotes
 * are removed and an anchor id's spaces become `-` (R40); the server cleans the same way (OverrideCatalog).
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
