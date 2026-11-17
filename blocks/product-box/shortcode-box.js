/**
 * The block's shortcode box (F4 R7, R49, D4; Task 24C): the display as its `[affibloom …]` text, which the server writes
 * (POST /editor/preview). Typing there and leaving the box, or Enter, reads the text back (POST /editor/shortcode) and
 * the display and the sidebar follow, absent attributes cleared. Text that names no display empties the preview and
 * leaves the sidebar as it was (edge case 2).
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * The box.
 *
 * @param {Object}                    props           The box.
 * @param {string}                    props.shortcode The display's text, as the server wrote it.
 * @param {(display: Object) => void} props.onRead    Takes the display read from typed text.
 * @param {(broken: boolean) => void} props.onBroken  Told whether the typed text names no display.
 */
export default function ShortcodeBox( { shortcode, onRead, onBroken } ) {
	const [ draft, setDraft ] = useState( shortcode );
	const [ refused, setRefused ] = useState( false );
	useEffect( () => setDraft( shortcode ), [ shortcode ] );
	// An answer for a box that is gone (Update Display gives a fresh one) is not used.
	const alive = useRef( true );
	useEffect(
		() => () => {
			alive.current = false;
		},
		[]
	);

	const read = () => {
		const text = draft.trim();
		if ( text === shortcode ) {
			return;
		}
		apiFetch( {
			path: '/affibloom/v1/editor/shortcode',
			method: 'POST',
			data: { text },
		} )
			.then( ( answer ) => {
				if ( ! alive.current ) {
					return;
				}
				setRefused( false );
				onBroken( false );
				onRead( answer.data );
			} )
			.catch( () => {
				if ( ! alive.current ) {
					return;
				}
				setRefused( true );
				onBroken( true );
			} );
	};

	return (
		<span className="affibloom-block__shortcode">
			<input
				type="text"
				aria-label={ __( 'Shortcode', 'affibloom' ) }
				value={ draft }
				spellCheck="false"
				aria-invalid={ refused ? 'true' : 'false' }
				onChange={ ( event ) => setDraft( event.target.value ) }
				onBlur={ read }
				onKeyDown={ ( event ) => {
					if ( 'Enter' === event.key ) {
						event.preventDefault();
						read();
					}
				} }
			/>
		</span>
	);
}
