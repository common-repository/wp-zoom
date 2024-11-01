<?php
/**
 * OAuth2 Provider class file
 *
 * @package SeattleWebCo\WPZoom
 */

namespace SeattleWebCo\WPZoom\Provider;

/**
 * Api class.
 */
class ZoomForWp extends Zoom {
	/**
	 * Endpoint to begin authorization
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl() {
		return 'https://api.seattlewebco.com/oauth?provider=zoom';
	}

	/**
	 * Endpoint to get access token
	 *
	 * @param array $params Additional parameters.
	 * @return string
	 */
	public function getBaseAccessTokenUrl( array $params ) {
		return 'https://api.seattlewebco.com/oauth/token?provider=zoom';
	}
}
