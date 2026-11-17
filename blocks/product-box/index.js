/**
 * The Product box block (F4, Task 23A): one block for every display type (D3). It is dynamic: the server draws it from
 * its attributes, so nothing is saved but the block comment.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import { FlowerIcon } from './flower';
import './editor.css';

registerBlockType( metadata.name, {
	icon: FlowerIcon,
	edit: Edit,
	save: () => null,
} );
