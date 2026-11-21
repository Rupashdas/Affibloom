<?php
/**
 * One place on the Locations tab (F12 L7; Lasso's link-display-row.php; Task 36B): Content (the host, left empty in a
 * group's nested rows), Link with Context and Type, both opening the preview (Task 36C), and Clicks.
 *
 * @package Affibloom
 * @var array{host: \Affibloom\Domain\LinkIndex\LocationHost, place: \Affibloom\Domain\LinkIndex\ProductLocation, nested: bool, clicks?: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\LinkIndex\ProductLocation;

$host        = $view['host'];
$place       = $view['place'];
$place_types = array(
	ProductLocation::TEXT    => array( 'link', __( 'This is a text link.', 'affibloom' ) ),
	ProductLocation::IMAGE   => array( 'image', __( 'This is an image link.', 'affibloom' ) ),
	ProductLocation::SINGLE  => array( 'box', __( 'This is a single link display.', 'affibloom' ) ),
	ProductLocation::GRID    => array( 'grid', __( 'This is a group of links display.', 'affibloom' ) ),
	ProductLocation::LIST    => array( 'list', __( 'This is a group of links display.', 'affibloom' ) ),
	ProductLocation::GALLERY => array( 'gallery', __( 'This is a group of links display.', 'affibloom' ) ),
);
$place_type  = $place_types[ $place->kind() ];
?>
<tr class="affibloom-location<?php echo $view['nested'] ? ' affibloom-location--nested' : ''; ?>"
	<?php echo $view['nested'] ? 'data-location-of="' . esc_attr( (string) $host->id ) . '" hidden' : ''; ?>>
	<td class="affibloom-col-content">
		<?php
		if ( ! $view['nested'] ) {
			$this->render( 'admin/partials/location-host', array( 'host' => $host ) );
		}
		?>
	</td>
	<td class="affibloom-col-link">
		<button type="button" class="affibloom-location__context<?php echo $place->is_display() ? ' affibloom-location__context--code' : ''; ?>" data-location-preview="<?php echo esc_attr( (string) $place->id ); ?>"
			title="<?php echo esc_attr( $place->context() ); ?>"><?php echo esc_html( $place->context() ); ?></button>
	</td>
	<td class="affibloom-col-type">
		<button type="button" class="affibloom-alert affibloom-location__type" data-location-preview="<?php echo esc_attr( (string) $place->id ); ?>" data-tip="<?php echo esc_attr( $place_type[1] ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $place_type[0] ) ); ?>
			<span class="screen-reader-text"><?php echo esc_html( $place_type[1] ); ?></span>
		</button>
	</td>
	<?php // Clicks in the last 30 days (L11, D25; Task 34C). ?>
	<td class="affibloom-col-clicks"><?php echo esc_html( number_format_i18n( $view['clicks'] ?? 0 ) ); ?></td>
</tr>
