/**
 * wp-scripts' build: the blocks it finds under blocks/ (their block.json), plus the Elementor widget's editor script
 * (Task ED2), which shares the block's preview queue and so goes through the same bundler.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: () => ( {
		...defaultConfig.entry(),
		elementor: path.resolve( __dirname, 'assets/editor/elementor.js' ),
	} ),
};
