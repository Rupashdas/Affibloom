<?php
/**
 * A button icon's picture (F28): its image on a dark tile, as on a button, or a warning tile when the image's file is
 * not on the site (D4), so no screen shows a broken picture. Used by the "Button icons" card and the icon picker.
 *
 * @package Affibloom
 * @var array{row: array{icon: \Affibloom\Domain\Icons\Icon, src: string, missing: bool}} $view
 */

defined( 'ABSPATH' ) || exit;

$row = $view['row'];
?>
<?php if ( $row['missing'] ) : ?>
	<span class="affibloom-icon-set__tile affibloom-icon-set__tile--missing" title="<?php esc_attr_e( 'Image missing', 'affibloom' ); ?>"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
<?php else : ?>
	<span class="affibloom-icon-set__tile"><img class="affibloom-icon-set__image" src="<?php echo esc_url( $row['src'] ); ?>" alt="" width="20" height="20" loading="lazy"></span>
<?php endif; ?>
