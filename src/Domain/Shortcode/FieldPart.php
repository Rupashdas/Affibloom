<?php
/**
 * One value of a product printed inline.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Shortcode;

/**
 * The parts `type="field" value="<part>"` prints (F31 R11, A14; Lasso's AAWP_SUPPORT_FIELDS_VALUE); any other value
 * prints nothing.
 */
enum FieldPart: string {

	case Title           = 'title';
	case Image           = 'image';
	case Thumb           = 'thumb';
	case Button          = 'button';
	case Price           = 'price';
	case ListPrice       = 'list_price';
	case AmountSaved     = 'amount_saved';
	case PercentageSaved = 'percentage_saved';
	case Rating          = 'rating';
	case StarRating      = 'star_rating';
	case Description     = 'description';
	case Reviews         = 'reviews';
	case Url             = 'url';
	case Link            = 'link';
	case LastUpdate      = 'last_update';
}
