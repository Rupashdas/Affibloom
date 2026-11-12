<?php
/**
 * Settings screen: the band with the title, subtitle (U10) and section tabs, then one card per section with
 * Save in its footer (docs/ui/design-system.md, Aurora). Labels, help texts and their order are Lasso's.
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>, settings: \Affibloom\Domain\Settings\Settings, action: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

$labels   = array(
	'general' => __( 'General', 'affibloom' ),
);
$settings = $view['settings'];
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'settings' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title"><?php esc_html_e( 'Settings', 'affibloom' ); ?></h1>
				<p class="affibloom-page__subtitle"><?php esc_html_e( 'Defaults for new links, the product box and Amazon.', 'affibloom' ); ?></p>

				<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'affibloom' ); ?>">
					<?php foreach ( $view['tabs'] as $tab_key ) : ?>
						<a class="affibloom-tabs__tab<?php echo $tab_key === $view['tab'] ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom-settings&tab=' . $tab_key ) ); ?>"
							<?php echo $tab_key === $view['tab'] ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( $labels[ $tab_key ] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>

		<div class="affibloom-page__body">
			<form class="affibloom-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $view['tab'] ); ?>">
				<?php wp_nonce_field( $view['action'] ); ?>

				<?php if ( 'general' === $view['tab'] ) : ?>
					<div class="affibloom-card__head">
						<h2 class="affibloom-card__title"><?php esc_html_e( 'Link Defaults', 'affibloom' ); ?></h2>
						<p class="affibloom-card__intro"><?php esc_html_e( 'Set the default attributes for new links.', 'affibloom' ); ?></p>
					</div>
					<div class="affibloom-card__body">
						<?php
						// Lasso's labels and help texts (admin/views/settings/general.php), in Lasso's order.
						$toggles = array(
							'new_tab'         => array( 'new-tab', __( 'New Window / Tab', 'affibloom' ), __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ) ),
							'nofollow'        => array( 'nofollow', __( 'NoFollow / NoIndex', 'affibloom' ), __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ) ),
							'sponsored'       => array( 'tag', __( 'Sponsored', 'affibloom' ), __( 'When enabled, this link will be set to sponsored.', 'affibloom' ) ),
							'show_disclosure' => array( 'info', __( 'Show Disclosure', 'affibloom' ), __( 'When enabled, this link will show the disclosure.', 'affibloom' ) ),
						);
						foreach ( $toggles as $key => [ $icon, $label, $help ] ) {
							$this->render(
								'admin/partials/toggle',
								array(
									'id'      => 'affibloom-setting-' . $key,
									'name'    => 'settings[' . $key . ']',
									'label'   => $label,
									'checked' => $settings->bool( $key ),
									'help'    => $help,
									'icon'    => $icon,
								)
							);
						}
						?>
					</div>
				<?php endif; ?>

				<div class="affibloom-card__foot">
					<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
</div>
