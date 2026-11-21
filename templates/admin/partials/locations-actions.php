<?php
/**
 * The Locations tab's head action (F12 L4, D23; Task 36B): Lasso's "Search", level with the title. Without JavaScript
 * it is a form that reloads the tab; product-locations.js redraws the list in place instead.
 *
 * @package Affibloom
 * @var array{id: int, term: string, order: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\ProductScreen;
?>
<form class="affibloom-page__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
	<input type="hidden" name="page" value="<?php echo esc_attr( ProductScreen::SLUG ); ?>">
	<input type="hidden" name="id" value="<?php echo esc_attr( (string) $view['id'] ); ?>">
	<input type="hidden" name="tab" value="locations">
	<?php if ( '' !== $view['order'] ) : ?>
		<input type="hidden" name="order" value="<?php echo esc_attr( $view['order'] ); ?>">
	<?php endif; ?>
	<label class="screen-reader-text" for="affibloom-locations-search"><?php esc_html_e( 'Search Locations', 'affibloom' ); ?></label>
	<input type="search" id="affibloom-locations-search" class="affibloom-input affibloom-search" name="s"
		value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php esc_attr_e( 'Search', 'affibloom' ); ?>">
</form>
