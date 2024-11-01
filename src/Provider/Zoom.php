<?php
/**
 * OAuth2 Provider class file
 *
 * @package SeattleWebCo\WPZoom
 */

namespace SeattleWebCo\WPZoom\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;

use League\OAuth2\Client\Provider\GenericResourceOwner;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use League\OAuth2\Client\Token\AccessToken;

use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use SeattleWebCo\WPZoom\Exception\ApiRequestException;
use SeattleWebCo\WPZoom\Exception\InvalidTokenException;

/**
 * Api class.
 */
class Zoom extends AbstractProvider {

	use BearerAuthorizationTrait;

	/**
	 * Response error
	 *
	 * @var string
	 */
	private $response_error = 'error';

	/**
	 * Store response code
	 *
	 * @var string
	 */
	private $response_code;

	/**
	 * Field to get
	 *
	 * @var string
	 */
	private $response_resource_owner_id = 'id';

	/**
	 * Endpoint to begin authorization
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl() {
		return 'https://zoom.us/oauth/authorize';
	}

	/**
	 * Endpoint to get access token
	 *
	 * @param array $params Additional parameters.
	 * @return string
	 */
	public function getBaseAccessTokenUrl( array $params ) {
		return 'https://zoom.us/oauth/token';
	}

	/**
	 * Endpoint for resource owner details
	 *
	 * @param AccessToken $token The access token.
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl( AccessToken $token ) {
		return 'https://zoom.us/v2/me';
	}

	/**
	 * Scopes required
	 *
	 * @return array
	 */
	protected function getDefaultScopes() {
		return array(
			'user:read',
			'webinar:read',
			'webinar:write',
		);
	}

	/**
	 * Checks API request response
	 *
	 * @param ResponseInterface $response PSR-7 Response.
	 * @param array             $data Data from response.
	 * @return void
	 * @throws IdentityProviderException Identity provider exception.
	 * @throws ApiRequestException Error response from API.
	 * @throws InvalidTokenException Invalid access token.
	 */
	protected function checkResponse( ResponseInterface $response, $data ) {
		// phpcs:ignore
		if ( substr( $response->getStatusCode(), 0, 1 ) != 2 ) {
			if ( isset( $data['code'] ) && $data['code'] === 124 ) {
				throw new InvalidTokenException(
					sprintf(
						/* translators: 1: Response message */
						__( 'Unable to retrieve access token from server: %s', 'wp-zoom' ),
						$data['message']
					)
				);
			}

			throw new ApiRequestException(
				sprintf(
					/* translators: 1: Response message */
					__( 'Error recieved from Zoom API: %1$s', 'wp-zoom' ),
					wp_json_encode( $data )
				)
			);
		}

		if ( ! empty( $data[ $this->response_error ] ) ) {
			$error = $data[ $this->response_error ];

			if ( ! is_string( $error ) ) {
				// phpcs:ignore
				$error = var_export( $error, true );
			}

			$code = $this->response_code && ! empty( $data[ $this->response_code ] ) ? $data[ $this->response_code ] : 0;

			if ( ! is_int( $code ) ) {
				$code = intval( $code );
			}

			throw new IdentityProviderException( $error, $code, $data );
		}
	}

	/**
	 * Create resource owner
	 *
	 * @param array       $response From response.
	 * @param AccessToken $token Access token.
	 * @return GenericResourceOwner
	 */
	protected function createResourceOwner( array $response, AccessToken $token ) {
		return new GenericResourceOwner( $response, $this->response_resource_owner_id );
	}

	/**
	 * Requests an access token using a specified grant and option set.
	 *
	 * @param  mixed $grant Grant type.
	 * @param  array $options Options.
	 * @throws InvalidTokenException Invalid access token.
	 * @return AccessTokenInterface
	 */
	public function getAccessToken( $grant, array $options = array() ) {
		$grant = $this->verifyGrant( $grant );

		$params = array(
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret,
			'redirect_uri'  => $this->redirectUri,
		);

		$params   = $grant->prepareRequestParameters( $params, $options );
		$request  = $this->getAccessTokenRequest( $params );
		$response = $this->getParsedResponse( $request );

		if ( false === is_array( $response ) || isset( $response['error'] ) ) {
			/* translators: 1: Response reason */
			throw new InvalidTokenException( sprintf( __( 'Unable to retrieve access token from server: %s', 'wp-zoom' ), $response['reason'] ?? 'NULL' ) );
		}

		$prepared = $this->prepareAccessTokenResponse( $response );
		$token    = $this->createAccessToken( $prepared, $grant );

		return $token;
	}
}
