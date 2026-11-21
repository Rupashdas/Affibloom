<?php
/**
 * A comparison table for readers (F25 R43, R44, R51; Task T12; Lasso's displays/table.php under Affibloom's names): the
 * wrapper `div.affibloom-display-table.ls-dp.affibloom-table-<theme>-theme` with its anchor and `data-nosnippet`, the
 * layout; under it the Amazon prices' timestamp and the disclosure. A table without products prints only the
 * disclosure.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Shortcode\TableReaderView $table
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( array() !== $table->rows ) : ?>
	<?php $attributes = $table->wrapper; ?>
	<div id="<?php echo esc_attr( $table->anchor ); ?>" class="<?php echo esc_attr( $table->classes ); ?>"<?php echo $table->nosnippet ? ' data-nosnippet' : ''; ?><?php require __DIR__ . '/partials/attributes.php'; ?>>
		<?php
		// T13 adds the Vertical layout.
		require __DIR__ . '/table-horizontal.php';
		?>
	</div>
	<?php if ( null !== $table->timestamp ) : ?>
		<span class="oldest-updated"><?php echo esc_html( $table->timestamp ); ?></span>
	<?php endif; ?>
<?php endif; ?>
<?php if ( null !== $table->disclosure ) : ?>
	<div class="affibloom-disclosure"><?php echo esc_html( $table->disclosure ); ?></div>
<?php endif; ?>
