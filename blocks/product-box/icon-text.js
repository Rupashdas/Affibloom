/**
 * A badge's or a button's text as its icon and its words, in the editors (Task 24G; F28's IconText). The editors
 * store `<span class='<class>'></span> <words>`: R40 drops double quotes, and a shortcode's attribute is written in
 * them. The site's own double-quoted form is read too. A text holding any other HTML is not split.
 */

/** One leading empty span with only a class, an optional space, then text with no HTML. */
const SHAPE = /^<span class=(["'])([^"'<>]+)\1><\/span> ?([^<>]*)$/;

/**
 * A stored text's icon and words.
 *
 * @param {string} value The stored text.
 * @return {?{icon: string, words: string}} Its parts; null when it holds other HTML.
 */
export const split = ( value ) => {
	const text = String( value || '' );
	if ( ! /[<>]/.test( text ) ) {
		return { icon: '', words: text };
	}
	const match = SHAPE.exec( text );
	return match ? { icon: match[ 2 ], words: match[ 3 ] } : null;
};

/**
 * The text as the editors store it.
 *
 * @param {string} icon  The icon's class ('' for none).
 * @param {string} words The words.
 * @return {string} The stored text.
 */
export const compose = ( icon, words ) => {
	if ( '' === icon ) {
		return words;
	}
	return (
		`<span class='${ icon }'></span>` + ( '' === words ? '' : ' ' + words )
	);
};
