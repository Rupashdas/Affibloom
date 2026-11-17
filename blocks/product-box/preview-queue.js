/**
 * The previews' queue (F4 R52, Task 24E): a long post with many displays must not flood the server. At most three
 * previews are asked at once, 40 ms apart; a block that asks again before its turn replaces its waiting request, so
 * only its latest draws. Every block's preview goes through it (and the Elementor widget's, ED2).
 */
import apiFetch from '@wordpress/api-fetch';

const AT_ONCE = 3;
const APART = 40;
const waiting = [];
let running = 0;
let last = 0;
let timer = null;

/** Starts what the limits allow; waits out the 40 ms between two starts. */
function pump() {
	timer = null;
	while ( running < AT_ONCE && waiting.length ) {
		const wait = last + APART - Date.now();
		if ( wait > 0 ) {
			timer = setTimeout( pump, wait );
			return;
		}
		const job = waiting.shift();
		running++;
		last = Date.now();
		apiFetch( {
			path: '/affibloom/v1/editor/preview',
			method: 'POST',
			data: { attributes: job.attributes },
		} )
			.then( job.resolve, job.reject )
			.finally( () => {
				running--;
				if ( ! timer ) {
					pump();
				}
			} );
	}
}

/**
 * Asks for a block's preview through the queue.
 *
 * @param {string} key        The block (its client ID).
 * @param {Object} attributes The block's attributes.
 * @return {Promise<Object>} The preview's answer; a request replaced by a newer one of the same block is refused with
 *                           `{ superseded: true }`.
 */
export function queuePreview( key, attributes ) {
	return new Promise( ( resolve, reject ) => {
		const earlier = waiting.findIndex( ( job ) => job.key === key );
		if ( earlier >= 0 ) {
			waiting.splice( earlier, 1 )[ 0 ].reject( { superseded: true } );
		}
		waiting.push( { key, attributes, resolve, reject } );
		if ( ! timer ) {
			pump();
		}
	} );
}
