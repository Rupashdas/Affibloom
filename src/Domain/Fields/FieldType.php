<?php
/**
 * The kinds of custom field Lasso offers (F1, Task 14C).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * The kinds of custom field Lasso offers when one is created (Html_Helper::render_field_types), with its labels.
 */
enum FieldType: string {

	case Text          = 'text';
	case CopyClipboard = 'copy_clipboard';
	case Textarea      = 'textarea';
	case Number        = 'number';
	case Rating        = 'rating';
	case BulletedList  = 'bulleted_list';
	case NumberedList  = 'numbered_list';

	/**
	 * Lasso's label in the Field Type select.
	 */
	public function label(): string {
		return match ( $this ) {
			self::Text          => 'Short Text',
			self::CopyClipboard => 'Copy to Clipboard',
			self::Textarea      => 'Long Text',
			self::Number        => 'Number',
			self::Rating        => 'Rating',
			self::BulletedList  => 'Bulleted List',
			self::NumberedList  => 'Numbered List',
		};
	}

	/**
	 * The value is several lines (Lasso's textarea row: Long Text and both lists).
	 */
	public function is_multiline(): bool {
		return in_array( $this, array( self::Textarea, self::BulletedList, self::NumberedList ), true );
	}
}
