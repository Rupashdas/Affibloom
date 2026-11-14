<?php
/**
 * The Affibloom header: logo mark, wordmark, the plugin's navigation and, on the right, "Add New Link", which
 * Lasso puts in its header on every screen (docs/ui/design-system.md, Aurora; F18 entry point 1). The modal it
 * opens is printed after the header.
 *
 * The tabs are the navigation's, in Lasso's order (F38 D1, Task ON1); the one the page belongs to is current, so a
 * product page marks Dashboard (R3). Below 992 px they fold behind a Menu button (R6).
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;
use Affibloom\Interface\Admin\Capabilities;

$header_tabs    = array_filter( $this->navigation()->tabs(), static fn ( $item ) => current_user_can( $item->capability ) );
$header_current = $this->navigation()->current( AdminMenu::requested_page() );
$header_can_add = current_user_can( Capabilities::PRODUCTS );
?>
<header class="affibloom-header">
	<a class="affibloom-header__brand" href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SLUG ) ); ?>">
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
	</a>
	<button type="button" class="affibloom-header__menu-button" aria-expanded="false" aria-controls="affibloom-header-nav" data-affibloom-nav-toggle>
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'menu' ) ); ?>
		<span class="affibloom-header__action-label"><?php esc_html_e( 'Menu', 'affibloom' ); ?></span>
	</button>
	<nav id="affibloom-header-nav" class="affibloom-header__nav" aria-label="<?php esc_attr_e( 'Affibloom', 'affibloom' ); ?>">
		<?php foreach ( $header_tabs as $header_tab ) : ?>
			<?php $header_is_current = $header_tab === $header_current; ?>
			<a class="affibloom-header__tab<?php echo $header_is_current ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . $header_tab->slug ) ); ?>"
				<?php echo $header_is_current ? 'aria-current="page"' : ''; ?>>
				<?php $this->render( 'admin/partials/icon', array( 'name' => $header_tab->icon ) ); ?>
				<span class="affibloom-header__tab-label"><?php echo esc_html( $header_tab->label() ); ?></span>
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
