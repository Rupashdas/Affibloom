<?php
/**
 * The Dashboard row's green lightbulb (F22 R54, Task O8; Lasso's rows/url-row.php 131-136): "This URL has N possible
 * opportunities.", a link to the product's Opportunities tab. Nothing when the product has none.
 *
 * @package Affibloom
 * @var array{count: int, product_page: string} $view
 */

defined( 'ABSPATH' ) || exit;

if ( $view['count'] < 1 ) {
	return;
}

/* translators: %d: how many opportunities the product has. */
$tip = sprintf( _n( 'This URL has %d possible opportunity.', 'This URL has %d possible opportunities.', $view['count'], 'affibloom' ), $view['count'] );
?>
<a class="affibloom-alert affibloom-alert--opportunity" href="<?php echo esc_url( add_query_arg( 'tab', 'opportunities', $view['product_page'] ) ); ?>" data-tip="<?php echo esc_attr( $tip ); ?>">
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'bulb' ) ); ?>
	<span class="screen-reader-text"><?php echo esc_html( $tip ); ?></span>
</a>
