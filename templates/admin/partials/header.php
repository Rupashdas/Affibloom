<?php
/**
 * The Affibloom header: logo mark, wordmark, the plugin's navigation and, on the right, "Add New Link", which
 * Lasso puts in its header on every screen (docs/ui/design-system.md, Aurora; F18 entry point 1). The modal it
 * opens is printed after the header.
 *
 * @package Affibloom
 * @var array{current: string} $view
 */

defined( 'ABSPATH' ) || exit;

$header_tabs = array(
	'dashboard' => array( __( 'Dashboard', 'affibloom' ), admin_url( 'admin.php?page=affibloom' ), 'home' ),
);
if ( current_user_can( \Affibloom\Interface\Admin\Capabilities::SETTINGS ) ) {
	$header_tabs['settings'] = array( __( 'Settings', 'affibloom' ), admin_url( 'admin.php?page=affibloom-settings' ), 'settings' );
}
$header_can_add = current_user_can( \Affibloom\Interface\Admin\Capabilities::PRODUCTS );
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
		<?php foreach ( $header_tabs as $key => [ $label, $url, $icon ] ) : ?>
			<a class="affibloom-header__tab<?php echo $key === $view['current'] ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $url ); ?>"
				<?php echo $key === $view['current'] ? 'aria-current="page"' : ''; ?>>
				<?php $this->render( 'admin/partials/icon', array( 'name' => $icon ) ); ?>
				<span class="affibloom-header__tab-label"><?php echo esc_html( $label ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php if ( $header_can_add ) : ?>
		<?php // Lasso's split button: the main part opens the single-link dialog, the caret lists the other ways to add. ?>
		<div class="affibloom-header__add">
			<button type="button" class="affibloom-header__action" data-affibloom-open="affibloom-add-modal" aria-haspopup="dialog">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
				<span class="affibloom-header__action-label"><?php esc_html_e( 'Add New Link', 'affibloom' ); ?></span>
			</button>
			<button type="button" class="affibloom-header__caret" aria-expanded="false" aria-controls="affibloom-add-menu"
				aria-label="<?php esc_attr_e( 'More ways to add links', 'affibloom' ); ?>" title="<?php esc_attr_e( 'More ways to add links', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-down' ) ); ?>
			</button>
			<?php // Lasso's Marketplace item comes with C1 (F18 decision D5). ?>
			<div id="affibloom-add-menu" class="affibloom-menu" hidden>
				<button type="button" class="affibloom-menu__item" data-affibloom-open="affibloom-add-modal" data-affibloom-mode="bulk" aria-haspopup="dialog">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'layers' ) ); ?></span>
					<span class="affibloom-menu__text">
						<span class="affibloom-menu__title"><?php esc_html_e( 'Bulk add links', 'affibloom' ); ?></span>
						<span class="affibloom-menu__desc"><?php esc_html_e( 'Import many URLs at once', 'affibloom' ); ?></span>
					</span>
				</button>
			</div>
		</div>
	<?php endif; ?>
</header>
<?php
if ( $header_can_add ) {
	$this->render( 'admin/partials/add-modal' );
}
