<?php
/**
 * Affibloom's logs as files in uploads (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Logs;

use Affibloom\Application\Logs\LogFiles;
use Affibloom\Application\Logs\Logger;
use Affibloom\Domain\Logs\LogType;
use Closure;

// phpcs:disable WordPress.WP.AlternativeFunctions -- appending to and pruning log files: WP_Filesystem has no append, and this runs in cron and REST requests without its credentials.

/**
 * Affibloom's logs as files (F37 R3-R6, D3; Task TL3): `<Y_m_d>_<type>.log.php` in `uploads/affibloom-logs`, one line
 * `[d/M/Y:H:i:s] <message>` per entry, times in UTC, as Lasso's `logs/<Y_m_d>_<type>.log` but outside the plugin folder
 * (Lasso's are public and lost on every update). Kept from the web three ways: each file opens with a PHP exit, so a
 * request for it prints nothing on any server (nginx ignores .htaccess); the folder has a deny-all `.htaccess` and an
 * empty `index.php`. Nothing to download or clear, as Lasso.
 */
final class FileLog implements Logger, LogFiles {

	/** The first line of every file: a request for it ends there. */
	private const GUARD = "<?php exit; ?>\n";

	/** The file name's ending. */
	private const SUFFIX = '.log.php';

	/**
	 * Builds the log.
	 *
	 * @param Closure|string $dir The folder, or what gives it on first use: fn(): string (no request asks for uploads
	 *                             before something is logged).
	 * @param Closure        $enabled Whether "Enable Logs" is on: fn(): bool.
	 * @param Closure        $now     The time: fn(): int.
	 */
	public function __construct( private Closure|string $dir, private Closure $enabled, private Closure $now ) {}

	/**
	 * The folder.
	 */
	private function folder(): string {
		if ( $this->dir instanceof Closure ) {
			$this->dir = (string) ( $this->dir )();
		}

		return $this->dir;
	}

	/**
	 * Writes one line, or nothing when logging is off and the line is not forced.
	 *
	 * @param LogType $type    Which log.
	 * @param string  $message The line.
	 * @param bool    $force   Written even with logging off.
	 */
	public function write( LogType $type, string $message, bool $force = false ): void {
		if ( ! $force && ! ( $this->enabled )() ) {
			return;
		}
		if ( ! $this->ready() ) {
			return;
		}
		$now  = (int) ( $this->now )();
		$file = $this->folder() . '/' . gmdate( 'Y_m_d', $now ) . '_' . $type->value . self::SUFFIX;
		$line = '[' . gmdate( 'd/M/Y:H:i:s', $now ) . '] ' . str_replace( array( "\r", "\n" ), ' ', $message ) . "\n";
		file_put_contents( $file, ( is_file( $file ) ? '' : self::GUARD ) . $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * The newest log of a type, as text (the guard line left out), or ''.
	 *
	 * @param LogType $type Which log.
	 */
	public function newest( LogType $type ): string {
		$files = glob( $this->folder() . '/*_' . $type->value . self::SUFFIX );
		$files = array_values( array_filter( false === $files ? array() : $files, static fn( string $file ): bool => 1 === preg_match( '/\/\d{4}_\d{2}_\d{2}_' . preg_quote( $type->value, '/' ) . '\.log\.php$/', $file ) ) );
		if ( array() === $files ) {
			return '';
		}
		rsort( $files );
		$text = (string) file_get_contents( $files[0] );

		return str_starts_with( $text, self::GUARD ) ? substr( $text, strlen( self::GUARD ) ) : $text;
	}

	/**
	 * Deletes the logs of days older than KEEP_DAYS.
	 *
	 * @param int $now The time, as a Unix timestamp.
	 * @return int How many files were deleted.
	 */
	public function prune( int $now ): int {
		$files = glob( $this->folder() . '/*' . self::SUFFIX );
		$limit = gmdate( 'Y_m_d', $now - self::KEEP_DAYS * 86400 );
		$gone  = 0;
		foreach ( false === $files ? array() : $files as $file ) {
			if ( 1 === preg_match( '/\/(\d{4}_\d{2}_\d{2})_[a-z_]+\.log\.php$/', $file, $day ) && $day[1] < $limit && unlink( $file ) ) {
				++$gone;
			}
		}

		return $gone;
	}

	/**
	 * Makes the folder and its guards when they are missing.
	 */
	private function ready(): bool {
		if ( ! is_dir( $this->folder() ) && ! mkdir( $this->folder(), 0755, true ) && ! is_dir( $this->folder() ) ) {
			return false;
		}
		if ( ! is_file( $this->folder() . '/index.php' ) ) {
			file_put_contents( $this->folder() . '/index.php', "<?php\n// Silence is golden.\n" );
		}
		if ( ! is_file( $this->folder() . '/.htaccess' ) ) {
			file_put_contents( $this->folder() . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n" );
		}

		return true;
	}
}
