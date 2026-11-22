<?php
/**
 * The lowest role that may use Affibloom (Task TL6).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

/**
 * The lowest role that may use Affibloom (F37 R31-R35, AC7; F9 R67; Task TL6; decision C3), stored in the setting
 * `permission`, and the WordPress capability that marks that role and every role above it. Lasso's mapping, with its
 * swap corrected: Contributor is `edit_posts`, Subscriber `read`. Administrator is the default (decision 1), and an
 * unknown value falls back to it, so a broken setting never opens Affibloom to more users.
 */
enum Permission: string {
	case Administrator = 'administrator';
	case Editor        = 'editor';
	case Author        = 'author';
	case Contributor   = 'contributor';
	case Subscriber    = 'subscriber';

	/**
	 * The capability every user of this role and above has.
	 */
	public function capability(): string {
		return match ( $this ) { // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- an enum method; the sniff reads this enum as plain code (FieldType passes).
			self::Administrator => 'manage_options',
			self::Editor        => 'edit_others_posts',
			self::Author        => 'publish_posts',
			self::Contributor   => 'edit_posts',
			self::Subscriber    => 'read',
		};
	}

	/**
	 * The permission a stored value names, administrators for anything else.
	 *
	 * @param string $value The setting.
	 */
	public static function of( string $value ): self {
		return self::tryFrom( $value ) ?? self::Administrator;
	}
}
