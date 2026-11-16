<?php
/**
 * Settings → Display, "Button icons" (F28, Task 14F; register U11): the icons a button or badge text can show. The
 * site's own (drawn by its Additional CSS) are listed and kept; icons added here get their CSS from Affibloom and can
 * be removed. Choosing the image uses WordPress's media library, which needs JavaScript; without it the form is
 * refused with the reason.
 *
 * @package Affibloom
 * @var array{icons: list<array{icon: \Affibloom\Domain\Icons\Icon, src: string, missing: bool}>} $view
 */

defined( 'ABSPATH' ) || exit;

?>
<?php // Add and Remove go over REST and stay on the page (display-settings.js; deviation 143). ?>
<section class="affibloom-card affibloom-icon-set" id="affibloom-icon-set" aria-labelledby="affibloom-icon-set-title"
	data-failed-text="<?php esc_attr_e( 'That did not work. Try again.', 'affibloom' ); ?>">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title" id="affibloom-icon-set-title"><?php esc_html_e( 'Button icons', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( "Icons you can put in front of a button or badge text. Icons from your site's CSS are drawn by your theme's Additional CSS; icons you add here need no CSS.", 'affibloom' ); ?></p>
	</div>

	<ul class="affibloom-card__body affibloom-icon-set__list">
		<?php foreach ( $view['icons'] as $row ) : ?>
			<?php $this->render( 'admin/partials/icon-set-item', array( 'row' => $row ) ); ?>
		<?php endforeach; ?>
	</ul>

	<form class="affibloom-card__foot affibloom-icon-set__add" method="post" data-icon-add>
		<input type="hidden" name="attachment" value="0" data-icon-attachment>
		<label class="screen-reader-text" for="affibloom-icon-name"><?php esc_html_e( 'Icon name', 'affibloom' ); ?></label>
		<input type="text" class="affibloom-input" id="affibloom-icon-name" name="name" maxlength="40" required
			placeholder="<?php esc_attr_e( 'Icon name', 'affibloom' ); ?>">
		<span class="affibloom-icon-set__tile" data-icon-preview-tile hidden><img class="affibloom-icon-set__image" alt="" width="20" height="20" data-icon-preview></span>
		<button type="button" class="affibloom-button affibloom-button--secondary" data-icon-choose><?php esc_html_e( 'Choose image', 'affibloom' ); ?></button>
		<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Add icon', 'affibloom' ); ?></button>
	</form>
</section>
