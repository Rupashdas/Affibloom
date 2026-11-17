<?php
/**
 * The Variants tab's toolbar right of the title (Lasso's header.php 306-319, F27 R5): the search, which narrows the
 * loaded rows as you type (by name, key or address, any case) without a request, and Add Variant (Task V2).
 *
 * @package Affibloom
 * @var array<string, mixed> $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-page__search" role="search">
	<label class="screen-reader-text" for="affibloom-variant-search"><?php esc_html_e( 'Search variants', 'affibloom' ); ?></label>
	<input type="search" id="affibloom-variant-search" class="affibloom-input affibloom-search" data-variant-search
		placeholder="<?php esc_attr_e( 'Search', 'affibloom' ); ?>" autocomplete="off">
</div>
<button type="button" class="affibloom-button affibloom-button--primary" data-variant-add>
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
	<span><?php esc_html_e( 'Add Variant', 'affibloom' ); ?></span>
</button>
