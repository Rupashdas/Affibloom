/**
 * The display modal's script (assets/editor/display-modal.js) is loaded once per page, the first time a block needs it,
 * and its state is told to every block (Lasso's R8: idle, loading, ready, error). A failed load can be retried.
 * EditorAssets prints the script's address in `window.affibloomEditor.modalScript`.
 */
import { useEffect, useState } from '@wordpress/element';

const listeners = new Set();
let state = window.affibloomDisplayModal ? 'ready' : 'idle';

function set( next ) {
	state = next;
	listeners.forEach( ( listener ) => listener( next ) );
}

/** Loads the modal's script unless it is loading or loaded; after a failure, loads it again. */
export function loadModal() {
	if ( 'loading' === state || 'ready' === state ) {
		return;
	}
	const address =
		window.affibloomEditor && window.affibloomEditor.modalScript;
	if ( ! address ) {
		set( 'error' );
		return;
	}
	set( 'loading' );
	const script = document.createElement( 'script' );
	script.src = address;
	script.onload = () =>
		set( window.affibloomDisplayModal ? 'ready' : 'error' );
	script.onerror = () => {
		script.remove();
		set( 'error' );
	};
	document.head.appendChild( script );
}

/**
 * The modal's state for a block, starting the load on first use.
 *
 * @return {string} 'idle', 'loading', 'ready' or 'error'.
 */
export function useModalState() {
	const [ current, setCurrent ] = useState( state );
	useEffect( () => {
		listeners.add( setCurrent );
		loadModal();
		setCurrent( state );
		return () => {
			listeners.delete( setCurrent );
		};
	}, [] );
	return current;
}
