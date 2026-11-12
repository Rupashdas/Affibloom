<?php
/**
 * The Affibloom header: logo mark, wordmark and the plugin's tabs (docs/ui/design-system.md).
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
		<svg class="affibloom-header__logo" viewBox="0 0 28 28" aria-hidden="true" focusable="false">
			<g class="affibloom-header__logo-petals">
				<ellipse cx="14" cy="7" rx="4.2" ry="6"/>
				<ellipse cx="14" cy="7" rx="4.2" ry="6" transform="rotate(72 14 14)"/>
				<ellipse cx="14" cy="7" rx="4.2" ry="6" transform="rotate(144 14 14)"/>
				<ellipse cx="14" cy="7" rx="4.2" ry="6" transform="rotate(216 14 14)"/>
				<ellipse cx="14" cy="7" rx="4.2" ry="6" transform="rotate(288 14 14)"/>
			</g>
			<circle class="affibloom-header__logo-ring" cx="14" cy="14" r="4"/>
			<circle class="affibloom-header__logo-dot" cx="14" cy="14" r="2.4"/>
		</svg>
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
