<?php
/**
 * One icon of the "Button icons" card (F28, Task 14F): its picture, name and class, and Remove for an icon added here.
 * Printed by the card and by the REST answer to Add icon (IconsController), which puts it in the list.
 *
 * @package Affibloom
 * @var array{row: array{icon: \Affibloom\Domain\Icons\Icon, src: string, missing: bool}} $view
 */

defined( 'ABSPATH' ) || exit;

$row  = $view['row'];
$icon = $row['icon'];
?>
<li class="affibloom-icon-set__item" data-icon-class="<?php echo esc_attr( $icon->css_class ); ?>">
	<?php $this->render( 'admin/partials/icon-picture', array( 'row' => $row ) ); ?>
	<span class="affibloom-icon-set__text">
		<span class="affibloom-icon-set__name"><?php echo esc_html( $icon->name ); ?></span>
		<code class="affibloom-icon-set__class"><?php echo esc_html( $icon->css_class ); ?></code>
		<?php if ( $row['missing'] ) : ?>
			<span class="affibloom-icon-set__missing">
				<?php
				/* translators: %s: the image's link, e.g. /wp-content/uploads/2023/04/youtube-icon.svg. */
				echo esc_html( sprintf( __( 'Image missing: %s', 'affibloom' ), $icon->url ) );
				?>
			</span>
		<?php endif; ?>
	</span>
	<?php if ( $icon->imported ) : ?>
		<span class="affibloom-icon-set__source"><?php esc_html_e( "From your site's CSS", 'affibloom' ); ?></span>
	<?php else : ?>
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-icon-remove="<?php echo esc_attr( $icon->css_class ); ?>"
			data-confirm="<?php echo esc_attr( sprintf( /* translators: %s: icon name. */ __( 'Remove %s? Texts that use it will show no icon until another is chosen.', 'affibloom' ), $icon->name ) ); ?>"
			aria-label="<?php echo esc_attr( sprintf( /* translators: %s: icon name. */ __( 'Remove %s', 'affibloom' ), $icon->name ) ); ?>">
			<?php esc_html_e( 'Remove', 'affibloom' ); ?>
		</button>
	<?php endif; ?>
</li>
