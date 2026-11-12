<?php
/**
 * Settings screen: the band with the title, subtitle (U10) and section tabs, then one card per section with
 * Save in the last card's footer (docs/ui/design-system.md, Aurora). Labels, help texts and their order are
 * Lasso's (docs/spec/interface.md).
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>, settings: \Affibloom\Domain\Settings\Settings, action: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'settings' ) ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/settings-band',
			array(
				'tab'  => $view['tab'],
				'tabs' => $view['tabs'],
			)
		);
		?>

		<div class="affibloom-page__body">
			<form class="affibloom-settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $view['tab'] ); ?>">
				<?php wp_nonce_field( $view['action'] ); ?>

				<?php if ( 'general' === $view['tab'] ) : ?>
					<div class="affibloom-card">
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
						<div class="affibloom-card__foot">
							<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( 'amazon' === $view['tab'] ) : ?>
					<div class="affibloom-card">
						<div class="affibloom-card__head">
							<h2 class="affibloom-card__title"><?php esc_html_e( 'Amazon Associates Accounts', 'affibloom' ); ?></h2>
							<p class="affibloom-card__intro"><?php esc_html_e( 'The tracking ID added to every Amazon link on this site.', 'affibloom' ); ?></p>
						</div>
						<div class="affibloom-card__body affibloom-card__body--fields">
							<div class="affibloom-field affibloom-field--text">
								<div class="affibloom-field__head">
									<label class="affibloom-label" for="affibloom-setting-tracking_id"><?php esc_html_e( 'Tracking ID for This Site', 'affibloom' ); ?></label>
								</div>
								<input type="text" class="affibloom-input" id="affibloom-setting-tracking_id" name="settings[tracking_id]"
									value="<?php echo esc_attr( $settings->string( 'tracking_id' ) ); ?>"
									placeholder="tracking-20" pattern="[A-Za-z0-9][A-Za-z0-9\-]{0,62}-[0-9]{2}">
							</div>
						</div>
					</div>

					<div class="affibloom-card">
						<div class="affibloom-card__head">
							<h2 class="affibloom-card__title"><?php esc_html_e( 'Amazon Preferences', 'affibloom' ); ?></h2>
							<p class="affibloom-card__intro"><?php esc_html_e( 'What Amazon products show in the box.', 'affibloom' ); ?></p>
						</div>
						<div class="affibloom-card__body">
							<?php
							$this->render(
								'admin/partials/toggle',
								array(
									'id'      => 'affibloom-setting-show_prime',
									'name'    => 'settings[show_prime]',
									'label'   => __( 'Show Prime Logo In Displays', 'affibloom' ),
									'checked' => $settings->bool( 'show_prime' ),
									'help'    => __( 'Show the blue Prime logo beside the price for all eligible Amazon products in your Displays.', 'affibloom' ),
									'icon'    => 'star',
								)
							);
							?>
						</div>
						<div class="affibloom-card__foot">
							<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
						</div>
					</div>
				<?php endif; ?>
			</form>
		</div>
	</div>

	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
</div>
