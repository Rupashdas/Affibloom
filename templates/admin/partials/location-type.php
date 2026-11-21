<?php
/**
 * A place's Type icon (Lasso's class-html-helper.php 622-659): a text or image link, a single display, or a group
 * display by its layout, with Lasso's "This is …" tooltip; it opens the Link with Context preview (F12 L10; F22 R12,
 * R13). Taken out of location-row.php on its second use (Link Opportunities, Task O1).
 *
 * @package Affibloom
 * @var array{place: \Affibloom\Domain\LinkIndex\ProductLocation} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\LinkIndex\ProductLocation;

$place       = $view['place'];
$place_types = array(
	ProductLocation::TEXT    => array( 'link', __( 'This is a text link.', 'affibloom' ) ),
	ProductLocation::IMAGE   => array( 'image', __( 'This is an image link.', 'affibloom' ) ),
	ProductLocation::SINGLE  => array( 'box', __( 'This is a single link display.', 'affibloom' ) ),
	ProductLocation::GRID    => array( 'grid', __( 'This is a group of links display.', 'affibloom' ) ),
	ProductLocation::LIST    => array( 'list', __( 'This is a group of links display.', 'affibloom' ) ),
	ProductLocation::GALLERY => array( 'gallery', __( 'This is a group of links display.', 'affibloom' ) ),
);
$place_type  = $place_types[ $place->kind() ];
?>
<button type="button" class="affibloom-alert affibloom-location__type" data-location-preview="<?php echo esc_attr( (string) $place->id ); ?>" data-tip="<?php echo esc_attr( $place_type[1] ); ?>">
	<?php $this->render( 'admin/partials/icon', array( 'name' => $place_type[0] ) ); ?>
	<span class="screen-reader-text"><?php echo esc_html( $place_type[1] ); ?></span>
</button>
