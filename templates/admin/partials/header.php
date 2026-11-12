<?php
/**
 * Lasso-style header bar with the plugin name and its tabs.
 *
 * @package Affibloom
 * @var array{current: string} $view
 */

defined( 'ABSPATH' ) || exit;

$header_tabs = array(
	'dashboard' => array( __( 'Dashboard', 'affibloom' ), admin_url( 'admin.php?page=affibloom' ) ),
);
if ( current_user_can( \Affibloom\Interface\Admin\Capabilities::SETTINGS ) ) {
	$header_tabs['settings'] = array( __( 'Settings', 'affibloom' ), admin_url( 'admin.php?page=affibloom-settings' ) );
}
?>
<header class="affibloom-header">
	<div class="affibloom-header__brand">
		<span class="affibloom-header__logo" aria-hidden="true">A</span>
		<span class="affibloom-header__name"><?php esc_html_e( 'Affibloom', 'affibloom' ); ?></span>
	</div>
	<nav class="affibloom-header__nav" aria-label="<?php esc_attr_e( 'Affibloom', 'affibloom' ); ?>">
		<?php foreach ( $header_tabs as $key => [ $label, $url ] ) : ?>
			<a class="affibloom-header__tab<?php echo $key === $view['current'] ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $url ); ?>"
				<?php echo $key === $view['current'] ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
</header>
