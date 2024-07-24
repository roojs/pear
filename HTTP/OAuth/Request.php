<?php

/**
 * Helper class for creating URLs with OAuth 1.0a parameters
 * 
 * from 'https://docs.gravityforms.com/rest-api-v2-authentication/'
 *
 * @since 2.4
 */
class HTTP_OAuth_Request {

	/**
	 * OAuth signature method algorithm.
	 */
	const HASH_ALGORITHM = 'SHA256';

	/**
	 * API endpoint URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Consumer key.
	 *
	 * @var string
	 */
	protected $consumer_key;

	/**
	 * Consumer secret.
	 *
	 * @var string
	 */
	protected $consumer_secret;

	/**
	 * Request method.
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * Request parameters.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Timestamp.
	 *
	 * @var string
	 */
	protected $timestamp;

	/**
	 * Initialize oAuth class.
	 *
	 * @param string $url             Store URL.
	 * @param string $consumer_key    Consumer key.
	 * @param string $consumer_secret Consumer Secret.
	 * @param string $method          Request method.
	 * @param array  $parameters      Request parameters.
	 * @param string $timestamp       Timestamp.
	 */
	public function __construct(
		$url,
		$consumer_key,
		$consumer_secret,
		$method,
		$parameters = [],
		$timestamp = ''
	) {
		$this->url             = $url;
		$this->consumer_key    = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		$this->method          = $method;
		$this->parameters      = $parameters;
		$this->timestamp       = empty( $timestamp ) ? time() : $timestamp;
	}

	/**
	 * Encode according to RFC 3986.
	 *
	 * @param string|array $value Value to be normalized.
	 *
	 * @return string
	 */
	protected function encode( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'encode' ], $value );
		} else {
			return str_replace( [ '+', '%7E' ], [ ' ', '~' ], rawurlencode( $value ) );
		}
	}

	/**
	 * Normalize parameters.
	 *
	 * @param array $parameters Parameters to normalize.
	 *
	 * @return array
	 */
	protected function normalize_parameters( $parameters ) {
		$normalized = [];

		foreach ( $parameters as $key => $value ) {
			// Percent symbols (%) must be double-encoded.
			$key   = $this->encode( $key );
			$value = $this->encode( $value );

			$normalized[ $key ] = $value;
		}

		return $normalized;
	}

	/**
	 * Process filters.
	 *
	 * @param array $parameters Request parameters.
	 *
	 * @return array
	 */
	protected function process_filters( $parameters ) {
		if ( isset( $parameters['filter'] ) ) {
			$filters = $parameters['filter'];
			unset( $parameters['filter'] );
			foreach ( $filters as $filter => $value ) {
				$parameters[ 'filter[' . $filter . ']' ] = $value;
			}
		}

		return $parameters;
	}

	/**
	 * Get secret.
	 *
	 * @return string
	 */
	protected function get_secret() {
		$secret = $this->consumer_secret;

		return $secret . '&';
	}

	/**
	 * Generate oAuth1.0 signature.
	 *
	 * @param array $parameters Request parameters including oauth.
	 *
	 * @return string
	 */
	protected function generate_oauth_signature( $parameters ) {
		$baseRequestUri = rawurlencode( $this->url );

		// Extract filters.
		$parameters = $this->process_filters( $parameters );

		// Normalize parameter key/values and sort them.
		$parameters = $this->normalize_parameters( $parameters );
		uksort( $parameters, 'strcmp' );

		// Set query string.
		$queryString  = implode( '%26', $this->join_with_equals_sign( $parameters ) ); // Join with ampersand.
		$stringToSign = $this->method . '&' . $baseRequestUri . '&' . $queryString;
		$secret       = $this->get_secret();

		return base64_encode( hash_hmac( self::HASH_ALGORITHM, $stringToSign, $secret, true ) );
	}

	/**
	 * Creates an array of urlencoded strings out of each array key/value pairs.
	 *
	 * @param  array  $params       Array of parameters to convert.
	 * @param  array  $query_params Array to extend.
	 * @param  string $key          Optional Array key to append
	 *
	 * @return string              Array of urlencoded strings
	 */
	protected function join_with_equals_sign( $params, $query_params = [], $key = '' ) {
		foreach ( $params as $param_key => $param_value ) {
			if ( $key ) {
				$param_key = $key . '%5B' . $param_key . '%5D'; // Handle multi-dimensional array.
			}

			if ( is_array( $param_value ) ) {
				$query_params = $this->join_with_equals_sign( $param_value, $query_params, $param_key );
			} else {
				$string         = $param_key . '=' . $param_value; // Join with equals sign.
				$query_params[] = $this->encode( $string );
			}
		}

		return $query_params;
	}

	/**
	 * Sort parameters.
	 *
	 * @param array $parameters Parameters to sort in byte-order.
	 *
	 * @return array
	 */
	protected function get_sorted_parameters( $parameters ) {
		uksort( $parameters, 'strcmp' );

		foreach ( $parameters as $key => $value ) {
			if ( is_array( $value ) ) {
				uksort( $parameters[ $key ], 'strcmp' );
			}
		}

		return $parameters;
	}

	/**
	 * Get oAuth1.0 parameters.
	 *
	 * @return string
	 */
	public function get_parameters() {
		$parameters = array_merge( $this->parameters, [
			'oauth_consumer_key'     => $this->consumer_key,
			'oauth_timestamp'        => $this->timestamp,
			'oauth_nonce'            => sha1( microtime() ),
			'oauth_signature_method' => 'HMAC-' . self::HASH_ALGORITHM,
		] );

		// The parameters above must be included in the signature generation.
		$parameters['oauth_signature'] = $this->generate_oauth_signature( $parameters );

		return $this->get_sorted_parameters( $parameters );
	}

	/**
	 * Gets the request URL with the oAuth parameters added to the query string
	 *
	 * @since 2.4
	 *
	 * @return string Returs the request URL with the oAuth parameters
	 */
	public function get_url() {
		return $this->url . '?' . http_build_query( $this->get_parameters() );
	}
}