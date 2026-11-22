<?php
/**
 * The setup wizard (F38 R47-R56, D17, AC10, AC11; Tasks ON6, ON7). Lasso's steps, words and fields without the Activate
 * step (decision B1), the Imports step (deviation 146) and Commission Upgrades (N7); Affibloom's look. One page: the
 * step bar and a section per step; setup.js shows one at a time, moves the focus to its heading and saves each step's
 * own form. Without the script every step shows, one under the other.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, config: \Affibloom\Domain\Display\DisplayConfig, choices: \Affibloom\Interface\Admin\IconChoices, api: array<string, mixed>} $view
 */

use Affibloom\Domain\Display\Theme;

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];
$steps    = array(
	'theme'     => __( 'Theme', 'affibloom' ),
	'customize' => __( 'Customize', 'affibloom' ),
	'amazon'    => __( 'Amazon', 'affibloom' ),
	'analytics' => __( 'Analytics', 'affibloom' ),
	'done'      => __( 'Done', 'affibloom' ),
);
$chosen   = $view['config']->theme()->name();
// Lasso's order of the theme cards (onboarding/theme.php).
$themes = array( 'Cactus', 'Flow', 'Lab', 'Money', 'Cutter', 'Geek', 'Llama', 'Splash' );
$themes = array_values( array_intersect( $themes, Theme::NAMES ) );
?>
<div class="wrap affibloom-admin affibloom-setup" data-setup data-dashboard="<?php echo esc_url( admin_url( 'admin.php?page=affibloom' ) ); ?>"
	data-failed="<?php esc_attr_e( "Try again. For some reason your settings didn't save.", 'affibloom' ); ?>">
	<div class="affibloom-setup__brand">
		<?php $this->render( 'admin/partials/logo' ); ?>
		<span><?php esc_html_e( 'Affibloom', 'affibloom' ); ?></span>
	</div>

	<nav class="affibloom-steps" aria-label="<?php esc_attr_e( 'Setup steps', 'affibloom' ); ?>">
		<ol class="affibloom-steps__list">
			<?php $number = 0; ?>
			<?php foreach ( $steps as $step => $label ) : ?>
				<li class="affibloom-steps__item">
					<button type="button" class="affibloom-steps__button" data-step-go="<?php echo esc_attr( $step ); ?>" <?php echo 'theme' === $step ? 'aria-current="step"' : ''; ?>>
						<span class="affibloom-steps__number" aria-hidden="true"><?php echo esc_html( (string) ++$number ); ?></span>
						<span class="affibloom-steps__label"><?php echo esc_html( $label ); ?></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<div class="affibloom-setup__card">
		<?php // Theme (R49): a click picks the theme, draws the box with it and opens Customize. ?>
		<section class="affibloom-setup__step" data-step="theme" aria-labelledby="affibloom-setup-theme">
			<h1 class="affibloom-setup__title" id="affibloom-setup-theme" tabindex="-1"><?php esc_html_e( 'Choose a Default Display Box Theme', 'affibloom' ); ?></h1>
			<p class="affibloom-setup__intro"><?php esc_html_e( 'This is an example of how a product will look on your site.', 'affibloom' ); ?></p>
			<div class="affibloom-setup__themes" role="group" aria-label="<?php esc_attr_e( 'Themes', 'affibloom' ); ?>">
				<?php foreach ( $themes as $theme_name ) : ?>
					<button type="button" class="affibloom-setup__theme" data-theme="<?php echo esc_attr( $theme_name ); ?>" aria-pressed="<?php echo $theme_name === $chosen ? 'true' : 'false'; ?>">
						<?php // The theme's own box, drawn small by setup.js (the Display screen's preview route). ?>
						<span class="affibloom-setup__theme-shot" data-theme-shot aria-hidden="true"></span>
						<span class="affibloom-setup__theme-name"><?php echo esc_html( $theme_name ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<?php // Customize (R50): the single box's colours and texts, Show Price, Show Disclosure and the disclosure text. ?>
		<section class="affibloom-setup__step" data-step="customize" aria-labelledby="affibloom-setup-customize">
			<h1 class="affibloom-setup__title" id="affibloom-setup-customize" tabindex="-1"><?php esc_html_e( 'Customize Your Displays', 'affibloom' ); ?></h1>
			<p class="affibloom-setup__intro"><?php esc_html_e( "Don't worry, you can easily change all of these settings later.", 'affibloom' ); ?></p>
			<form class="affibloom-setup__form" data-setup-form="customize" data-rest-save="/affibloom/v1/settings/display" novalidate>
				<input type="hidden" name="tab" value="setup_display">
				<input type="hidden" name="type" value="single">
				<input type="hidden" name="display[single][theme]" value="<?php echo esc_attr( $chosen ); ?>" data-setup-theme>
				<div class="affibloom-setup__split">
					<div class="affibloom-setup__fields">
						<?php
						$this->render(
							'admin/partials/display-colors-accordion',
							array(
								'config'  => $view['config'],
								'name'    => 'display[single]',
								'choices' => $view['choices'],
							)
						);
						$this->render(
							'admin/partials/switch-row',
							array(
								'id'      => 'affibloom-setting-show_price',
								'name'    => 'settings[show_price]',
								'label'   => __( 'Show Price', 'affibloom' ),
								'checked' => $settings->bool( 'show_price' ),
								'help'    => __( 'Turn this on to show the price in Displays by default. You can override this per display.', 'affibloom' ),
							)
						);
						$this->render(
							'admin/partials/switch-row',
							array(
								'id'      => 'affibloom-setting-show_disclosure',
								'name'    => 'settings[show_disclosure]',
								'label'   => __( 'Show Disclosure', 'affibloom' ),
								'checked' => $settings->bool( 'show_disclosure' ),
								'help'    => __( 'Turn this on to show the disclosure in single displays by default. You can override this per display.', 'affibloom' ),
							)
						);
						?>
						<div class="affibloom-field">
							<label class="affibloom-label" for="affibloom-setting-disclosure_text"><?php esc_html_e( 'Disclosure text', 'affibloom' ); ?></label>
							<textarea class="affibloom-input" id="affibloom-setting-disclosure_text" name="settings[disclosure_text]" rows="3"
								aria-describedby="affibloom-setting-disclosure_text-hint"><?php echo esc_textarea( $settings->string( 'disclosure_text' ) ); ?></textarea>
							<p class="affibloom-field__hint" id="affibloom-setting-disclosure_text-hint"><?php esc_html_e( 'This is the default disclosure text used with your displays.', 'affibloom' ); ?></p>
						</div>
					</div>
					<div class="affibloom-setup__preview is-loading" data-setup-preview
						data-preview-title="<?php esc_attr_e( 'Display preview', 'affibloom' ); ?>"
						data-preview-error="<?php esc_attr_e( 'The preview could not be drawn. Try again.', 'affibloom' ); ?>"></div>
				</div>
				<div class="affibloom-setup__actions">
					<button type="button" class="affibloom-button affibloom-button--secondary" data-step-go="theme"><?php esc_html_e( '← Change Themes', 'affibloom' ); ?></button>
					<span class="affibloom-setup__spacer"></span>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-setup-skip><?php esc_html_e( 'Skip →', 'affibloom' ); ?></button>
					<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save and Continue →', 'affibloom' ); ?></button>
				</div>
			</form>
		</section>

		<?php // Amazon (R53, N7): Lasso's fields without Commission Upgrades; the Amazon tab's own parts. ?>
		<section class="affibloom-setup__step" data-step="amazon" aria-labelledby="affibloom-setup-amazon">
			<h1 class="affibloom-setup__title" id="affibloom-setup-amazon" tabindex="-1"><?php esc_html_e( 'Amazon Associates', 'affibloom' ); ?></h1>
			<form class="affibloom-setup__form affibloom-settings" data-setup-form="amazon" data-rest-save="/affibloom/v1/settings/tabs/setup_amazon" novalidate>
				<div class="affibloom-card">
					<div class="affibloom-card__head">
						<h2 class="affibloom-card__title"><?php esc_html_e( 'Amazon Associates Accounts', 'affibloom' ); ?></h2>
					</div>
					<div class="affibloom-card__body affibloom-card__body--fields">
						<?php $this->render( 'admin/partials/amazon-tracking-id-field', array( 'settings' => $settings ) ); ?>
					</div>
				</div>
				<?php
				$this->render( 'admin/partials/amazon-auto-monetize-card', array( 'settings' => $settings ) );
				$this->render(
					'admin/partials/amazon-api-card',
					array(
						'settings' => $settings,
						'api'      => $view['api'],
					)
				);
				?>
				<div class="affibloom-card">
					<div class="affibloom-card__head">
						<h2 class="affibloom-card__title"><?php esc_html_e( 'Amazon Preferences', 'affibloom' ); ?></h2>
					</div>
					<div class="affibloom-card__body affibloom-card__body--fields">
						<?php
						$preferences = array(
							'show_prime'            => array( __( 'Show Prime Logo In Displays', 'affibloom' ), __( 'Show the blue Prime logo beside the price for all eligible Amazon products in your Displays.', 'affibloom' ) ),
							'show_discount_pricing' => array( __( 'Show Discount Pricing', 'affibloom' ), __( 'Show the base Amazon price as strikethrough text, followed by the discounted price in bold.', 'affibloom' ) ),
						);
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
					</div>
				</div>
				<div class="affibloom-setup__actions">
					<span class="affibloom-setup__spacer"></span>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-setup-skip><?php esc_html_e( 'Skip →', 'affibloom' ); ?></button>
					<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save and Continue →', 'affibloom' ); ?></button>
				</div>
			</form>
		</section>

		<?php // Analytics (R54): the General tab's Google Analytics card. ?>
		<section class="affibloom-setup__step" data-step="analytics" aria-labelledby="affibloom-setup-analytics">
			<h1 class="affibloom-setup__title" id="affibloom-setup-analytics" tabindex="-1"><?php esc_html_e( 'Google Analytics', 'affibloom' ); ?></h1>
			<p class="affibloom-setup__intro"><?php esc_html_e( 'Clicks are sent to Google Analytics as affibloom_primary_click, affibloom_secondary_click and affibloom_link_click, counted from today.', 'affibloom' ); ?></p>
			<form class="affibloom-setup__form affibloom-settings" data-setup-form="analytics" data-rest-save="/affibloom/v1/settings/tabs/setup_analytics" novalidate>
				<?php $this->render( 'admin/partials/settings-general-analytics', array( 'settings' => $settings ) ); ?>
				<div class="affibloom-setup__actions">
					<span class="affibloom-setup__spacer"></span>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-setup-skip><?php esc_html_e( 'Skip →', 'affibloom' ); ?></button>
					<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save and Continue →', 'affibloom' ); ?></button>
				</div>
			</form>
		</section>

		<?php // Done (R56): Go to Dashboard ends the wizard (POST /setup/done). ?>
		<section class="affibloom-setup__step affibloom-setup__done" data-step="done" aria-labelledby="affibloom-setup-done">
			<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'check' ) ); ?></span>
			<h1 class="affibloom-setup__title" id="affibloom-setup-done" tabindex="-1"><?php esc_html_e( "You're Ready", 'affibloom' ); ?></h1>
			<p class="affibloom-setup__intro"><?php esc_html_e( "That's it! You're now ready to add displays to your site.", 'affibloom' ); ?></p>
			<a class="affibloom-button affibloom-button--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom' ) ); ?>" data-setup-done><?php esc_html_e( 'Go to Dashboard', 'affibloom' ); ?></a>
		</section>
	</div>

	<?php
	$this->render( 'admin/partials/amazon-enable-dialog' );
	$this->render( 'admin/partials/toast-template' );
	$this->render( 'admin/partials/footer' );
	?>
</div>
