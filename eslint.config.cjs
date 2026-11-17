/**
 * ESLint for the editor scripts (`npm run lint:js`, Task 23A): WordPress's own rules (wp-scripts), with one change:
 * `@wordpress/*` imports are WordPress's scripts (`wp.*`), which the build maps to globals, so they are neither
 * installed packages nor project dependencies.
 */
const wordpress = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpress,
	{
		rules: {
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
			'import/no-extraneous-dependencies': 'off',
		},
	},
];
