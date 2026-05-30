<?php

declare( strict_types=1 );

namespace Lettr\Tests;

use PHPUnit\Framework\TestCase;
use Lettr_Api;

/**
 * Shared base: resets the HTTP/option stubs before every test and exposes a few
 * small helpers for asserting on the request the client built.
 */
abstract class LettrTestCase extends TestCase {

	const API_KEY = 'lttr_test_key_123';

	protected function setUp(): void {
		parent::setUp();
		lettr_test_reset();
	}

	/**
	 * A client wired to a known API key (skips the get_option() lookup).
	 */
	protected function client( ?string $api_key = self::API_KEY ): Lettr_Api {
		return new Lettr_Api( $api_key );
	}

	/**
	 * The HTTP method of the last request.
	 */
	protected function lastMethod(): string {
		$request = lettr_test_last_request();
		return $request['args']['method'] ?? '';
	}

	/**
	 * The full URL of the last request.
	 */
	protected function lastUrl(): string {
		$request = lettr_test_last_request();
		return $request['url'] ?? '';
	}

	/**
	 * The path + query portion of the last request (BASE_URL stripped).
	 */
	protected function lastPath(): string {
		return str_replace( Lettr_Api::BASE_URL, '', $this->lastUrl() );
	}

	/**
	 * The decoded query string of the last request as an assoc array.
	 *
	 * @return array<string,mixed>
	 */
	protected function lastQuery(): array {
		$url = $this->lastUrl();
		$qs  = parse_url( $url, PHP_URL_QUERY );
		if ( ! $qs ) {
			return array();
		}
		parse_str( $qs, $out );
		return $out;
	}

	/**
	 * The decoded JSON body of the last request (null if no body was sent).
	 *
	 * @return mixed
	 */
	protected function lastBody() {
		$request = lettr_test_last_request();
		if ( ! isset( $request['args']['body'] ) ) {
			return null;
		}
		return json_decode( $request['args']['body'], true );
	}

	/**
	 * The request headers of the last request.
	 *
	 * @return array<string,string>
	 */
	protected function lastHeaders(): array {
		$request = lettr_test_last_request();
		return $request['args']['headers'] ?? array();
	}
}
