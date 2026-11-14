<?php
/**
 * Settings → Amazon, in Lasso's structure (the user, 2026-11-13): two columns of cards, Amazon Associates Accounts
 * on the left, Auto-Monetize Amazon Links and Amazon Preferences on the right, switches left of their labels with
 * "?" help tips, and Save at the bottom right. Affibloom's colours and type. Controls, order and help texts:
 * docs/features/F17/requirements.md. The Product Data API card comes with task C1, its first reader.
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>, settings: \Affibloom\Domain\Settings\Settings, action: string, notice: string, refused: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

$settings      = $view['settings'];
$tag_refused   = in_array( 'tracking_id', $view['refused'], true );
$list_refused  = in_array( 'tracking_id_whitelist', $view['refused'], true );
$days_refused  = in_array( 'days_before_out_of_stock_alerts', $view['refused'], true );
$tracking_rule = '^[a-zA-Z0-9\-_.]+-[0-9]{2,3}$';

// Lasso's Amazon Preferences, in Lasso's order, with Lasso's help texts (product name removed).
$preferences = array(
	'show_prime'                      => array( __( 'Show Prime Logo In Displays', 'affibloom' ), __( 'Show the blue Prime logo beside the price for all eligible Amazon products in your Displays.', 'affibloom' ) ),
	'show_discount_pricing'           => array( __( 'Show Discount Pricing', 'affibloom' ), __( 'Show the base Amazon price as strikethrough text, followed by the discounted price in bold.', 'affibloom' ) ),
	'commission_upgrades'             => array( __( 'Commission Upgrades', 'affibloom' ), __( 'Site-wide: when a brand raises commission on products you already link to, clicks are routed to the higher payout at click time.', 'affibloom' ) ),
	'update_pricing_daily'            => array( __( 'Update Amazon pricing daily', 'affibloom' ), __( 'Scan and update all of your Amazon links for the latest price.', 'affibloom' ) ),
	'pricing_locations_only'          => array( __( 'Only update products with Locations', 'affibloom' ), __( 'When enabled, Amazon pricing and link status checks only run for links that appear in Locations. Turn off to include your full catalog.', 'affibloom' ) ),
	'force_change_from_link_details'  => array( __( 'Force changes from link details', 'affibloom' ), __( "When enabled, the tracking ID in posts is updated to the ID from the product's link details page.", 'affibloom' ) ),
	'keep_title_when_url_replaced'    => array( __( 'Keep Title When URL Is Replaced', 'affibloom' ), __( 'When enabled, replacing the Primary Destination URL will keep the current title. Disable to automatically update the title based on the new destination.', 'affibloom' ) ),
	'shorten_amazon_links'            => array( __( 'Shorten Amazon affiliate links', 'affibloom' ), __( 'Remove unnecessary attributes from long Amazon affiliate links for a cleaner reader experience.', 'affibloom' ) ),
	'mobile_deep_linking'             => array( __( 'Mobile Deep Linking', 'affibloom' ), __( 'Open clicks in the Amazon app.', 'affibloom' ) ),
	'international_click'             => array( __( 'Send Readers to Their Local Store', 'affibloom' ), __( 'Readers will be redirected to the Amazon store closest to their detected country.', 'affibloom' ) ),
	'keep_genius_formatting'          => array( __( 'Keep Geni.us Formatting', 'affibloom' ), __( 'Turn this off to remove geni.us cloaking from all links.', 'affibloom' ) ),
	'scan_sitestripe_in_post_content' => array( __( 'Scan SiteStripe in post content', 'affibloom' ), __( 'When enabled, post content is scanned for SiteStripe images, iframes, and page-builder data during link detection. Disable for faster scans if you do not use SiteStripe embeds.', 'affibloom' ) ),
);
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
			<form class="affibloom-settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-save-when-changed data-unsaved-guard>
				<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>">
				<input type="hidden" name="tab" value="amazon">
				<?php wp_nonce_field( $view['action'] ); ?>

				<div class="affibloom-columns">
					<div class="affibloom-columns__column">
						<div class="affibloom-card">
							<div class="affibloom-card__head">
								<h2 class="affibloom-card__title"><?php esc_html_e( 'Amazon Associates Accounts', 'affibloom' ); ?></h2>
								<p class="affibloom-card__intro"><?php esc_html_e( "Enter your primary tracking ID and make sure your international accounts are connected with OneLink. It'll automatically send visitors to their local store.", 'affibloom' ); ?></p>
							</div>
							<div class="affibloom-card__body affibloom-card__body--fields">
								<div class="affibloom-field">
									<label class="affibloom-label" for="affibloom-setting-tracking_id"><?php esc_html_e( 'Tracking ID for This Site', 'affibloom' ); ?></label>
									<input type="text" class="affibloom-input<?php echo $tag_refused ? ' is-invalid' : ''; ?>" id="affibloom-setting-tracking_id" name="settings[tracking_id]"
										value="<?php echo esc_attr( $settings->string( 'tracking_id' ) ); ?>" placeholder="tracking-20"
										data-tracking-rule="<?php echo esc_attr( $tracking_rule ); ?>" aria-describedby="affibloom-setting-tracking_id-error"
										<?php echo $tag_refused ? 'aria-invalid="true"' : ''; ?>>
									<?php // After a refused save the field shows the ID that was kept, so the message says so; typing restores Lasso's message. ?>
									<p class="affibloom-field__error" id="affibloom-setting-tracking_id-error" data-live="<?php esc_attr_e( 'This is an invalid Tracking ID', 'affibloom' ); ?>" <?php echo $tag_refused ? '' : 'hidden'; ?>>
										<?php $tag_refused ? esc_html_e( 'That was not a valid Tracking ID, so the previous one was kept.', 'affibloom' ) : esc_html_e( 'This is an invalid Tracking ID', 'affibloom' ); ?>
									</p>
								</div>

								<?php
								$this->render(
									'admin/partials/switch-row',
									array(
										'id'      => 'affibloom-setting-multiple_tracking_ids',
										'name'    => 'settings[multiple_tracking_ids]',
										'label'   => __( 'Allow Multiple Tracking IDs', 'affibloom' ),
										'checked' => $settings->bool( 'multiple_tracking_ids' ),
									)
								);
								?>

								<div class="affibloom-field affibloom-field--spaced">
									<label class="affibloom-label" for="affibloom-setting-tracking_id_whitelist"><?php esc_html_e( 'Tracking ID Whitelist', 'affibloom' ); ?></label>
									<?php // A plain space-separated field; the script turns it into removable tags, as Lasso's tag list. ?>
									<input type="text" class="affibloom-input<?php echo $list_refused ? ' is-invalid' : ''; ?>" id="affibloom-setting-tracking_id_whitelist"
										name="settings[tracking_id_whitelist]" value="<?php echo esc_attr( $settings->string( 'tracking_id_whitelist' ) ); ?>"
										placeholder="<?php esc_attr_e( 'Select Tracking IDs', 'affibloom' ); ?>" data-tag-list data-tracking-rule="<?php echo esc_attr( $tracking_rule ); ?>"
										<?php /* translators: %s: a tracking ID. */ ?>
										data-remove-label="<?php esc_attr_e( 'Remove %s', 'affibloom' ); ?>" data-clear-label="<?php esc_attr_e( 'Remove all tracking IDs', 'affibloom' ); ?>"
										aria-describedby="affibloom-setting-tracking_id_whitelist-hint affibloom-setting-tracking_id_whitelist-error"
										<?php disabled( ! $settings->bool( 'multiple_tracking_ids' ) ); ?> <?php echo $list_refused ? 'aria-invalid="true"' : ''; ?>>
									<p class="affibloom-field__hint" id="affibloom-setting-tracking_id_whitelist-hint" data-tag-hint><?php esc_html_e( 'Separate tracking IDs with spaces.', 'affibloom' ); ?></p>
									<p class="affibloom-field__error" id="affibloom-setting-tracking_id_whitelist-error" data-live="<?php esc_attr_e( 'This is an invalid Tracking ID', 'affibloom' ); ?>" <?php echo $list_refused ? '' : 'hidden'; ?>>
										<?php $list_refused ? esc_html_e( 'One of those was not a valid Tracking ID, so the previous list was kept.', 'affibloom' ) : esc_html_e( 'This is an invalid Tracking ID', 'affibloom' ); ?>
									</p>
								</div>
							</div>
						</div>
					</div>

					<div class="affibloom-columns__column">
						<div class="affibloom-card">
							<div class="affibloom-card__head">
								<h2 class="affibloom-card__title"><?php esc_html_e( 'Auto-Monetize Amazon Links', 'affibloom' ); ?></h2>
								<p class="affibloom-card__intro"><?php esc_html_e( 'Automatically monetize all current and future Amazon links with your Tracking ID, migrate your SiteStripe displays, and add them to your affiliate dashboard.', 'affibloom' ); ?></p>
							</div>
							<div class="affibloom-card__body affibloom-card__body--fields">
								<?php
								$this->render(
									'admin/partials/switch-row',
									array(
										'id'      => 'affibloom-setting-auto_monetize_amazon',
										'name'    => 'settings[auto_monetize_amazon]',
										'label'   => __( 'Enable Amazon Auto-Monetization', 'affibloom' ),
										'checked' => $settings->bool( 'auto_monetize_amazon' ),
									)
								);
								?>
							</div>
						</div>

						<div class="affibloom-card">
							<div class="affibloom-card__head">
								<h2 class="affibloom-card__title"><?php esc_html_e( 'Amazon Preferences', 'affibloom' ); ?></h2>
							</div>
							<div class="affibloom-card__body affibloom-card__body--fields">
								<?php
								foreach ( $preferences as $key => [ $label, $help ] ) {
									$this->render(
										'admin/partials/switch-row',
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
								<div class="affibloom-toggle-row">
									<input type="number" class="affibloom-input affibloom-input--number<?php echo $days_refused ? ' is-invalid' : ''; ?>" id="affibloom-setting-days_before_out_of_stock_alerts"
										name="settings[days_before_out_of_stock_alerts]" value="<?php echo esc_attr( (string) $settings->int( 'days_before_out_of_stock_alerts' ) ); ?>"
										min="0" max="30" step="1" aria-describedby="affibloom-setting-days_before_out_of_stock_alerts-help" <?php echo $days_refused ? 'aria-invalid="true"' : ''; ?>>
									<label class="affibloom-toggle-row__label" for="affibloom-setting-days_before_out_of_stock_alerts"><?php esc_html_e( 'Days before out of stock alerts', 'affibloom' ); ?></label>
									<?php
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-setting-days_before_out_of_stock_alerts-help',
											'text'  => __( 'Only see out-of-stock alerts after this many days have passed.', 'affibloom' ),
											'label' => __( 'Days before out of stock alerts', 'affibloom' ),
										)
									);
									?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="affibloom-form-actions">
					<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php
	$this->render( 'admin/partials/unsaved-modal' );
	$this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) );
	?>
</div>
