<?php
/**
 * The few WP-CLI methods the plugin calls, for PHPStan only (php-stubs/wp-cli-stubs is not installed: Packagist is
 * unreachable from this machine, environment.md → Known issues). Never loaded at run time.
 *
 * @package Affibloom
 */

// phpcs:ignoreFile -- a signature stub, not plugin code.

class WP_CLI {
	/**
	 * @param callable|object|string $callable
	 * @param array<string, mixed>   $args
	 */
	public static function add_command( string $name, $callable, array $args = array() ): bool {}

	public static function success( string $message ): void {}

	/**
	 * @param string|\WP_Error|\Exception|\Throwable $message
	 * @param bool|int                               $exit
	 * @return never
	 */
	public static function error( $message, $exit = true ) {}
}
