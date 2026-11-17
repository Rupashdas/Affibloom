<?php
/**
 * A group's head actions (F23 R5; design system 0b.4): Add a Link on the Links tab, and Copy Shortcode (F31 R32,
 * Task 18S12), whose tooltip reads "Copied!" once the group's grid shortcode is copied (groups.js). Moved out of the
 * group's band by Task MD3.
 *
 * @package Affibloom
 * @var array{tab: string, shortcode: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( 'links' === $view['tab'] ) : ?>
	<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-open="affibloom-add-to-group" aria-haspopup="dialog">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
		<span><?php esc_html_e( 'Add a Link', 'affibloom' ); ?></span>
	</button>
<?php endif; ?>
<button type="button" class="affibloom-button affibloom-button--secondary" data-copy-group-shortcode="<?php echo esc_attr( $view['shortcode'] ); ?>"
	title="<?php esc_attr_e( 'Copy this Display to your clipboard.', 'affibloom' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'affibloom' ); ?>"
	data-copy-failed="<?php esc_attr_e( 'Could not copy shortcode.', 'affibloom' ); ?>">
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
	<span data-copy-label aria-live="polite"><?php esc_html_e( 'Copy Shortcode', 'affibloom' ); ?></span>
</button>
