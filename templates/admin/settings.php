<?php
/**
 * Settings → General, in Lasso's structure (F9 R2, D2; Task 9B; the user, 2026-11-13, for the Amazon tab and now this
 * one): two columns of cards, switches left of their labels with "?" help tips, and Save Changes at the bottom right,
 * disabled until something changes. Affibloom's colours and type. Labels, help texts and order are Lasso's
 * (docs/features/F9/requirements.md); the cards Lasso shows that Affibloom does not build yet (Link Index, Permissions,
 * Uninstall) are not shown until their tasks.
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>, settings: \Affibloom\Domain\Settings\Settings, rest_path: string, notice: string, support: bool, post_types: array<string, string>, fields: array<string, string>, has_acf: bool} $view
 */

defined( 'ABSPATH' ) || exit;

$card_data = array(
	'settings' => $view['settings'],
);
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

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
			<?php // Saved over REST, staying on the page (settings-save.js; the one save behaviour, deviation 143). ?>
			<form class="affibloom-settings" method="post" data-save-when-changed data-unsaved-guard data-refresh-values data-rest-save="<?php echo esc_attr( $view['rest_path'] ); ?>"
				data-failed-text="<?php esc_attr_e( "Try again. For some reason your settings didn't save.", 'affibloom' ); ?>">

				<div class="affibloom-columns">
					<div class="affibloom-columns__column">
						<?php
						$this->render( 'admin/partials/settings-general-analytics', $card_data );
						$this->render( 'admin/partials/settings-general-prefix', $card_data );
						$this->render( 'admin/partials/settings-general-smart-css', $card_data );
						if ( $view['support'] ) {
							$this->render( 'admin/partials/settings-general-support', $card_data );
						}
						?>
					</div>

					<div class="affibloom-columns__column">
						<?php
						$this->render(
							'admin/partials/settings-general-detection',
							$card_data + array(
								'post_types' => $view['post_types'],
								'fields'     => $view['fields'],
								'has_acf'    => $view['has_acf'],
							)
						);
						$this->render( 'admin/partials/settings-general-performance', $card_data );
						$this->render( 'admin/partials/settings-general-link-defaults', $card_data );
						$this->render( 'admin/partials/settings-general-notifications', $card_data );
						?>
					</div>
				</div>

				<div class="affibloom-form-actions">
					<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php
	$this->render( 'admin/partials/update-link-settings-modal' );
	$this->render( 'admin/partials/ga-pageview-modal' );
	$this->render( 'admin/partials/rebuild-modal' );
	$this->render( 'admin/partials/save-modal' );
	$this->render( 'admin/partials/unsaved-modal' );
	$this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) );
	$this->render( 'admin/partials/toast-template' );
	?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
