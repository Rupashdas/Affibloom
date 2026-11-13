<?php
/**
 * Settings → Display, "Button icons" (F28, Task 14F; register U11): the icons a button or badge text can show. The
 * site's own (drawn by its Additional CSS) are listed and kept; icons added here get their CSS from Affibloom and can
 * be removed. Choosing the image uses WordPress's media library, which needs JavaScript; without it the form is
 * refused with the reason.
 *
 * @package Affibloom
 * @var array{icons: list<array{icon: \Affibloom\Domain\Icons\Icon, src: string}>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\IconController;

$admin_post = admin_url( 'admin-post.php' );
?>
<section class="affibloom-card affibloom-icon-set" id="affibloom-icon-set" aria-labelledby="affibloom-icon-set-title">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title" id="affibloom-icon-set-title"><?php esc_html_e( 'Button icons', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( "Icons you can put in front of a button or badge text. Icons from your site's CSS are drawn by your theme's Additional CSS; icons you add here need no CSS.", 'affibloom' ); ?></p>
	</div>

	<ul class="affibloom-card__body affibloom-icon-set__list">
		<?php foreach ( $view['icons'] as $row ) : ?>
			<?php $icon = $row['icon']; ?>
			<li class="affibloom-icon-set__item" data-icon-class="<?php echo esc_attr( $icon->css_class ); ?>">
				<?php // On a dark tile, as on a button: the site's icons are white glyphs. ?>
				<span class="affibloom-icon-set__tile"><img class="affibloom-icon-set__image" src="<?php echo esc_url( $row['src'] ); ?>" alt="" width="20" height="20" loading="lazy"></span>
				<span class="affibloom-icon-set__text">
					<span class="affibloom-icon-set__name"><?php echo esc_html( $icon->name ); ?></span>
					<code class="affibloom-icon-set__class"><?php echo esc_html( $icon->css_class ); ?></code>
				</span>
				<?php if ( $icon->imported ) : ?>
					<span class="affibloom-icon-set__source"><?php esc_html_e( "From your site's CSS", 'affibloom' ); ?></span>
				<?php else : ?>
					<form class="affibloom-icon-set__remove" method="post" action="<?php echo esc_url( $admin_post ); ?>"
						data-confirm="<?php echo esc_attr( sprintf( /* translators: %s: icon name. */ __( 'Remove %s? Texts that use it will show no icon until another is chosen.', 'affibloom' ), $icon->name ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( IconController::REMOVE ); ?>">
						<input type="hidden" name="class" value="<?php echo esc_attr( $icon->css_class ); ?>">
						<?php wp_nonce_field( IconController::REMOVE ); ?>
						<button type="submit" class="affibloom-button affibloom-button--small affibloom-button--secondary"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: icon name. */ __( 'Remove %s', 'affibloom' ), $icon->name ) ); ?>">
							<?php esc_html_e( 'Remove', 'affibloom' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<form class="affibloom-card__foot affibloom-icon-set__add" method="post" action="<?php echo esc_url( $admin_post ); ?>" data-icon-add>
		<input type="hidden" name="action" value="<?php echo esc_attr( IconController::ADD ); ?>">
		<input type="hidden" name="attachment" value="0" data-icon-attachment>
		<?php wp_nonce_field( IconController::ADD ); ?>
		<label class="screen-reader-text" for="affibloom-icon-name"><?php esc_html_e( 'Icon name', 'affibloom' ); ?></label>
		<input type="text" class="affibloom-input" id="affibloom-icon-name" name="name" maxlength="40" required
			placeholder="<?php esc_attr_e( 'Icon name', 'affibloom' ); ?>">
		<span class="affibloom-icon-set__tile" data-icon-preview-tile hidden><img class="affibloom-icon-set__image" alt="" width="20" height="20" data-icon-preview></span>
		<button type="button" class="affibloom-button affibloom-button--secondary" data-icon-choose><?php esc_html_e( 'Choose image', 'affibloom' ); ?></button>
		<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Add icon', 'affibloom' ); ?></button>
	</form>
</section>
