<?php
/**
 * A REST answer that is a CSV file.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * A CSV download through the REST API (F11 D18, F22 D19): the rows written by fputcsv() into a memory buffer, sent as
 * the file itself rather than JSON, with its name in Content-Disposition and the number of rows below the header in
 * `X-Affibloom-Exported` (for "Exported N …"). Taken out of the Dashboard's export on its second use (Task O9).
 */
final class CsvResponse {

	/** The header that carries the number of rows. */
	public const COUNT_HEADER = 'X-Affibloom-Exported';

	/**
	 * The answer for these rows.
	 *
	 * @param iterable<list<string>> $rows     The header row first, then the data.
	 * @param string                 $filename The file's name.
	 */
	public static function of( iterable $rows, string $filename ): WP_REST_Response|WP_Error {
		$stream = fopen( 'php://temp', 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- an in-memory buffer, not a file.
		if ( false === $stream ) {
			return new WP_Error( 'affibloom_export_stream', __( 'Could not open export stream.', 'affibloom' ), array( 'status' => 500 ) );
		}
		$count = -1;
		foreach ( $rows as $row ) {
			fputcsv( $stream, $row, ',', '"', '' );
			++$count;
		}
		rewind( $stream );
		$csv = (string) stream_get_contents( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- the buffer above.

		$response = new WP_REST_Response( $csv );
		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="' . $filename . '"' );
		$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
		$response->header( self::COUNT_HEADER, (string) max( 0, $count ) );
		$response->header( 'Access-Control-Expose-Headers', self::COUNT_HEADER );

		return $response;
	}

	/**
	 * Sends a CSV answer as it is (rest_pre_serve_request); every other answer is left to WordPress.
	 *
	 * @param bool             $served  Whether the answer was sent already.
	 * @param WP_HTTP_Response $result  The answer.
	 * @param WP_REST_Request  $request The request.
	 * @param WP_REST_Server   $server  The server.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public static function serve( $served, $result, $request, $server ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress passes four.
		unset( $request, $server );
		$type = (string) ( $result->get_headers()['Content-Type'] ?? '' );
		if ( $served || 200 !== $result->get_status() || ! str_starts_with( $type, 'text/csv' ) ) {
			return (bool) $served;
		}
		echo $result->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a CSV file, its cells written by fputcsv() and guarded against formulas.

		return true;
	}
}
