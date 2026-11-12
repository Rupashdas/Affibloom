<?php
/**
 * Settings screen. As in Lasso, the page title is the tab's name and the tabs sit to its right.
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
		<div class="affibloom-page__head">
			<h1 class="affibloom-page__title"><?php echo esc_html( $labels[ $view['tab'] ] ); ?></h1>

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

		<form class="affibloom-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $view['tab'] ); ?>">
			<?php wp_nonce_field( $view['action'] ); ?>

			<div class="affibloom-card">
				<?php if ( 'general' === $view['tab'] ) : ?>
					<section class="affibloom-section">
						<h2 class="affibloom-section__title"><?php esc_html_e( 'Link Defaults', 'affibloom' ); ?></h2>
						<p class="affibloom-section__intro"><?php esc_html_e( 'Set the default attributes for new links.', 'affibloom' ); ?></p>
						<?php
						// Lasso's labels and help texts (admin/views/settings/general.php), in Lasso's order.
						$toggles = array(
							'new_tab'         => array( __( 'New Window / Tab', 'affibloom' ), __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ) ),
							'nofollow'        => array( __( 'NoFollow / NoIndex', 'affibloom' ), __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ) ),
							'sponsored'       => array( __( 'Sponsored', 'affibloom' ), __( 'When enabled, this link will be set to sponsored.', 'affibloom' ) ),
							'show_disclosure' => array( __( 'Show Disclosure', 'affibloom' ), __( 'When enabled, this link will show the disclosure.', 'affibloom' ) ),
						);
						foreach ( $toggles as $key => [ $label, $help ] ) {
							$this->render(
								'admin/partials/toggle',
								array(
									'id'      => 'affibloom-setting-' . $key,
									'name'    => 'settings[' . $key . ']',
									'label'   => $label,
									'checked' => $settings->bool( $key ),
									'help'    => $help,
								)
							);
						}
						?>
					</section>
				<?php endif; ?>
			</div>

			<div class="affibloom-form__actions">
				<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
			</div>
		</form>
	</div>

	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
</div>
