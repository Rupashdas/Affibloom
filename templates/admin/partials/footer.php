<?php
/**
 * The end of every Affibloom page: the plugin's version, as Lasso's footer badge (F38 R16, D7). WordPress's own version
 * line is blanked on these pages (AdminMenu::update_footer).
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="affibloom-footer">
	<?php
	/* translators: %s: the plugin's version, such as 1.2.0. */
	echo esc_html( sprintf( __( 'Version %s', 'affibloom' ), AFFIBLOOM_VERSION ) );
	?>
</footer>
