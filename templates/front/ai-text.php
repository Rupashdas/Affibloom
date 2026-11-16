<?php
/**
 * A stored AI text (F30 R43; Lasso's aitext.php under Affibloom's names): the text in three containers, through
 * `wp_kses_post` (D14; Lasso prints it raw).
 *
 * @package Affibloom
 * @var string $text The stored text.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-box affibloom-ai">
	<div class="affibloom-ai-text-container">
		<div class="affibloom-ai-text">
			<?php echo wp_kses_post( $text ); ?>
		</div>
	</div>
</div>
