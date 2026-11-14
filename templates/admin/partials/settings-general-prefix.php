<?php
/**
 * Settings → General, Cloaked Link Prefix (F9 R16-R18; Task 9G). What is typed becomes its slug form as it is typed
 * (general-settings.js, Lasso's keyup rule) and is stored through sanitize_title. What the prefix does to links is
 * Task F24's: nothing here rewrites a post (decision A7).
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;

$prefix = $view['settings']->string( 'link_prefix' );
?>
<div class="affibloom-card" id="affibloom-card-prefix">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Cloaked Link Prefix', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Add subdirectory to cloaked links. Most leave this empty.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<span class="affibloom-field__label-row">
				<label class="affibloom-label" for="affibloom-setting-link_prefix"><?php esc_html_e( 'Prefix', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-link_prefix-help',
						'text'  => __( 'For no prefix leave this field empty.', 'affibloom' ),
						'label' => __( 'Prefix', 'affibloom' ),
					)
				);
				?>
			</span>
			<input type="text" class="affibloom-input" id="affibloom-setting-link_prefix" name="settings[link_prefix]" value="<?php echo esc_attr( $prefix ); ?>"
				placeholder="recommends" autocomplete="off" spellcheck="false" data-slug-field aria-describedby="affibloom-setting-link_prefix-example">
			<p class="affibloom-field__hint" id="affibloom-setting-link_prefix-example">
				<?php
				/* translators: %s: an example link address. */
				printf( esc_html__( 'Example: %s', 'affibloom' ), '<code>' . esc_html( trailingslashit( home_url() ) ) . '<span data-prefix-slot>' . esc_html( '' === $prefix ? '' : $prefix . '/' ) . '</span>link-name/</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				?>
			</p>
		</div>
	</div>
</div>
