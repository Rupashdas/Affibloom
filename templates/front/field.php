<?php
/**
 * One value of a product inline (F31 R11; Lasso's aawp/fields.php under Affibloom's names): `span.affibloom-aawp-fields`
 * holding the part's value, a linked thumbnail, the product's button, Amazon's stars or a link; nothing for an unknown
 * part.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductInput;

$inline = $view->inline;
if ( empty( $inline['part'] ) ) {
	return;
}
?>
<span class="affibloom-aawp-fields">
<?php
switch ( $inline['part'] ) {
	case 'thumb':
		$src = $view->image_src;
		if ( str_contains( $src, 'media-amazon' ) && in_array( $inline['image_size'], array( 'small', 'medium' ), true ) ) {
			$src = str_replace( '.jpg', 'small' === $inline['image_size'] ? '._SL75_.jpg' : '._SL160_.jpg', $src );
		}
		$align   = array(
			'center' => 'aligncenter',
			'left'   => 'alignleft',
			'right'  => 'alignright',
		)[ $inline['image_align'] ] ?? '';
		$classes = trim( $inline['image_class'] . ' ' . $align );
		$sized   = ctype_digit( $inline['image_width'] ) && ctype_digit( $inline['image_height'] );

		$attributes = $view->buttons[0]->attributes ?? $view->link;
		?>
		<a<?php require __DIR__ . '/partials/attributes.php'; ?>><img src="<?php echo esc_url( $src ); ?>" class="<?php echo esc_attr( $classes ); ?>"<?php echo $sized ? ' width="' . esc_attr( $inline['image_width'] ) . '" height="' . esc_attr( $inline['image_height'] ) . '"' : ''; ?> <?php echo \Affibloom\Interface\Front\ImageLoading::attribute(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- one of three fixed attributes. ?> alt="<?php echo esc_attr( $view->image_alt ); ?>"></a>
		<?php
		break;
	case 'button':
		$buttons = array_slice( $view->buttons, 0, 1 );
		?>
		<span class="aawp-fields-button <?php echo esc_attr( $view->theme ); ?>"><?php require __DIR__ . '/partials/buttons.php'; ?></span>
		<?php
		break;
	case 'star_rating':
		?>
		<a target="_blank" href="<?php echo esc_url( $inline['reviews'] ); ?>" class="affibloom-stars" style="--rating: 0"><span class="affibloom-stars-value">0.0</span></a>
		<?php
		break;
	case 'link':
		if ( '' !== $inline['name'] ) {
			$attributes = $view->link;
			?>
			<a<?php require __DIR__ . '/partials/attributes.php'; ?>><?php echo esc_html( $inline['name'] ); ?></a>
			<?php
		}
		break;
	case 'description':
		echo wp_kses_post( $inline['value'] );
		break;
	case 'price':
		echo wp_kses( $inline['value'], ProductInput::PRICE_TAGS );
		break;
	default:
		echo esc_html( $inline['value'] );
}
?>
</span><?php
