<?php
/**
 * DB Status (F37 R9-R11, D4, AC5; Task TL4): a hidden Settings page, reached by its address only, as Lasso's
 * `page=settings-db`. Every Affibloom table (all of them, where Lasso lists 26 of its 29) with what creating or updating
 * it did on this load.
 *
 * @package Affibloom
 * @var array{tabs: list<string>, rows: list<array{name: string, output: list<string>}>} $view
 */

defined( 'ABSPATH' ) || exit;
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
				'title'    => __( 'DB Status', 'affibloom' ),
				'subtitle' => __( "Affibloom's tables, checked and brought up to date as this page loads.", 'affibloom' ),
			)
		);
		?>

		<div class="affibloom-page__body">
			<div class="affibloom-card affibloom-card--list">
				<table class="affibloom-table affibloom-db-status">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Table Name', 'affibloom' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Output', 'affibloom' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $view['rows'] as $row ) : ?>
							<tr>
								<td class="affibloom-db-status__name"><code><?php echo esc_html( $row['name'] ); ?></code></td>
								<td class="affibloom-db-status__output">
									<?php foreach ( $row['output'] as $line ) : ?>
										<div><?php echo esc_html( $line ); ?></div>
									<?php endforeach; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
