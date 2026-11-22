<?php
/**
 * The kinds of log Affibloom keeps (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Logs;

/**
 * The kinds of log Affibloom keeps (F37 R3, D3; Task TL3): Lasso's types, each a file per day named by its value.
 * Lasso's Import Log is not kept: Affibloom imports nothing (deviation 146).
 */
enum LogType: string {
	case LinkDatabase   = 'link_database';
	case Amazon         = 'amazon';
	case AmazonApiError = 'amazon_api_error';
	case UrlIssueCheck  = 'url_issue_check';
	case DuplicatedSlug = 'duplicated_slug';
	case SqlErrors      = 'sql_errors';
}
