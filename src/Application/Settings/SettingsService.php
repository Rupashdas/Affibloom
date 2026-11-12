<?php
/**
 * Reading and saving settings, one tab at a time.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Settings;

use InvalidArgumentException;
use LogicException;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Reading and saving settings, one tab at a time.
 */
final class SettingsService {

	/** The fields each tab owns, with the type that decides how a value is cleaned. */
	private const FIELDS = array(
		'general' => array(
			'new_tab'         => 'bool',
			'nofollow'        => 'bool',
			'sponsored'       => 'bool',
			'show_disclosure' => 'bool',
		),
	);

	/**
	 * Builds the service.
	 *
	 * @param SettingsRepository $repository Where settings are kept.
	 */
	public function __construct( private SettingsRepository $repository ) {}

	/**
	 * The current settings.
	 */
	public function current(): Settings {
		return $this->repository->get();
	}

	/**
	 * Tab keys, in display order.
	 *
	 * @return list<string>
	 */
	public function tabs(): array {
		return array_keys( self::FIELDS );
	}

	/**
	 * Saves the fields of one tab. Invalid values keep their old value.
	 *
	 * @param string               $tab   Tab key.
	 * @param array<string, mixed> $input Raw, unslashed form values.
	 * @return list<string> Keys whose values were refused.
	 * @throws InvalidArgumentException For an unknown tab.
	 */
	public function update_tab( string $tab, array $input ): array {
		if ( ! isset( self::FIELDS[ $tab ] ) ) {
			throw new InvalidArgumentException( 'Unknown settings tab.' );
		}

		$changes = array();
		foreach ( self::FIELDS[ $tab ] as $key => $type ) {
			$changes[ $key ] = $this->clean( $type, $input[ $key ] ?? null );
		}

		$this->repository->save( $this->repository->get()->with( $changes ) );

		// Every field so far is a checkbox, which cannot hold an invalid value. Task 10's text, colour and
		// tag cleaners can refuse a value; it collects the refused keys here.
		return array();
	}

	/**
	 * Cleans one value.
	 *
	 * @param string $type Field type from FIELDS.
	 * @param mixed  $raw  Raw value; a checkbox that is not ticked is absent (null).
	 * @throws LogicException For a type without a cleaner.
	 */
	private function clean( string $type, mixed $raw ): bool {
		return match ( $type ) {
			// A ticked checkbox sends "1"; an unticked one sends nothing. true/"true"/"on" cover other callers (Task 26).
			'bool'  => in_array( $raw, array( true, 1, '1', 'on', 'true', 'yes' ), true ),
			default => throw new LogicException( "No cleaner for type $type." ),
		};
	}
}
