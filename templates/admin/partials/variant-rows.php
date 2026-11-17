<?php
/**
 * The Variants tab's rows (Lasso's url-variants.js 185-282, F27 R8): the name in bold with the address under it (a new
 * tab), the key, and Copy, which puts the address on the clipboard and says "Copied!" (D5). Each row carries its
 * values for the search.
 *
 * @package Affibloom
 * @var array{rows: list<array{id: int, name: string, key: string, url: string}>} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<?php foreach ( $view['rows'] as $variant_row ) : ?>
	<tr data-variant-row data-variant-id="<?php echo esc_attr( (string) $variant_row['id'] ); ?>"
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
		<td class="affibloom-col-actions"></td>
	</tr>
<?php endforeach; ?>
