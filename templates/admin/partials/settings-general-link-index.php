<?php
/**
 * Settings → General, Link Index (F9 R20-R21, F21 R51; Task 9J): while a rebuild runs, Build Progress, ETA and Lasso's
 * background sentence; otherwise Last Updated and Links Indexed, and nothing while the index is empty. general-settings.js
 * redraws it every 5 seconds from GET /affibloom/v1/link-index. The support view adds Rebuild Database (R51), which asks
 * "Ready to rebuild?" first (Task 35G).
 *
 * @package Affibloom
 * @var array{link_index: array{status: string, progress_text: string, eta_text: string, last_updated_text: string, links_text: string, links_indexed: int}, support: bool} $view
 */

defined( 'ABSPATH' ) || exit;

$index   = $view['link_index'];
$running = 'running' === $index['status'];
?>
<div class="affibloom-card" id="affibloom-card-link-index" data-link-index>
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Link Index', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Affibloom tracks every link on your site.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<dl class="affibloom-facts" data-link-index-running <?php echo $running ? '' : 'hidden'; ?>>
			<dt><?php esc_html_e( 'Build Progress', 'affibloom' ); ?></dt>
			<dd data-link-index-progress><?php echo esc_html( $index['progress_text'] ); ?></dd>
			<dt><?php esc_html_e( 'ETA', 'affibloom' ); ?></dt>
			<dd data-link-index-eta><?php echo esc_html( $index['eta_text'] ); ?></dd>
		</dl>
		<p class="affibloom-field__hint" data-link-index-sentence <?php echo $running ? '' : 'hidden'; ?>><?php esc_html_e( "We're indexing your links in the background, feel free to do something else.", 'affibloom' ); ?></p>
		<dl class="affibloom-facts" data-link-index-done <?php echo ! $running && $index['links_indexed'] > 0 ? '' : 'hidden'; ?>>
			<dt><?php esc_html_e( 'Last Updated', 'affibloom' ); ?></dt>
			<dd data-link-index-updated><?php echo esc_html( $index['last_updated_text'] ); ?></dd>
			<dt><?php esc_html_e( 'Links Indexed', 'affibloom' ); ?></dt>
			<dd data-link-index-links><?php echo esc_html( $index['links_text'] ); ?></dd>
		</dl>
		<?php if ( $view['support'] ) : ?>
			<p class="affibloom-card__actions">
				<button type="button" class="affibloom-button affibloom-button--secondary" data-link-index-rebuild><?php esc_html_e( 'Rebuild Database', 'affibloom' ); ?></button>
			</p>
		<?php endif; ?>
	</div>
</div>
