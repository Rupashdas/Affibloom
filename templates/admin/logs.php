<?php
/**
 * Logs (F37 R1-R8, D3, AC3; Task TL3): a hidden Settings page, reached by its address only, as Lasso's
 * `page=settings-logs`. The Enable Logs switch and, read only, the newest log of each type with Lasso's titles and
 * descriptions (its name taken out). Lasso's Import Log is not shown: Affibloom imports nothing (deviation 146).
 * No download, clear or search, as Lasso.
 *
 * @package Affibloom
 * @var array{tabs: list<string>, settings: \Affibloom\Domain\Settings\Settings, rest_path: string, notice: string, logs: array<string, string>} $view
 */

defined( 'ABSPATH' ) || exit;

$boxes = array(
	'link_database'    => array( __( 'Link Discovery', 'affibloom' ), __( 'Every time a new link on your site is discovered and stored.', 'affibloom' ) ),
	'amazon'           => array( __( 'Amazon Processing', 'affibloom' ), __( 'Each time your Amazon links are updated.', 'affibloom' ) ),
	'amazon_api_error' => array( __( 'Amazon Product API Errors', 'affibloom' ), __( 'Any errors that occur when trying to hit the Amazon Product API.', 'affibloom' ) ),
	'url_issue_check'  => array( __( 'Link Alerts', 'affibloom' ), __( 'Every time a health check finds a problem with a URL.', 'affibloom' ) ),
	'duplicated_slug'  => array( __( 'Duplicate Slug Changes', 'affibloom' ), __( 'Any slug conflicts we detect when changing a permalink.', 'affibloom' ) ),
	'sql_errors'       => array( __( 'SQL Errors', 'affibloom' ), __( 'Any database errors that occur.', 'affibloom' ) ),
);
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/settings-band',
			array(
				'tab'      => '',
				'tabs'     => $view['tabs'],
				'title'    => __( 'Logs', 'affibloom' ),
				'subtitle' => __( 'What Affibloom did in the background, newest file of each kind.', 'affibloom' ),
			)
		);
		?>

		<div class="affibloom-page__body">
			<form class="affibloom-settings" method="post" data-save-when-changed data-unsaved-guard data-rest-save="<?php echo esc_attr( $view['rest_path'] ); ?>"
				data-failed-text="<?php esc_attr_e( "Try again. For some reason your settings didn't save.", 'affibloom' ); ?>">
				<div class="affibloom-card" id="affibloom-card-logs">
					<div class="affibloom-card__body affibloom-card__body--fields">
						<?php
						$this->render(
							'admin/partials/switch-row',
							array(
								'id'      => 'affibloom-setting-enable_logs',
								'name'    => 'settings[enable_logs]',
								'label'   => __( 'Enable Logs', 'affibloom' ),
								'checked' => $view['settings']->bool( 'enable_logs' ),
								'note'    => __( 'Off, only database errors and table repairs are written. Logs are kept for 14 days.', 'affibloom' ),
							)
						);
						?>
					</div>
				</div>

				<div class="affibloom-logs">
					<?php foreach ( $boxes as $log_type => $words ) : ?>
						<section class="affibloom-card affibloom-log" aria-labelledby="affibloom-log-<?php echo esc_attr( $log_type ); ?>">
							<div class="affibloom-card__head">
								<h2 class="affibloom-card__title" id="affibloom-log-<?php echo esc_attr( $log_type ); ?>"><?php echo esc_html( $words[0] ); ?></h2>
								<p class="affibloom-card__intro"><?php echo esc_html( $words[1] ); ?></p>
							</div>
							<div class="affibloom-card__body">
								<label class="screen-reader-text" for="affibloom-log-text-<?php echo esc_attr( $log_type ); ?>"><?php echo esc_html( $words[0] ); ?></label>
								<textarea class="affibloom-input affibloom-log__text" id="affibloom-log-text-<?php echo esc_attr( $log_type ); ?>" rows="8" readonly spellcheck="false"
									placeholder="<?php esc_attr_e( 'Nothing logged yet.', 'affibloom' ); ?>"><?php echo esc_textarea( $view['logs'][ $log_type ] ?? '' ); ?></textarea>
							</div>
						</section>
					<?php endforeach; ?>
				</div>

				<div class="affibloom-form-actions">
					<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php
	$this->render( 'admin/partials/save-modal' );
	$this->render( 'admin/partials/unsaved-modal' );
	$this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) );
	$this->render( 'admin/partials/toast-template' );
	?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
