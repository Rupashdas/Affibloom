<?php
/**
 * One row of the Groups list, in Lasso's order (`admin/views/rows/group-row.php`): Name, Description, Links. Lasso makes
 * the whole row a link to the group; here the name is the link and stretches over the row, so the row is clickable and a
 * keyboard or screen reader finds one link.
 *
 * The row opens the group's Details tab until Task G4 adds the Links tab, which becomes the row's target.
 *
 * @package Affibloom
 * @var array{group: \Affibloom\Domain\Group\Group} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\GroupScreen;

$group = $view['group'];
?>
<tr data-group-id="<?php echo esc_attr( (string) $group->id ); ?>">
	<td class="affibloom-col-name">
		<a class="affibloom-row__name affibloom-row__link" href="<?php echo esc_url( GroupScreen::url( $group->id, 'details' ) ); ?>" title="<?php echo esc_attr( $group->name ); ?>"><?php echo esc_html( $group->name ); ?></a>
	</td>
	<td class="affibloom-col-description"><?php echo esc_html( $group->list_description() ); ?></td>
	<td class="affibloom-col-links"><?php echo esc_html( number_format_i18n( $group->count ) ); ?></td>
</tr>
