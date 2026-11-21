<?php
/**
 * The builder's head actions (F25 R13; Task T3; Lasso's tables/header.php): Saved / Saving, Copy Shortcode (F31 R33,
 * Task 18S13: `[affibloom type="table" id="<id>"]`, hidden until the table is saved) and Create Table.
 *
 * @package Affibloom
 * @var array{saved: bool, has_id: bool, shortcode: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="affibloom-table-page__status" data-table-status role="status" aria-live="polite"><?php echo $view['saved'] ? esc_html__( 'Saved', 'affibloom' ) : ''; ?></span>
<button type="button" class="affibloom-button affibloom-button--secondary" data-table-copy="<?php echo esc_attr( $view['shortcode'] ); ?>"
	title="<?php esc_attr_e( 'Copy this Display to your clipboard.', 'affibloom' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'affibloom' ); ?>"
	data-copy-failed="<?php esc_attr_e( 'Could not copy the shortcode.', 'affibloom' ); ?>"<?php echo $view['has_id'] ? '' : ' hidden'; ?>>
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
	<span data-copy-label><?php esc_html_e( 'Copy Shortcode', 'affibloom' ); ?></span>
</button>
<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-open="affibloom-table-create" aria-haspopup="dialog">
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
	<span><?php esc_html_e( 'Create Table', 'affibloom' ); ?></span>
</button>
