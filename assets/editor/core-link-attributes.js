/**
 * Keeps the link attributes Lasso stamped into core and GenerateBlocks blocks valid in the block editor (F4 R80-R84,
 * N3; Task ED5). 147 posts carry `data-lasso-id`, `data-lasso-name` and `data-lasso-lid` on the links of image,
 * GenerateBlocks button and button blocks (and list items' links); their blocks' save functions do not write those
 * attributes, so without these filters the editor finds the stored HTML different from the save output and marks the
 * blocks invalid. The filters only make the save output match what is stored: no content changes unless the editor
 * saves (R84). The attribute names stay Lasso's, as the posts store them.
 *
 * The logic is Lasso 369's (admin/assets/js/lasso-gutenberg-block.js 296-411, 1912-1931, 2103-2364, 2539-2831,
 * 2947-3240, 3404-3444; GPL v2 or later), without its Stackable parts (Stackable is not on the site and no post holds a
 * Stackable block) and its `data-dev` copy of the id (no stored post has one, so writing it would break the match). While Lasso's own filters are registered (Lasso still active), these step aside, so the blocks are
 * processed once.
 */
( function () {
	'use strict';

	const wp = window.wp;
	const LASSO_NAMESPACE = 'affiliate-plugin/lasso';
	const NAMESPACE = 'affibloom/core-link-attributes';

	/**
	 * Whether Lasso's editor script has registered its own filters.
	 *
	 * @return {boolean} True while Lasso is active in the editor.
	 */
	const lassoFilters = function () {
		return wp.hooks.hasFilter( 'blocks.getSaveElement', LASSO_NAMESPACE );
	};

	/** The anchor attributes a link carries (R80). */
	const anchorAttributes = function () {
		const read = function ( attribute ) {
			return {
				attribute,
				selector: 'a',
				source: 'attribute',
				type: 'string',
			};
		};
		return {
			'data-lasso-id': read( 'data-lasso-id' ),
			'data-lasso-name': read( 'data-lasso-name' ),
			'data-lasso-lid': read( 'data-lasso-lid' ),
			rel: read( 'rel' ),
			target: read( 'target' ),
		};
	};

	/** Every link of an image block, with its own attributes. */
	const allLinksAttribute = function () {
		const read = function ( attribute ) {
			return { type: 'string', source: 'attribute', attribute };
		};
		return {
			'all-links': {
				selector: 'a',
				source: 'query',
				type: 'array',
				query: {
					data_lasso_id: read( 'data-lasso-id' ),
					data_lasso_name: read( 'data-lasso-name' ),
					data_lasso_lid: read( 'data-lasso-lid' ),
					href: read( 'href' ),
					target: read( 'target' ),
					rel: read( 'rel' ),
				},
			},
		};
	};

	const isButtonBlock = function ( name ) {
		return (
			name === 'core/button' || name.includes( 'generateblocks/button' )
		);
	};

	/**
	 * The attributes a block gains: an image's link, links and image; a button's link.
	 *
	 * @param {string} name The block's name.
	 * @return {Object} Attribute definitions.
	 */
	const extraAttributes = function ( name ) {
		if ( name === 'core/image' ) {
			return Object.assign( {}, anchorAttributes(), allLinksAttribute(), {
				'data-lasso-id-img': {
					attribute: 'data-lasso-id',
					selector: 'img',
					source: 'attribute',
					type: 'string',
				},
			} );
		}
		return isButtonBlock( name ) ? anchorAttributes() : {};
	};

	const escapeRegExp = function ( text ) {
		return text.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	};

	/**
	 * A quoted attribute's value in stored HTML.
	 *
	 * @param {string} html      The block's stored HTML.
	 * @param {string} attribute The attribute, e.g. "data-lasso-id" or "rel".
	 * @return {string} Its value, or "".
	 */
	const readAttribute = function ( html, attribute ) {
		if ( typeof html !== 'string' ) {
			return '';
		}
		const found = html.match(
			new RegExp(
				'\\s' +
					escapeRegExp( attribute ) +
					'\\s*=\\s*(?:"([^"]*)"|\'([^\']*)\')',
				'i'
			)
		);
		if ( ! found ) {
			return '';
		}
		return ( found[ 1 ] !== undefined ? found[ 1 ] : found[ 2 ] ) || '';
	};

	const anchorHas = function ( html, attribute ) {
		return new RegExp( '<a[^>]*' + attribute + '\\s*=', 'i' ).test( html );
	};

	/**
	 * An image block's attributes: those only in the block's JSON are dropped (the save would add what the stored HTML
	 * lacks), then filled from the stored HTML (R81).
	 *
	 * @param {Object} attributes The parsed attributes.
	 * @param {string} html       The stored HTML.
	 * @return {Object} The attributes.
	 */
	const imageAttributes = function ( attributes, html ) {
		if ( ! anchorHas( html, 'data-lasso-id' ) ) {
			delete attributes[ 'data-lasso-id' ];
			delete attributes[ 'data-lasso-name' ];
			attributes[ 'all-links' ] = [];
		} else if ( ! anchorHas( html, 'data-lasso-name' ) ) {
			delete attributes[ 'data-lasso-name' ];
		}
		if ( ! /<img[^>]*data-lasso-id\s*=/i.test( html ) ) {
			delete attributes[ 'data-lasso-id-img' ];
		}
		[ 'data-lasso-id', 'data-lasso-name' ].forEach( function ( name ) {
			const value = anchorHas( html, name )
				? readAttribute( html, name )
				: '';
			if ( ! attributes[ name ] && value ) {
				attributes[ name ] = value;
			}
		} );
		return attributes;
	};

	/**
	 * A button block's attributes filled from the stored HTML when they did not parse (R81).
	 *
	 * @param {Object} attributes The parsed attributes.
	 * @param {string} html       The stored HTML.
	 * @return {Object} The attributes.
	 */
	const buttonAttributes = function ( attributes, html ) {
		[
			'data-lasso-id',
			'data-lasso-name',
			'data-lasso-lid',
			'rel',
			'target',
		].forEach( function ( name ) {
			if ( ! attributes[ name ] ) {
				const value = readAttribute( html, name );
				if ( value ) {
					attributes[ name ] = value;
				}
			}
		} );
		if ( ! attributes.linkRel && attributes.rel ) {
			attributes.linkRel = attributes.rel;
		}
		if (
			attributes.target === '_blank' &&
			attributes.linkNewTab !== true
		) {
			attributes.linkNewTab = true;
		}
		return attributes;
	};

	/**
	 * The block's Lasso link: its own attributes, else its first link's.
	 *
	 * @param {Object} attributes The block's attributes.
	 * @return {{id: string, name: string, lid: string}} The link's attributes.
	 */
	const meta = function ( attributes ) {
		const first = ( attributes[ 'all-links' ] || [] )[ 0 ] || {};
		return {
			id: attributes[ 'data-lasso-id' ] || first.data_lasso_id || '',
			name:
				attributes[ 'data-lasso-name' ] || first.data_lasso_name || '',
			lid: attributes[ 'data-lasso-lid' ] || first.data_lasso_lid || '',
		};
	};

	const hasMeta = function ( attributes ) {
		if ( ! attributes ) {
			return false;
		}
		const link = meta( attributes );
		if (
			link.id ||
			link.name ||
			link.lid ||
			attributes[ 'data-lasso-id-img' ]
		) {
			return true;
		}
		return (
			( attributes[ 'all-links' ] || [] ).some( function ( one ) {
				return (
					one &&
					( one.data_lasso_id ||
						one.data_lasso_name ||
						one.data_lasso_lid )
				);
			} ) ||
			( typeof attributes.content === 'string' &&
				attributes.content.indexOf( 'data-lasso-' ) !== -1 )
		);
	};

	/**
	 * Props with the link's attributes set.
	 *
	 * @param {Object}  props      The anchor's props.
	 * @param {Object}  link       meta().
	 * @param {Object=} attributes The block's, for rel and target.
	 * @return {Object} New props.
	 */
	const withLink = function ( props, link, attributes ) {
		const next = Object.assign( {}, props );
		if ( link.id ) {
			next[ 'data-lasso-id' ] = link.id;
		}
		if ( link.name ) {
			next[ 'data-lasso-name' ] = link.name;
		}
		if ( link.lid ) {
			next[ 'data-lasso-lid' ] = link.lid;
		}
		if ( attributes && attributes.rel ) {
			next.rel = attributes.rel;
		}
		if ( attributes && attributes.target ) {
			next.target = attributes.target;
		}
		return next;
	};

	const withImageId = function ( img, id ) {
		return wp.element.cloneElement(
			img,
			Object.assign( {}, img.props, { 'data-lasso-id': id } )
		);
	};

	/**
	 * An image block's save tree with the attributes on its link and image (apiVersion 3 blocks skip extraProps).
	 *
	 * @param {Object} element    The save element (figure).
	 * @param {Object} attributes The block's attributes.
	 * @return {Object} The element, cloned when changed.
	 */
	const imageSave = function ( element, attributes ) {
		const link = meta( attributes );
		const imageId = attributes[ 'data-lasso-id-img' ] || '';
		const children =
			element && element.props ? element.props.children : null;
		if (
			! children ||
			( ! link.id && ! link.name && ! link.lid && ! imageId )
		) {
			return element;
		}
		const hasLink = link.id || link.name || link.lid;
		const anchor = function ( a ) {
			if ( ! a || ! a.props ) {
				return a;
			}
			const inner = a.props.children;
			if ( imageId && inner && inner.type === 'img' && inner.props ) {
				return wp.element.cloneElement(
					a,
					withLink( a.props, link ),
					withImageId( inner, imageId )
				);
			}
			return wp.element.cloneElement( a, withLink( a.props, link ) );
		};
		const map = function ( nodes, onAnchor, onImage ) {
			if ( Array.isArray( nodes ) ) {
				return nodes.map( function ( node ) {
					return map( node, onAnchor, onImage );
				} );
			}
			if ( nodes && nodes.type === 'a' && onAnchor ) {
				return onAnchor( nodes );
			}
			if ( nodes && nodes.type === 'img' && nodes.props && onImage ) {
				return onImage( nodes );
			}
			return nodes;
		};
		const image = function ( img ) {
			return withImageId( img, imageId );
		};

		// WordPress 6+: <figure>{ Fragment [ <a>|<img>, <figcaption> ] }</figure>.
		if ( children.type === wp.element.Fragment ) {
			const inner = children.props.children;
			let mapped = hasLink ? map( inner, anchor, null ) : inner;
			if ( imageId && ! hasLink ) {
				mapped = map( inner, null, image );
			}
			return wp.element.cloneElement(
				element,
				{},
				wp.element.cloneElement( children, { children: mapped } )
			);
		}
		if ( Array.isArray( children ) ) {
			const at = children.findIndex( function ( node ) {
				return node && node.type === 'a';
			} );
			if ( at < 0 ) {
				return element;
			}
			const next = children.slice();
			next[ at ] = anchor( next[ at ] );
			return wp.element.cloneElement( element, {}, next );
		}
		if ( children.type === 'a' ) {
			return wp.element.cloneElement( element, {}, anchor( children ) );
		}
		if ( children.type === 'img' && imageId ) {
			return wp.element.cloneElement( element, {}, image( children ) );
		}
		return element;
	};

	/**
	 * A core button's save tree: its RichText link cloned with the attributes (changing props in place does not reach
	 * the saved HTML).
	 *
	 * @param {Object} element    The save element.
	 * @param {Object} attributes The block's attributes.
	 * @return {Object} The element.
	 */
	const coreButtonSave = function ( element, attributes ) {
		const child = element && element.props ? element.props.children : null;
		if (
			! child ||
			! child.props ||
			( child.props.tagName !== 'a' && child.props.tagName !== 'button' )
		) {
			return element;
		}
		return wp.element.cloneElement(
			element,
			{},
			wp.element.cloneElement(
				child,
				withLink( child.props, meta( attributes ), attributes )
			)
		);
	};

	/**
	 * The props of every link in a save tree, in document order.
	 *
	 * @param {Object} props A node's props.
	 * @param {Array}  found The links found so far.
	 * @return {Array} The links' props.
	 */
	const linkProps = function ( props, found ) {
		if ( ! props || typeof props !== 'object' ) {
			return found;
		}
		const children = props.children;
		const visit = function ( child ) {
			if ( child && typeof child === 'object' && 'props' in child ) {
				if ( child.type === 'a' ) {
					found.push( child.props );
				} else {
					linkProps( child.props, found );
				}
			}
		};
		if ( Array.isArray( children ) ) {
			children.forEach( visit );
		} else {
			visit( children );
		}
		return found;
	};

	/**
	 * Entities decoded, then escaped once, in the given attributes' values, so a list item's saved links match the
	 * stored HTML's (`&` against `&amp;`).
	 *
	 * @param {string}   html  The list item's content.
	 * @param {string[]} names The attributes.
	 * @return {string} The HTML.
	 */
	const normaliseValues = function ( html, names ) {
		let out = html;
		names.forEach( function ( name ) {
			out = out.replace(
				new RegExp(
					'(\\s' + escapeRegExp( name ) + '=")([^"]*)(")',
					'g'
				),
				function ( whole, open, value, close ) {
					let decoded = value;
					let previous;
					for (
						let round = 0;
						previous !== decoded && round < 10;
						round++
					) {
						previous = decoded;
						decoded = wp.htmlEntities.decodeEntities( decoded );
					}
					return (
						open +
						String( decoded )
							.replace( /&/g, '&amp;' )
							.replace( /"/g, '&quot;' )
							.replace( /</g, '&lt;' )
							.replace( />/g, '&gt;' ) +
						close
					);
				}
			);
		} );
		// Stored names sometimes end in a cut entity before the ellipsis: `&amp...`.
		return out.replace(
			/data-lasso-name="([^"]*)"/g,
			function ( whole, value ) {
				const fixed = value.replace( /&amp;\.\.\.$/, '&amp...' );
				return fixed === value
					? whole
					: 'data-lasso-name="' + fixed + '"';
			}
		);
	};

	/**
	 * A list item's save tree with its content's links normalised (R82).
	 *
	 * @param {Object} element    The save element.
	 * @param {Object} attributes The block's attributes.
	 * @return {Object} The element.
	 */
	const listItemSave = function ( element, attributes ) {
		const content =
			typeof attributes.content === 'string' ? attributes.content : '';
		if ( element.type !== 'li' || ! /<a[\s>]/i.test( content ) ) {
			return element;
		}
		const html = normaliseValues( content, [
			'href',
			'data-lasso-name',
			'data-lasso-id',
			'data-lasso-lid',
		] );
		if ( html === content ) {
			return element;
		}
		return wp.element.cloneElement(
			element,
			{},
			wp.element.createElement( wp.element.RawHTML, null, html )
		);
	};

	/**
	 * Blocks whose saved markup is left alone (R82).
	 *
	 * @param {string} name The block's name.
	 * @return {boolean} True for those blocks.
	 */
	const skipped = function ( name ) {
		return [ 'kadence/', 'yoast/', 'tph/', 'editor-blocks/' ].some(
			function ( prefix ) {
				return name.includes( prefix );
			}
		);
	};

	// R80: the attributes, on blocks registered from now on and on those registered before this script.
	wp.hooks.addFilter(
		'blocks.registerBlockType',
		NAMESPACE,
		function ( settings, name ) {
			const extra = extraAttributes( name );
			if ( Object.keys( extra ).length ) {
				settings.attributes = Object.assign(
					{},
					settings.attributes,
					extra
				);
			}
			return settings;
		}
	);
	[ 'core/image', 'core/button', 'generateblocks/button' ].forEach(
		function ( name ) {
			const type = wp.blocks && wp.blocks.getBlockType( name );
			if ( type && type.attributes ) {
				type.attributes = Object.assign(
					{},
					type.attributes,
					extraAttributes( name )
				);
			}
		}
	);

	// R81: the attributes read from the stored HTML.
	wp.hooks.addFilter(
		'blocks.getBlockAttributes',
		NAMESPACE,
		function ( attributes, blockType, html ) {
			if ( lassoFilters() || typeof html !== 'string' || ! html ) {
				return attributes;
			}
			if ( isButtonBlock( blockType.name ) ) {
				return buttonAttributes( attributes, html );
			}
			return blockType.name === 'core/image'
				? imageAttributes( attributes, html )
				: attributes;
		}
	);

	// R82: the attributes written back onto the saved link (blocks of apiVersion 1 and 2).
	wp.hooks.addFilter(
		'blocks.getSaveContent.extraProps',
		NAMESPACE,
		function ( props, blockType, attributes ) {
			if (
				lassoFilters() ||
				skipped( blockType.name ) ||
				! hasMeta( attributes )
			) {
				return props;
			}
			const link = meta( attributes );
			const figure =
				props && props.children && props.children.props
					? props.children.props
					: null;
			const first =
				figure && Array.isArray( figure.children )
					? figure.children[ 0 ]
					: null;
			if ( first && first.type === 'a' ) {
				Object.assign( first.props, withLink( {}, link, attributes ) );
			} else if (
				figure &&
				( figure.type === 'a' || figure.tagName === 'a' )
			) {
				Object.assign( figure, withLink( {}, link, attributes ) );
			}
			return props;
		}
	);

	// R82: the save tree, for every block (apiVersion 3 blocks skip extraProps).
	wp.hooks.addFilter(
		'blocks.getSaveElement',
		NAMESPACE,
		function ( element, blockType, attributes ) {
			if (
				lassoFilters() ||
				skipped( blockType.name ) ||
				element === null ||
				typeof element !== 'object'
			) {
				return element;
			}
			if ( blockType.name === 'core/list-item' ) {
				return listItemSave( element, attributes );
			}
			if ( ! hasMeta( attributes ) ) {
				return element;
			}
			if ( blockType.name === 'core/image' ) {
				return imageSave( element, attributes );
			}
			if ( blockType.name === 'core/button' ) {
				element = coreButtonSave( element, attributes );
			}
			const props = element.props || null;
			if ( ! props ) {
				return element;
			}
			const link = meta( attributes );
			// A block whose one child is a RichText link.
			const child = props.children && props.children.props;
			if ( child && child.tagName === 'a' ) {
				Object.assign( child, withLink( {}, link, attributes ) );
			}
			// Each link of the block takes its own stored attributes, when its address matches.
			const links = attributes[ 'all-links' ] || [];
			const found = linkProps( props, [] );
			found.forEach( function ( one, index ) {
				const stored = links[ index ];
				if (
					stored &&
					stored.href === one.href &&
					stored.data_lasso_id
				) {
					Object.assign(
						one,
						withLink(
							{},
							{
								id: stored.data_lasso_id,
								name: stored.data_lasso_name,
								lid: stored.data_lasso_lid || link.lid,
							},
							{ rel: stored.rel, target: stored.target }
						)
					);
				}
			} );
			// A GenerateBlocks button is itself the link: its attributes go in `htmlAttrs`.
			if (
				! found.length &&
				blockType.name.includes( 'generateblocks/button' ) &&
				props.htmlAttrs instanceof Object &&
				props.tagName === 'a'
			) {
				Object.assign(
					props.htmlAttrs,
					withLink( {}, link, attributes )
				);
			}
			return element;
		}
	);
} )();
