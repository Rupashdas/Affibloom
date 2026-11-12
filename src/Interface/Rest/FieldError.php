<?php
/**
 * A refused field as the REST error the API contract describes.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\InvalidField;
use Affibloom\Interface\Messages;
use WP_Error;

/**
 * A refused field as the REST error the API contract describes (00-overview, "REST responses"): 422, code
 * affibloom_invalid_field, the words in data.fields. Taken out of ProductsController on its second use.
 */
final class FieldError {

	/**
	 * The error for a refused field.
	 *
	 * @param InvalidField $error What the domain refused.
	 */
	public static function from( InvalidField $error ): WP_Error {
		$message = Messages::for_reason( $error->reason );

		return new WP_Error(
			'affibloom_invalid_field',
			$message,
			array(
				'status' => 422,
				'fields' => array( $error->field => $message ),
			)
		);
	}
}
