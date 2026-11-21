<?php
/**
 * A product page tab's head action (F12 L4, D23; Task 36B; the Opportunities tab, F22 R35, Task O5): Lasso's "Search",
 * level with the title. Without JavaScript it is a form that reloads the tab; the tab's script redraws the list in place
 * instead. The Locations tab's by default; another tab gives its key, the box's id and its label.
 *
 * @package Affibloom
 * @var array{id: int, term: string, order: string, tab?: string, search_id?: string, label?: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\ProductScreen;
?>
<form class="affibloom-page__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
	<input type="hidden" name="page" value="<?php echo esc_attr( ProductScreen::SLUG ); ?>">
	<input type="hidden" name="id" value="<?php echo esc_attr( (string) $view['id'] ); ?>">
	<input type="hidden" name="tab" value="<?php echo esc_attr( $view['tab'] ?? 'locations' ); ?>">
	<?php if ( '' !== $view['order'] ) : ?>
		<input type="hidden" name="order" value="<?php echo esc_attr( $view['order'] ); ?>">
	<?php endif; ?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $view['search_id'] ?? 'affibloom-locations-search' ); ?>"><?php echo esc_html( $view['label'] ?? __( 'Search Locations', 'affibloom' ) ); ?></label>
	<input type="search" id="<?php echo esc_attr( $view['search_id'] ?? 'affibloom-locations-search' ); ?>" class="affibloom-input affibloom-search" name="s"
		value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php esc_attr_e( 'Search', 'affibloom' ); ?>">
</form>
