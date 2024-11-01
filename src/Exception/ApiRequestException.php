<?php
/**
 * API request exception
 *
 * @package SeattleWebCo\WPZoom
 */

namespace SeattleWebCo\WPZoom\Exception;

/**
 * Thrown when an invalid token response is recieved from server
 */
class ApiRequestException extends \Exception {
	/**
	 * Response object
	 *
	 * @var mixed
	 */
	protected $response;

	/**
	 * Returns the exception's response body.
	 *
	 * @return array|string
	 */
	public function getResponseBody() {
		return $this->response;
	}
}
