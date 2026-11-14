<?php
/**
 * The kinds of custom field Lasso has (F1 Task 14C; F26 Task FL1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * The kinds of custom field Lasso has, with its labels. Seven are offered when a field is created
 * (Html_Helper::render_field_types); the other five belong to Lasso's built-in fields only (F26 R3, D3): Price, Hyperlink,
 * Image, Rich text and Product name.
 */
enum FieldType: string {

	case Text          = 'text';
	case CopyClipboard = 'copy_clipboard';
	case Textarea      = 'textarea';
	case Number        = 'number';
	case Rating        = 'rating';
	case BulletedList  = 'bulleted_list';
	case NumberedList  = 'numbered_list';
	case Label         = 'label';
	case Button        = 'button';
	case Image         = 'image';
	case Editor        = 'editor';
	case ProductName   = 'product_name';

	/**
	 * Lasso's label in the Field Type select (the built-in kinds are Lasso's own names for them).
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
			self::Label         => 'Price',
			self::Button        => 'Hyperlink',
			self::Image         => 'Image',
			self::Editor        => 'Rich text',
			self::ProductName   => 'Product name',
		};
	}

	/**
	 * The kinds a field can be created or changed to, in Lasso's order (Short Text first): Lasso's seven.
	 *
	 * @return list<self>
	 */
	public static function choosable(): array {
		return array(
			self::Text,
			self::CopyClipboard,
			self::Textarea,
			self::Number,
			self::Rating,
			self::BulletedList,
			self::NumberedList,
		);
	}

	/**
	 * Whether a field can be created, or changed, to this kind.
	 */
	public function is_choosable(): bool {
		return in_array( $this, self::choosable(), true );
	}

	/**
	 * The value is several lines (Lasso's textarea row: Long Text and both lists).
	 */
	public function is_multiline(): bool {
		return in_array( $this, array( self::Textarea, self::BulletedList, self::NumberedList ), true );
	}

	/**
	 * The icon the Fields list shows for this kind (Lasso's rule, field-row-count.php; Lasso draws none for the two
	 * list kinds, F26 D16 gives them list icons). The names are partials/icon.php's.
	 */
	public function icon(): string {
		return match ( $this ) {
			self::Text, self::ProductName => 'text',
			self::CopyClipboard           => 'copy',
			self::Textarea, self::Editor  => 'paragraph',
			self::Number                  => 'hash',
			self::Rating                  => 'star',
			self::BulletedList            => 'list',
			self::NumberedList            => 'list-ordered',
			self::Label                   => 'dollar',
			self::Button                  => 'link',
			self::Image                   => 'image',
		};
	}
}
