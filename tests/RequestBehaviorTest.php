<?php

declare( strict_types=1 );

namespace Lettr\Tests;

/**
 * Tests for the shared request()/response handling, exercised through public
 * methods (health/auth_check/send_email are convenient thin entry points).
 */
final class RequestBehaviorTest extends LettrTestCase {

	public function test_sets_bearer_authorization_header(): void {
		$this->client()->health();

		$headers = $this->lastHeaders();
		$this->assertSame( 'Bearer ' . self::API_KEY, $headers['Authorization'] );
		$this->assertSame( 'application/json', $headers['Accept'] );
	}

	public function test_sends_versioned_user_agent(): void {
		$this->client()->health();

		$args = lettr_test_last_request()['args'];
		$this->assertSame( 'lettr-wordpress/' . LETTR_VERSION, $args['user-agent'] ?? null );
		$this->assertArrayHasKey( 'timeout', $args );
	}

	public function test_get_request_carries_no_body_and_no_content_type(): void {
		$this->client()->health();

		$this->assertArrayNotHasKey( 'body', lettr_test_last_request()['args'] );
		$this->assertArrayNotHasKey( 'Content-Type', $this->lastHeaders() );
	}

	public function test_2xx_with_json_returns_decoded_array(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 200, array( 'ok' => true, 'n' => 3 ) ) );

		$result = $this->client()->health();

		$this->assertSame( array( 'ok' => true, 'n' => 3 ), $result );
	}

	public function test_2xx_with_empty_body_returns_empty_array(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 200, null ) );

		$result = $this->client()->health();

		$this->assertSame( array(), $result );
	}

	public function test_204_returns_true(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 204, null ) );

		$result = $this->client()->delete_domain( 'example.com' );

		$this->assertTrue( $result );
	}

	public function test_error_with_envelope_maps_to_wp_error(): void {
		lettr_test_enqueue_response(
			lettr_test_make_response(
				422,
				array(
					'message'    => 'Validation failed.',
					'error_code' => 'validation_error',
					'errors'     => array( 'scheduled_at' => array( 'Must be in the future.' ) ),
				)
			)
		);

		$result = $this->client()->schedule_campaign( 'c1', array( 'scheduled_at' => 'x' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'lettr_api_validation_error', $result->get_error_code() );
		$data = $result->get_error_data();
		$this->assertSame( 422, $data['status'] );
		$this->assertSame( 'Validation failed.', $data['message'] );
		$this->assertSame( array( 'scheduled_at' => array( 'Must be in the future.' ) ), $data['errors'] );
	}

	public function test_error_without_envelope_falls_back_to_status_code(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 500, 'gateway boom' ) );

		$result = $this->client()->health();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'lettr_api_500', $result->get_error_code() );
		$this->assertSame( 'Lettr API HTTP 500', $result->get_error_message() );
	}

	public function test_transport_error_is_returned_untouched(): void {
		$transport = new \WP_Error( 'http_request_failed', 'Connection timed out' );
		lettr_test_enqueue_response( $transport );

		$result = $this->client()->health();

		$this->assertSame( $transport, $result );
	}

	public function test_quota_headers_are_attached_to_successful_response(): void {
		lettr_test_enqueue_response(
			lettr_test_make_response(
				200,
				array( 'message' => 'sent' ),
				array(
					'X-Monthly-Limit'     => '1000',
					'X-Monthly-Remaining' => '997',
					'X-Daily-Reset'       => '2026-05-30T00:00:00Z',
					'X-Irrelevant'        => 'ignored',
				)
			)
		);

		$result = $this->client()->send_email( array( 'from' => 'a@b.c', 'to' => 'd@e.f', 'text' => 'hi' ) );

		$this->assertArrayHasKey( '_quota', $result );
		$this->assertSame( '1000', $result['_quota']['X-Monthly-Limit'] );
		$this->assertSame( '997', $result['_quota']['X-Monthly-Remaining'] );
		$this->assertSame( '2026-05-30T00:00:00Z', $result['_quota']['X-Daily-Reset'] );
		$this->assertArrayNotHasKey( 'X-Irrelevant', $result['_quota'] );
	}

	public function test_no_quota_key_when_headers_absent(): void {
		lettr_test_enqueue_response( lettr_test_make_response( 200, array( 'message' => 'sent' ) ) );

		$result = $this->client()->send_email( array( 'from' => 'a@b.c', 'to' => 'd@e.f', 'text' => 'hi' ) );

		$this->assertArrayNotHasKey( '_quota', $result );
	}

	public function test_zero_query_value_is_preserved_but_empty_string_dropped(): void {
		$this->client()->list_emails(
			array(
				'per_page' => 0,
				'cursor'   => '',
				'from'     => 'x',
			)
		);

		$query = $this->lastQuery();
		$this->assertArrayHasKey( 'per_page', $query );
		$this->assertSame( '0', $query['per_page'] );
		$this->assertArrayNotHasKey( 'cursor', $query );
		$this->assertSame( 'x', $query['from'] );
	}

	public function test_body_is_json_encoded(): void {
		$payload = array( 'name' => 'Spring', 'nested' => array( 'a' => 1 ) );
		$this->client()->create_template( $payload );

		$request = lettr_test_last_request();
		$this->assertSame( json_encode( $payload ), $request['args']['body'] );
		$this->assertSame( $payload, $this->lastBody() );
	}
}
