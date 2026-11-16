<?php
/**
 * A product placed as a lone image (F31 R10, A10; Lasso's image.php under Affibloom's names): the linked picture,
 * 500 x 500, its link the product's.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$attributes = $view->link;
?>
<?php // A gallery's items leave the id to the gallery (F30 D3). ?>
<div<?php echo '' === $view->anchor ? '' : ' id="' . esc_attr( $view->anchor ) . '"'; ?> class="affibloom-box affibloom-image-container">
	<!-- PRODUCT IMAGE -->
	<a class="affibloom-image image-style" id="css-<?php echo esc_attr( (string) ( $view->link['data-affibloom-id'] ?? '' ) ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
		<img class="affibloom-image-no-lazy" src="<?php echo esc_url( $view->image_src ); ?>" <?php echo \Affibloom\Interface\Front\ImageLoading::attribute(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- one of three fixed attributes. ?> height="500" width="500" alt="<?php echo esc_attr( $view->image_alt ); ?>">
	</a>
</div>
