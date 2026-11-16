<?php
/**
 * The custom fields block (F5 R23, R30-R34; Lasso's field-row.php under Affibloom's names): Pros and Cons as lists with
 * check or cross icons, rating fields as stars, bulleted and numbered lists, Copy to Clipboard with its button, and
 * every other type as text with its line breaks. Names and values are escaped (D4; Lasso prints them raw).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Box\BoxField;

if ( array() === $view->fields ) {
	return;
}
?>
<div class="affibloom-fields">
	<?php foreach ( $view->fields as $field ) : ?>
		<?php if ( BoxField::PROS_CONS === $field->kind ) : ?>
			<div class="affibloom-fields-<?php echo esc_attr( strtolower( $field->name ) ); ?> affibloom-fields-<?php echo esc_attr( (string) $field->id ); ?>">
				<strong><?php echo esc_html( $field->name ); ?>:</strong>
				<?php if ( array() !== $field->lines() ) : ?>
					<ul>
						<?php foreach ( $field->lines() as $line ) : ?>
							<?php if ( 2 === $field->id ) : ?>
								<li><span class="affibloom-check"><span class="affibloom-check-content"></span></span><?php echo esc_html( $line ); ?></li>
							<?php else : ?>
								<li><span class="affibloom-x"><span class="affibloom-x-1"></span><span class="affibloom-x-2"></span></span><?php echo esc_html( $line ); ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php elseif ( BoxField::RATING === $field->kind ) : ?>
			<div class="affibloom-fields-single">
				<strong><?php echo esc_html( $field->name ); ?>:</strong>
				<div class="affibloom-stars" style="--rating: <?php echo esc_attr( is_numeric( trim( $field->value ) ) ? trim( $field->value ) : '0' ); ?>">
					<span class="affibloom-stars-value">
						<?php echo esc_html( number_format( (float) $field->value, 1, '.', '' ) ); ?>
					</span>
				</div>
			</div>
		<?php elseif ( BoxField::BULLETED === $field->kind || BoxField::NUMBERED === $field->kind ) : ?>
			<?php $list_tag = BoxField::BULLETED === $field->kind ? 'ul' : 'ol'; ?>
			<div class="affibloom-fields-single">
				<strong><?php echo esc_html( $field->name ); ?>:</strong>
				<div class="affibloom-fields-<?php echo esc_attr( sanitize_title( $field->name ) ); ?> affibloom-fields-<?php echo esc_attr( (string) $field->id ); ?>">
					<?php if ( array() !== $field->lines() ) : ?>
						<<?php echo tag_escape( $list_tag ); ?> class="list">
							<?php foreach ( $field->lines() as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</<?php echo tag_escape( $list_tag ); ?>>
					<?php endif; ?>
				</div>
			</div>
		<?php elseif ( BoxField::COPY === $field->kind ) : ?>
			<div class="affibloom-fields-single affibloom-field-copy-clipboard">
				<strong><?php echo esc_html( $field->name ); ?>:</strong>
				<?php if ( '' !== $field->value ) : ?>
					<span class="affibloom-copy-clipboard-value"><?php echo esc_html( $field->value ); ?></span><button type="button" class="affibloom-copy-clipboard-btn" aria-label="<?php esc_attr_e( 'Copy to clipboard', 'affibloom' ); ?>" data-copy-value="<?php echo esc_attr( $field->value ); ?>"><svg class="affibloom-copy-clipboard-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg></button>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="affibloom-fields-single">
				<strong><?php echo esc_html( $field->name ); ?>:</strong>
				<?php echo nl2br( esc_html( $field->value ) ); ?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
