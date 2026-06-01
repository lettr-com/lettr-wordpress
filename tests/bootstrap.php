<?php
/**
 * PHPUnit bootstrap for the Lettr WordPress plugin.
 *
 * These are plain unit tests — they do NOT load WordPress. Instead we define
 * the small set of WordPress functions the plugin actually calls as test
 * doubles, and route every outbound HTTP call (`wp_remote_request`) through a
 * controllable stub so tests can assert the exact request that was built and
 * dictate the response that comes back.
 *
 * This whole directory is excluded from the bundled plugin (see .distignore
 * and the `build` script in composer.json), so none of this ships.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'LETTR_VERSION' ) ) {
	define( 'LETTR_VERSION', '1.2.0' );
}

// ---------------------------------------------------------------------------
// HTTP stub state + helpers
// ---------------------------------------------------------------------------

$GLOBALS['lettr_test_requests']  = array();
$GLOBALS['lettr_test_responses'] = array();
$GLOBALS['lettr_options']        = array();
$GLOBALS['lettr_network_home']   = 'https://example.com';

/**
 * Clear recorded requests, queued responses, and options between tests.
 */
function lettr_test_reset() {
	$GLOBALS['lettr_test_requests']  = array();
	$GLOBALS['lettr_test_responses'] = array();
	$GLOBALS['lettr_options']        = array();
	$GLOBALS['lettr_network_home']   = 'https://example.com';
}

/**
 * Queue the next response `wp_remote_request` will return. Pass a WP_Error to
 * simulate a transport failure, or build one with lettr_test_make_response().
 *
 * @param array|WP_Error $response
 */
function lettr_test_enqueue_response( $response ) {
	$GLOBALS['lettr_test_responses'][] = $response;
}

/**
 * Build a WordPress-shaped HTTP response array.
 *
 * @param int               $code    HTTP status code.
 * @param array|string|null $body    Array (JSON-encoded), raw string, or null (empty body).
 * @param array             $headers Response headers.
 * @return array
 */
function lettr_test_make_response( $code, $body = null, array $headers = array() ) {
	if ( is_array( $body ) ) {
		$body = wp_json_encode( $body );
	} elseif ( null === $body ) {
		$body = '';
	}

	return array(
		'response' => array( 'code' => $code ),
		'body'     => $body,
		'headers'  => $headers,
	);
}

/**
 * The most recent request that reached `wp_remote_request`.
 *
 * @return array{url:string,args:array}|null
 */
function lettr_test_last_request() {
	$requests = $GLOBALS['lettr_test_requests'];
	return empty( $requests ) ? null : end( $requests );
}

// ---------------------------------------------------------------------------
// WordPress test doubles
// ---------------------------------------------------------------------------

class WP_Error {

	/** @var array<string,string[]> */
	private $errors = array();

	/** @var array<string,mixed> */
	private $error_data = array();

	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( '' === $code && 0 === $code ) {
			return;
		}
		if ( '' !== $code ) {
			$this->errors[ $code ][] = $message;
			if ( '' !== $data && array() !== $data ) {
				$this->error_data[ $code ] = $data;
			}
		}
	}

	public function get_error_code() {
		if ( empty( $this->errors ) ) {
			return '';
		}
		return array_key_first( $this->errors );
	}

	public function get_error_message() {
		$code = $this->get_error_code();
		return '' === $code ? '' : $this->errors[ $code ][0];
	}

	public function get_error_data() {
		$code = $this->get_error_code();
		return $this->error_data[ $code ] ?? null;
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

/**
 * Records the request and returns the next queued response (default: empty 200).
 */
function wp_remote_request( $url, $args = array() ) {
	$GLOBALS['lettr_test_requests'][] = array(
		'url'  => $url,
		'args' => $args,
	);

	if ( ! empty( $GLOBALS['lettr_test_responses'] ) ) {
		return array_shift( $GLOBALS['lettr_test_responses'] );
	}

	return lettr_test_make_response( 200, array() );
}

function wp_remote_retrieve_response_code( $response ) {
	return is_array( $response ) ? ( $response['response']['code'] ?? 0 ) : 0;
}

function wp_remote_retrieve_body( $response ) {
	return is_array( $response ) ? ( $response['body'] ?? '' ) : '';
}

function wp_remote_retrieve_header( $response, $header ) {
	if ( ! is_array( $response ) || empty( $response['headers'] ) ) {
		return '';
	}
	foreach ( $response['headers'] as $key => $value ) {
		if ( strtolower( (string) $key ) === strtolower( (string) $header ) ) {
			return $value;
		}
	}
	return '';
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

/**
 * Faithful-enough port of add_query_arg for the two-arg (array, url) form the
 * client uses. Merges $args into the URL's existing query string.
 */
function add_query_arg( $args, $url ) {
	$fragment = '';
	if ( false !== strpos( $url, '#' ) ) {
		list( $url, $frag ) = explode( '#', $url, 2 );
		$fragment           = '#' . $frag;
	}

	$base     = $url;
	$existing = array();
	if ( false !== strpos( $url, '?' ) ) {
		list( $base, $query_string ) = explode( '?', $url, 2 );
		parse_str( $query_string, $existing );
	}

	$merged = array_merge( $existing, $args );
	$query  = http_build_query( $merged );

	return $base . ( '' !== $query ? '?' . $query : '' ) . $fragment;
}

function get_option( $name, $default_value = false ) {
	return array_key_exists( $name, $GLOBALS['lettr_options'] ) ? $GLOBALS['lettr_options'][ $name ] : $default_value;
}

function update_option( $name, $value ) {
	$GLOBALS['lettr_options'][ $name ] = $value;
	return true;
}

function delete_option( $name ) {
	unset( $GLOBALS['lettr_options'][ $name ] );
	return true;
}

function apply_filters( $tag, $value, ...$args ) {
	return $value;
}

function network_home_url( $path = '' ) {
	return $GLOBALS['lettr_network_home'];
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

// ---------------------------------------------------------------------------
// Plugin classes under test
// ---------------------------------------------------------------------------

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../class-lettr.php';
require __DIR__ . '/../class-lettr-api.php';
