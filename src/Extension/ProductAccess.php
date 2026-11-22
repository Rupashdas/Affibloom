<?php
/**
 * What the extension API may do with products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Products\DuplicateLink;
use Affibloom\Application\Products\ProductNotFound;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Products\ValidationFailed;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Interface\Rest\ProductResource;
use Closure;
use InvalidArgumentException;

/**
 * What the extension API may do with products (Tasks 25A, 26A, 26B): check fields, import a product, read its fields,
 * change them, retire or trash it, each through Affibloom's own service and repository, so a helper never writes
 * Affibloom's posts or meta itself. A refusal comes out as an InvalidArgumentException naming the fields.
 */
final class ProductAccess {

	/** What the product page's form does not send back: who it is, where it is shown, which groups hold it. */
	private const NOT_FIELDS = array( 'id', 'public_link', 'groups', 'primary_position', 'slug', 'type', 'asin' );

	/**
	 * Builds the access.
	 *
	 * @param ProductService                     $service  The product page's rules.
	 * @param ProductRepository                  $products The library.
	 * @param Closure(int, string, string): void $dates    Writes a product's created and changed dates.
	 */
	public function __construct( private ProductService $service, private ProductRepository $products, private Closure $dates ) {}

	/**
	 * What Save Changes would refuse.
	 *
	 * @param array<string, mixed> $fields Field => value.
	 * @return array<string, string>
	 */
	public function check( array $fields ): array {
		return $this->service->check( $fields );
	}

	/**
	 * A new published product from the fields, with its dates.
	 *
	 * @param array<string, mixed> $fields   Field => value; `name` and `url` needed.
	 * @param string               $created  "Y-m-d H:i:s" in the site's time, '' for now.
	 * @param string               $modified As $created.
	 * @throws InvalidArgumentException When a field is refused.
	 */
	public function import( array $fields, string $created, string $modified ): int {
		try {
			$id = (int) $this->service->import( $fields )->id;
		} catch ( ValidationFailed $refused ) {
			throw new InvalidArgumentException( esc_html( self::refusals( $refused ) ) );
		}
		if ( '' !== $created && '' !== $modified ) {
			( $this->dates )( $id, $created, $modified );
		}

		return $id;
	}

	/**
	 * A product's fields as the product page holds them, or null when there is no such product.
	 *
	 * @param int $id The product.
	 * @return array<string, mixed>|null
	 */
	public function fields( int $id ): ?array {
		$product = $this->products->get( $id );

		return null === $product ? null : array_diff_key( ( new ProductResource() )->full( $product ), array_flip( self::NOT_FIELDS ) );
	}

	/**
	 * Changes a published product's fields, as Save Changes does.
	 *
	 * @param int                  $id     The product.
	 * @param array<string, mixed> $fields Field => value.
	 * @throws InvalidArgumentException When a field is refused, the link is another product's, or there is no such
	 *                                  published product.
	 */
	public function update( int $id, array $fields ): void {
		try {
			$this->service->update( $id, $fields );
		} catch ( ValidationFailed $refused ) {
			throw new InvalidArgumentException( esc_html( self::refusals( $refused ) ) );
		} catch ( ProductNotFound ) {
			throw new InvalidArgumentException( esc_html( "No published product $id." ) );
		} catch ( DuplicateLink ) {
			throw new InvalidArgumentException( 'url: already_exists' );
		}
	}

	/**
	 * Retires a product, pointing at the product it was merged into (0 for none).
	 *
	 * @param int $id The product.
	 * @param int $to The product it points at.
	 * @throws InvalidArgumentException When there is no such product.
	 */
	public function retire( int $id, int $to ): void {
		$product = $this->products->get( $id );
		if ( null === $product ) {
			throw new InvalidArgumentException( esc_html( "No product $id." ) );
		}
		$this->products->retire( $product, $to );
	}

	/**
	 * Writes a product's created and changed dates, as given (after a status change, which stamps "now").
	 *
	 * @param int    $id       The product.
	 * @param string $created  "Y-m-d H:i:s" in the site's time.
	 * @param string $modified As $created.
	 */
	public function date( int $id, string $created, string $modified ): void {
		( $this->dates )( $id, $created, $modified );
	}

	/**
	 * Moves a product to the trash.
	 *
	 * @param int $id The product.
	 * @throws InvalidArgumentException When it could not be trashed.
	 */
	public function trash( int $id ): void {
		if ( ! $this->products->trash( $id ) ) {
			throw new InvalidArgumentException( esc_html( "Could not trash product $id." ) );
		}
	}

	/**
	 * The refused fields as one line, "url: invalid_url; name: required".
	 *
	 * @param ValidationFailed $refused The refusal.
	 */
	private static function refusals( ValidationFailed $refused ): string {
		$parts = array();
		foreach ( $refused->errors as $field => $reason ) {
			$parts[] = $field . ': ' . $reason;
		}

		return implode( '; ', $parts );
	}
}
