/**
 * The block in the editor (F4, Task 23A): Lasso's panel (R5) in Meadow's look (ADR-8). Empty, it asks for a display
 * and offers **Add a Display**, which opens the display modal at "Choose a Display Type" (R9). While the modal's
 * script loads the button reads "Loading..." and is disabled; if it failed, "Retry Add a Display" loads it again (R6).
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Button, Spinner } from '@wordpress/components';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { FlowerIcon } from './flower';
import { loadModal, useModalState } from './modal-loader';

export default function Edit( { setAttributes } ) {
	const state = useModalState();
	const button = useRef();
	const blockProps = useBlockProps( { className: 'affibloom-block' } );

	const ready = 'ready' === state;
	const failed = 'error' === state;
	let label = __( 'Loading…', 'affibloom' );
	if ( ready ) {
		label = __( 'Add a Display', 'affibloom' );
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
			insert: ( attributes ) => setAttributes( attributes ),
		} );
	};

	return (
		<div { ...blockProps }>
			<div className="affibloom-block__panel">
				<span className="affibloom-block__mark">{ FlowerIcon }</span>
				<p className="affibloom-block__prompt">
					{ __( 'Choose a Link to display.', 'affibloom' ) }
				</p>
				<Button
					ref={ button }
					variant="primary"
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
