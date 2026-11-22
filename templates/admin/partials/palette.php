<?php
/**
 * "Search links" (F38 R67, D19; Task ON9): the dialog palette.js copies into the page when the shortcut opens it.
 * Lasso's words, its product name taken out of the heading; the keys are filled in for the user's system.
 *
 * @package Affibloom
 * @var array{product_url: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<template id="affibloom-palette-template" data-product-url="<?php echo esc_url( $view['product_url'] ); ?>"
	data-keys-pc="<?php esc_attr_e( 'Ctrl+Shift+L', 'affibloom' ); ?>" data-keys-mac="<?php esc_attr_e( '⌘⇧L', 'affibloom' ); ?>"
	data-loading="<?php esc_attr_e( 'Loading…', 'affibloom' ); ?>" data-empty="<?php esc_attr_e( 'No links found.', 'affibloom' ); ?>"
	data-failed="<?php esc_attr_e( 'The list could not be loaded.', 'affibloom' ); ?>">
	<div class="affibloom-palette" data-palette>
		<div class="affibloom-palette__backdrop" data-palette-close></div>
		<div class="affibloom-palette__dialog" role="dialog" aria-modal="true" aria-labelledby="affibloom-palette-title">
			<div class="affibloom-palette__head">
				<h2 class="affibloom-palette__title" id="affibloom-palette-title"><?php esc_html_e( 'Search links', 'affibloom' ); ?></h2>
				<button type="button" class="affibloom-palette__close" aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>" data-palette-close>
					<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>
			</div>
			<label class="screen-reader-text" for="affibloom-palette-input"><?php esc_html_e( 'Search by link name or URL…', 'affibloom' ); ?></label>
			<input type="search" id="affibloom-palette-input" class="affibloom-palette__input" autocomplete="off" spellcheck="false"
				placeholder="<?php esc_attr_e( 'Search by link name or URL…', 'affibloom' ); ?>" aria-controls="affibloom-palette-results" data-palette-input>
			<?php /* translators: %s: the keyboard shortcut, as Ctrl+Shift+L or ⌘⇧L. */ ?>
			<p class="affibloom-palette__hint" data-palette-hint data-format="<?php esc_attr_e( 'Shortcut: %s. Press again to close.', 'affibloom' ); ?>"></p>
			<p class="affibloom-palette__status" role="status" data-palette-status></p>
			<ul class="affibloom-palette__results" id="affibloom-palette-results" role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'affibloom' ); ?>" data-palette-results></ul>
		</div>
	</div>
</template>
