<?php
/**
 * The Variants tab's rows (Lasso's url-variants.js 185-282, F27 R8): the name in bold with the address under it (a new
 * tab), the key, Copy, which puts the address on the clipboard and says "Copied!" (D5), Edit and Remove. Each row
 * carries its stored values: the search reads them, and a save sends them for the rows not being edited (R25).
 * PUT /products/{id}/variants prints this same partial.
 *
 * @package Affibloom
 * @var array{rows: list<array{id: int, name: string, key: string, url: string}>} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<?php foreach ( $view['rows'] as $variant_row ) : ?>
	<tr data-variant-row data-variant-id="<?php echo esc_attr( (string) $variant_row['id'] ); ?>"
		data-name="<?php echo esc_attr( $variant_row['name'] ); ?>" data-key="<?php echo esc_attr( $variant_row['key'] ); ?>"
		data-search="<?php echo esc_attr( strtolower( $variant_row['name'] . ' ' . $variant_row['key'] . ' ' . $variant_row['url'] ) ); ?>">
		<td class="affibloom-col-name">
			<span class="affibloom-variant__name"><?php echo esc_html( $variant_row['name'] ); ?></span>
			<a class="affibloom-row__url" href="<?php echo esc_url( $variant_row['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $variant_row['url'] ); ?></a>
		</td>
		<td class="affibloom-col-key"><code class="affibloom-variant__key"><?php echo esc_html( $variant_row['key'] ); ?></code></td>
		<td class="affibloom-col-copy">
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-variant-copy="<?php echo esc_attr( $variant_row['url'] ); ?>"
				data-copied="<?php esc_attr_e( 'Copied!', 'affibloom' ); ?>" data-copy-failed="<?php esc_attr_e( 'Could not copy the URL.', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
				<span data-copy-label aria-live="polite"><?php esc_html_e( 'Copy', 'affibloom' ); ?></span>
			</button>
		</td>
		<td class="affibloom-col-actions">
			<span class="affibloom-variant__actions">
				<?php /* translators: %s: a variant's name. */ ?>
				<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-variant-edit aria-label="<?php echo esc_attr( sprintf( __( 'Edit %s', 'affibloom' ), $variant_row['name'] ) ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'pencil' ) ); ?>
					<span><?php esc_html_e( 'Edit', 'affibloom' ); ?></span>
				</button>
				<?php /* translators: %s: a variant's name. */ ?>
				<button type="button" class="affibloom-button affibloom-button--small affibloom-button--danger-text" data-variant-remove aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'affibloom' ), $variant_row['name'] ) ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
					<span><?php esc_html_e( 'Remove', 'affibloom' ); ?></span>
				</button>
			</span>
		</td>
	</tr>
<?php endforeach; ?>
