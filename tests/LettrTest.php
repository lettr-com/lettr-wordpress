<?php

declare( strict_types=1 );

namespace Lettr\Tests;

use Lettr;

/**
 * Tests for the Lettr core helper class (key validation, sender defaults, and
 * the live key-verification flow that talks to /auth/check).
 */
final class LettrTest extends LettrTestCase {

	public function test_is_valid_key_requires_the_lttr_prefix(): void {
		$this->assertTrue( Lettr::is_valid_key( 'lttr_abc123' ) );
		$this->assertFalse( Lettr::is_valid_key( 'nope_abc' ) );
		$this->assertFalse( Lettr::is_valid_key( '' ) );
		$this->assertFalse( Lettr::is_valid_key( ' lttr_abc' ) );
	}

	public function test_get_api_key_reads_the_option(): void {
		update_option( 'lettr_api_key', 'lttr_stored' );
		$this->assertSame( 'lttr_stored', Lettr::get_api_key() );
	}

	public function test_get_from_name_defaults_to_wordpress(): void {
		$this->assertSame( 'WordPress', Lettr::get_from_name() );

		update_option( 'lettr_from_name', 'Acme' );
		$this->assertSame( 'Acme', Lettr::get_from_name() );
	}

	public function test_get_from_address_derives_default_from_site_host(): void {
		$GLOBALS['lettr_network_home'] = 'https://www.example.org';
		$this->assertSame( 'wordpress@example.org', Lettr::get_from_address() );
	}

	public function test_get_from_address_prefers_saved_option(): void {
		$GLOBALS['lettr_network_home'] = 'https://www.example.org';
		update_option( 'lettr_from_address', 'hello@acme.test' );
		$this->assertSame( 'hello@acme.test', Lettr::get_from_address() );
	}

	public function test_verify_key_returns_true_on_accepted_key(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 200, array( 'message' => 'ok' ) ) );

		$this->assertTrue( Lettr::verify_key( 'lttr_good' ) );

		// Sanity-check it hit the auth endpoint with the candidate key.
		$request = lettr_test_last_request();
		$this->assertStringEndsWith( '/auth/check', $request['url'] );
		$this->assertSame( 'Bearer lttr_good', $request['args']['headers']['Authorization'] );
	}

	public function test_verify_key_returns_false_on_401(): void {
		lettr_test_enqueue_response(
			lettr_test_make_response( 401, array( 'message' => 'Invalid API key.' ) )
		);

		$this->assertFalse( Lettr::verify_key( 'lttr_bad' ) );
	}

	public function test_verify_key_returns_wp_error_on_transport_or_server_failure(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 500, array( 'message' => 'boom' ) ) );

		$result = Lettr::verify_key( 'lttr_x' );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
