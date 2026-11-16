<?php
/**
 * The image link (F5 R11): the product's link around the picture, lazy loaded. Lasso's Ezoic and WP Rocket lazy-load
 * classes are not printed.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$attributes = $view->link;
?>
<a class="affibloom-image"<?php require __DIR__ . '/attributes.php'; ?><?php echo $view->image_hidden ? ' style="display: none !important;"' : ''; ?>>
	<img class="affibloom-image-no-lazy" src="<?php echo esc_url( $view->image_src ); ?>" height="500" width="500" loading="lazy" alt="<?php echo esc_attr( $view->image_alt ); ?>">
</a>
