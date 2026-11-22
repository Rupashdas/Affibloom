<?php
/**
 * A Lasso-style switch row: the switch on the left, its label, and a "?" help tip when there is help text.
 *
 * Used where a screen follows Lasso's structure (Display's Disclosure & behavior, the Amazon tab). Extracted on its
 * second use. Settings → General uses it too, in Lasso's structure (deviation 96); Aurora's row with an icon chip
 * (partials/toggle.php) is gone (Task MD6).
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, checked: bool, help?: string, note?: string, disabled?: bool, data?: array<string, string>} $view
 */

defined( 'ABSPATH' ) || exit;

$help    = $view['help'] ?? '';
$help_id = $view['id'] . '-help';
// A line of text under the row, always shown (the Amazon tab's switches that needed an outside cloud, Task C1L).
$note      = $view['note'] ?? '';
$note_id   = $view['id'] . '-note';
$described = trim( ( '' === $help ? '' : $help_id ) . ( '' === $note ? '' : ' ' . $note_id ) );
// A switch that cannot be changed for now (Google Analytics' switches while there is no Tracking ID, Task 9F), and any
// data attributes a page script reads (the link defaults' "Update Link Settings" dialog, Task 9D).
$disabled = $view['disabled'] ?? false;
$data     = '';
foreach ( $view['data'] ?? array() as $attribute => $value ) {
	$data .= ' data-' . esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
}
?>
<div class="affibloom-toggle-row">
	<span class="affibloom-toggle">
		<input type="checkbox" class="affibloom-toggle__input" role="switch" id="<?php echo esc_attr( $view['id'] ); ?>"
			name="<?php echo esc_attr( $view['name'] ); ?>" value="1" <?php checked( $view['checked'] ); ?>
			<?php echo '' === $described ? '' : 'aria-describedby="' . esc_attr( $described ) . '"'; ?><?php disabled( $disabled ); ?><?php echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() above. ?>>
		<span class="affibloom-toggle__track" aria-hidden="true"></span>
	</span>
	<label class="affibloom-toggle-row__label" for="<?php echo esc_attr( $view['id'] ); ?>"><?php echo esc_html( $view['label'] ); ?></label>
	<?php
	if ( '' !== $help ) {
		$this->render(
			'admin/partials/help',
			array(
				'id'    => $help_id,
				'text'  => $help,
				'label' => $view['label'],
			)
		);
	}
	?>
</div>
<?php if ( '' !== $note ) : ?>
	<p class="affibloom-toggle-row__note" id="<?php echo esc_attr( $note_id ); ?>"><?php echo esc_html( $note ); ?></p>
<?php endif; ?>
