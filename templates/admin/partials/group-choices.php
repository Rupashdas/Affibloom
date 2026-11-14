<?php
/**
 * The site's groups as JSON, printed once per page for every group picker on it (partials/group-picker.php). Names are
 * encoded for a script block, so nothing in a name can end it.
 *
 * @package Affibloom
 * @var array{groups: list<\Affibloom\Domain\Group\Group>} $view
 */

defined( 'ABSPATH' ) || exit;

$choices = array();
foreach ( $view['groups'] as $choice ) {
	$choices[] = array(
		'id'   => $choice->id,
		'name' => wp_specialchars_decode( $choice->name, ENT_QUOTES ),
	);
}
?>
<script type="application/json" id="affibloom-group-choices"><?php echo wp_json_encode( $choices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
