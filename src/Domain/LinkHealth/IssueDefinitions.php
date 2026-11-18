<?php
/**
 * The issue codes a product can carry, with a label and a one-line description.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

/**
 * Lasso's 41 issue codes (F11 R2: lasso_url_issue_definitions) with their labels and Affibloom's own one-line
 * descriptions (D3: Lasso's texts are Mozilla's and shown nowhere). A domain map, not a table.
 */
final class IssueDefinitions {

	/**
	 * Code => label, description. PHP stores the codes that read as numbers ('400' and up) as integer keys; '000'
	 * stays a string. Lookups by a string code find both.
	 *
	 * @var array<int|string, array{string, string}>
	 */
	private const DEFINITIONS = array(
		'000' => array( 'Amazon Out of Stock', 'Amazon lists the product with no stock.' ),
		'400' => array( 'Bad Request', 'The merchant could not understand the request for this link.' ),
		'401' => array( 'Unauthorized', 'The page asks for a login before it shows anything.' ),
		'402' => array( 'Payment Required', 'The page is behind a payment.' ),
		'403' => array( 'Forbidden', 'The merchant refuses to show the page.' ),
		'404' => array( 'Not Found', 'The page does not exist, or the link is blank.' ),
		'405' => array( 'Method Not Allowed', 'The merchant does not accept a plain page request at this address.' ),
		'406' => array( 'Not Acceptable', 'The merchant has no version of the page it is willing to send.' ),
		'407' => array( 'Proxy Authentication Required', 'A proxy between the site and the merchant asks for a login.' ),
		'408' => array( 'Request Timeout', 'The merchant gave up waiting for the request.' ),
		'409' => array( 'Conflict', 'The merchant reports a conflict with the page\'s current state.' ),
		'410' => array( 'Gone', 'The page was removed for good.' ),
		'411' => array( 'Length Required', 'The merchant wants a request length it was not given.' ),
		'412' => array( 'Precondition Failed', 'A condition of the request was not met by the merchant.' ),
		'413' => array( 'Payload Too Large', 'The merchant found the request too large.' ),
		'414' => array( 'URI Too Long', 'The link is longer than the merchant accepts.' ),
		'415' => array( 'Unsupported Media Type', 'The merchant does not accept the request\'s format.' ),
		'416' => array( 'Range Not Satisfiable', 'The merchant cannot send the part of the page asked for.' ),
		'417' => array( 'Expectation Failed', 'The merchant cannot meet what the request expected.' ),
		'418' => array( 'I\'m a teapot', 'The merchant answers with a joke code instead of the page.' ),
		'421' => array( 'Misdirected Request', 'The request reached a server that cannot answer for this address.' ),
		'422' => array( 'Unprocessable Entity (WebDAV)', 'The merchant understood the request but could not process it.' ),
		'423' => array( 'Locked (WebDAV)', 'The page is locked.' ),
		'424' => array( 'Failed Dependency (WebDAV)', 'The page depends on another request that failed.' ),
		'425' => array( 'Too Early', 'The merchant will not answer a request it may see twice.' ),
		'426' => array( 'Upgrade Required', 'The merchant wants a newer protocol.' ),
		'428' => array( 'Precondition Required', 'The merchant wants a conditional request.' ),
		'429' => array( 'Too Many Requests', 'The merchant is limiting how often it is asked.' ),
		'431' => array( 'Request Header Fields Too Large', 'The merchant found the request\'s headers too large.' ),
		'451' => array( 'Unavailable For Legal Reasons', 'The page is withheld for legal reasons.' ),
		'500' => array( 'Internal Server Error', 'The merchant\'s server failed, or the link gave no answer at all.' ),
		'501' => array( 'Not Implemented', 'The merchant\'s server cannot handle this request.' ),
		'502' => array( 'Bad Gateway', 'A server in front of the merchant got a bad answer.' ),
		'503' => array( 'Service Unavailable', 'The merchant is down or too busy for now.' ),
		'504' => array( 'Gateway Timeout', 'A server in front of the merchant did not get an answer in time.' ),
		'505' => array( 'HTTP Version Not Supported', 'The merchant does not support the request\'s HTTP version.' ),
		'506' => array( 'Variant Also Negotiates', 'The merchant\'s server is set up wrongly.' ),
		'507' => array( 'Insufficient Storage (WebDAV)', 'The merchant\'s server is out of storage.' ),
		'508' => array( 'Loop Detected (WebDAV)', 'The merchant\'s server found a loop.' ),
		'510' => array( 'Not Extended', 'The merchant wants more from the request.' ),
		'511' => array( 'Network Authentication Required', 'The network asks for a login before it lets the request through.' ),
	);

	/**
	 * Every code, in order.
	 *
	 * @return list<string>
	 */
	public static function codes(): array {
		return array_map( 'strval', array_keys( self::DEFINITIONS ) );
	}

	/**
	 * A code's label; "HTTP <code>" for one outside the list.
	 *
	 * @param string $code The code.
	 */
	public static function label( string $code ): string {
		return self::DEFINITIONS[ $code ][0] ?? 'HTTP ' . $code;
	}

	/**
	 * A code's one-line description; '' for one outside the list.
	 *
	 * @param string $code The code.
	 */
	public static function description( string $code ): string {
		return self::DEFINITIONS[ $code ][1] ?? '';
	}
}
