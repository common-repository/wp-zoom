<?php
/**
 * Api class file
 *
 * @package SeattleWebCo\WPZoom
 */

namespace SeattleWebCo\WPZoom;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SeattleWebCo\WPZoom\Exception\InvalidTokenException;

/**
 * Api class.
 */
class Api {

	/**
	 * Base API endpoint.
	 *
	 * @var string
	 */
	public $base_uri = 'https://api.zoom.us/v2';

	/**
	 * Currently authenticated user ID
	 *
	 * @var integer
	 */
	public $user_id;

	/**
	 * OAuth 2 provider
	 *
	 * @var AbstractProvider
	 */
	public $provider;

	/**
	 * Init
	 *
	 * @param AbstractProvider $provider OAuth 2 provider.
	 */
	public function __construct( AbstractProvider $provider ) {
		$this->provider = $provider;

		$this->user_id = get_option( 'wp_zoom_user_id', null );
	}

	/**
	 * Update access token in database
	 *
	 * @param AccessToken|Array $access_token Access token data to save to database.
	 * @return AccessToken
	 */
	public function update_access_token( $access_token ) {
		if ( is_array( $access_token ) ) {
			$access_token = wp_parse_args(
				$access_token,
				array(
					'access_token'      => 'null',
				)
			);

			$access_token = new AccessToken( (array) $access_token );
		}

		if ( (string) $access_token === 'null' ) {
			return $access_token;
		}

		update_option(
			'wp_zoom_oauth_tokens',
			$access_token->jsonSerialize()
		);

		return $access_token;
	}

	/**
	 * Returns a valid access token and refreshes the current one if needed
	 *
	 * @return AccessToken|string
	 */
	private function get_access_token() {
		$tokens = get_option( 'wp_zoom_oauth_tokens', array() );

		if ( empty( $tokens['access_token'] ) || empty( $tokens['refresh_token'] ) || empty( $tokens['expires'] ) ) {
			return null;
		}

		if ( $tokens['expires'] <= time() ) {
			$access_token = $this->provider->getAccessToken( 'refresh_token', array( 'refresh_token' => $tokens['refresh_token'] ) );

			return $this->update_access_token( $access_token );
		}

		return new AccessToken( $tokens );
	}

	/**
	 * Perform a request against the Zoom API
	 *
	 * @param string $uri Request endpoint.
	 * @param string $method Request method.
	 * @param mixed  $body Request body.
	 * @param array  $headers Request headers.
	 * @return mixed
	 */
	public function request( $uri, $method = 'GET', $body = null, $headers = array() ) {
		try {
			$access_token = $this->get_access_token();

			if ( null !== $access_token ) {
				$request = $this->provider->getAuthenticatedRequest(
					$method,
					$uri,
					$access_token,
					array(
						'headers' => $headers,
						'body'    => $body,
					)
				);

				$response = $this->provider->getParsedResponse( $request );

				return $response;
			}
		} catch ( InvalidTokenException $e ) {
			delete_option( 'wp_zoom_oauth_tokens' );
			delete_option( 'wp_zoom_user_id' );

			Cache::delete_all();

			Log::write(
				$e->getMessage(),
				'error',
				array(
					'uri'       => $uri,
					'method'    => $method,
					'body'      => $body,
					'headers'   => $headers,
				)
			);

			Log::write(
				esc_html__( 'Disconnecting Zoom API connection.', 'wp-zoom' ),
				'error'
			);

			do_action( 'wp_zoom_disconnected', $e );
		} catch ( \Exception $e ) {
			Log::write(
				$e->getMessage(),
				'error',
				array(
					'uri'       => $uri,
					'method'    => $method,
					'body'      => $body,
					'headers'   => $headers,
				)
			);
		}

		return array();
	}

	/**
	 * Get current authenticated user details
	 *
	 * @return string
	 */
	public function get_me() {
		return $this->request( $this->base_uri . '/users/me', 'GET', null, array( 'Content-Type' => 'application/json;charset=UTF-8' ) );
	}

	/**
	 * Get a single webinar
	 *
	 * @param string  $webinar_id The webinar ID.
	 * @param boolean $cached Retrieve cached results or not.
	 * @return array
	 */
	public function get_webinar( string $webinar_id, bool $cached = true ) {
		if ( $cached ) {
			$cache = Cache::get( 'wp_zoom_webinar_' . $webinar_id );

			if ( false !== $cache ) {
				return $cache;
			}
		}

		$response = $this->request( $this->base_uri . '/webinars/' . $webinar_id, 'GET', null, array( 'Content-Type' => 'application/json;charset=UTF-8' ) );

		if ( empty( $response ) ) {
			return $response;
		}

		Cache::set( 'wp_zoom_webinar_' . $webinar_id, $response, 'wp_zoom_webinars' );

		return $response;
	}

	/**
	 * Get all webinars
	 *
	 * @param boolean $cached Retrieve cached results or not.
	 * @return array
	 */
	public function get_webinars( bool $cached = true ) {
		if ( $cached ) {
			$cache = Cache::get( 'wp_zoom_webinars' );

			if ( false !== $cache ) {
				return $cache;
			}
		}

		$response = $this->request(
			add_query_arg( array( 'page_size' => 300 ), $this->base_uri . '/users/' . $this->user_id . '/webinars' ),
			'GET',
			null,
			array( 'Content-Type' => 'application/json;charset=UTF-8' )
		);

		if ( empty( $response ) ) {
			return $response;
		}

		Cache::set( 'wp_zoom_webinars', $response, 'wp_zoom_webinars' );

		return $response;
	}

	/**
	 * Get questions for webinar
	 *
	 * @param string  $webinar_id The webinar ID.
	 * @param boolean $cached Retrieve cached results or not.
	 * @return array
	 */
	public function get_webinar_registrant_questions( string $webinar_id, bool $cached = true ) {
		if ( $cached ) {
			$cache = Cache::get( 'wp_zoom_webinar_' . $webinar_id . '_questions' );

			if ( false !== $cache ) {
				return $cache;
			}
		}

		$response = $this->request(
			$this->base_uri . '/webinars/' . $webinar_id . '/registrants/questions',
			'GET',
			null,
			array( 'Content-Type' => 'application/json;charset=UTF-8' )
		);

		if ( empty( $response ) ) {
			return $response;
		}

		Cache::set( 'wp_zoom_webinar_' . $webinar_id . '_questions', $response, 'wp_zoom_webinars' );

		return $response;
	}

	/**
	 * Register customer for a webinar
	 *
	 * @param string $webinar_id The webinar ID.
	 * @param array  $registrant_data The registrant data to POST.
	 * @param string $occurrence_id The webinar occurrence if applicable.
	 * @return array
	 */
	public function add_webinar_registrant( string $webinar_id, array $registrant_data, string $occurrence_id = null ) {
		$response = $this->request(
			add_query_arg( array( 'occurrence_ids' => $occurrence_id ), $this->base_uri . '/webinars/' . $webinar_id . '/registrants' ),
			'POST',
			wp_json_encode( $registrant_data ),
			array( 'Content-Type' => 'application/json;charset=UTF-8' )
		);

		do_action( 'wp_zoom_add_webinar_registrant_success', $response, $registrant_data, $webinar_id, $occurrence_id );

		return $response;
	}

	/**
	 * Get a single meeting
	 *
	 * @param string  $meeting_id The meeting ID.
	 * @param boolean $cached Retrieve cached results or not.
	 * @return array
	 */
	public function get_meeting( string $meeting_id, bool $cached = true ) {
		if ( $cached ) {
			$cache = Cache::get( 'wp_zoom_meeting_' . $meeting_id );

			if ( false !== $cache ) {
				return $cache;
			}
		}

		$response = $this->request( $this->base_uri . '/meetings/' . $meeting_id, 'GET', null, array( 'Content-Type' => 'application/json;charset=UTF-8' ) );

		if ( empty( $response ) ) {
			return $response;
		}

		Cache::set( 'wp_zoom_meeting_' . $meeting_id, $response, 'wp_zoom_meetings' );

		return $response;
	}

	/**
	 * Get all meetings
	 *
	 * @param boolean $cached Retrieve cached results or not.
	 * @return array
	 */
	public function get_meetings( bool $cached = true ) {
		if ( $cached ) {
			$cache = Cache::get( 'wp_zoom_meetings' );

			if ( false !== $cache ) {
				return $cache;
			}
		}

		$response = $this->request(
			add_query_arg( array( 'page_size' => 300 ), $this->base_uri . '/users/' . $this->user_id . '/meetings' ),
			'GET',
			null,
			array( 'Content-Type' => 'application/json;charset=UTF-8' )
		);

		if ( empty( $response ) ) {
			return $response;
		}

		Cache::set( 'wp_zoom_meetings', $response, 'wp_zoom_meetings' );

		return $response;
	}

}
