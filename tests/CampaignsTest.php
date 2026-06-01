<?php

declare( strict_types=1 );

namespace Lettr\Tests;

/**
 * Tests for the Campaigns client methods (the /campaigns surface).
 */
final class CampaignsTest extends LettrTestCase {

	public function test_list_campaigns_hits_the_collection_endpoint(): void {
		$this->client()->list_campaigns();

		$this->assertSame( 'GET', $this->lastMethod() );
		$this->assertSame( '/campaigns', $this->lastPath() );
		$this->assertSame( array(), $this->lastQuery() );
		$this->assertNull( $this->lastBody() );
	}

	public function test_list_campaigns_forwards_query_filters(): void {
		$this->client()->list_campaigns(
			array(
				'page'     => 2,
				'per_page' => 50,
				'status'   => 'sent',
			)
		);

		$this->assertSame( 'GET', $this->lastMethod() );
		$this->assertStringStartsWith( '/campaigns?', $this->lastPath() );
		$this->assertSame(
			array(
				'page'     => '2',
				'per_page' => '50',
				'status'   => 'sent',
			),
			$this->lastQuery()
		);
	}

	public function test_list_campaigns_drops_empty_query_values(): void {
		$this->client()->list_campaigns(
			array(
				'page'   => 1,
				'status' => '',
				'extra'  => null,
			)
		);

		$this->assertSame( array( 'page' => '1' ), $this->lastQuery() );
	}

	public function test_get_campaign_builds_the_item_path(): void {
		$this->client()->get_campaign( '0193e6a8-1f3a-7c2a-b9e2-1aa1d2e5d3f0' );

		$this->assertSame( 'GET', $this->lastMethod() );
		$this->assertSame( '/campaigns/0193e6a8-1f3a-7c2a-b9e2-1aa1d2e5d3f0', $this->lastPath() );
	}

	public function test_get_campaign_url_encodes_the_id(): void {
		$this->client()->get_campaign( 'a b/c' );

		$this->assertStringContainsString( '/campaigns/a%20b%2Fc', $this->lastUrl() );
	}

	public function test_list_campaign_events_builds_path_and_query(): void {
		$this->client()->list_campaign_events(
			'camp-1',
			array(
				'event_type' => 'open',
				'email'      => 'jane@example.com',
				'limit'      => 100,
				'cursor'     => 'abc123',
			)
		);

		$this->assertSame( 'GET', $this->lastMethod() );
		$this->assertStringStartsWith( '/campaigns/camp-1/events?', $this->lastPath() );
		$this->assertSame(
			array(
				'event_type' => 'open',
				'email'      => 'jane@example.com',
				'limit'      => '100',
				'cursor'     => 'abc123',
			),
			$this->lastQuery()
		);
	}

	public function test_list_campaign_events_without_query_omits_cursor(): void {
		$this->client()->list_campaign_events( 'camp-1' );

		$this->assertSame( '/campaigns/camp-1/events', $this->lastPath() );
		$this->assertSame( array(), $this->lastQuery() );
	}

	public function test_send_campaign_posts_without_a_body(): void {
		$this->client()->send_campaign( 'camp-1' );

		$this->assertSame( 'POST', $this->lastMethod() );
		$this->assertSame( '/campaigns/camp-1/send', $this->lastPath() );
		$this->assertNull( $this->lastBody() );
		$this->assertArrayNotHasKey( 'Content-Type', $this->lastHeaders() );
	}

	public function test_send_campaign_returns_array_on_202_accepted(): void {
		lettr_test_enqueue_response(
			lettr_test_make_response( 202, array( 'message' => 'Campaign sending started.' ) )
		);

		$result = $this->client()->send_campaign( 'camp-1' );

		$this->assertIsArray( $result );
		$this->assertSame( 'Campaign sending started.', $result['message'] );
	}

	public function test_schedule_campaign_posts_scheduled_at_in_body(): void {
		$this->client()->schedule_campaign( 'camp-1', array( 'scheduled_at' => '2026-06-01T09:00:00+00:00' ) );

		$this->assertSame( 'POST', $this->lastMethod() );
		$this->assertSame( '/campaigns/camp-1/schedule', $this->lastPath() );
		$this->assertSame( array( 'scheduled_at' => '2026-06-01T09:00:00+00:00' ), $this->lastBody() );
		$this->assertSame( 'application/json', $this->lastHeaders()['Content-Type'] ?? null );
	}

	public function test_unschedule_campaign_posts_without_a_body(): void {
		$this->client()->unschedule_campaign( 'camp-1' );

		$this->assertSame( 'POST', $this->lastMethod() );
		$this->assertSame( '/campaigns/camp-1/unschedule', $this->lastPath() );
		$this->assertNull( $this->lastBody() );
	}

	public function test_campaign_action_propagates_validation_error(): void {
		lettr_test_enqueue_response(
			lettr_test_make_response(
				422,
				array(
					'message'    => 'Campaign can only be sent from draft status.',
					'error_code' => 'campaign_not_sendable',
				)
			)
		);

		$result = $this->client()->send_campaign( 'camp-1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'lettr_api_campaign_not_sendable', $result->get_error_code() );
		$this->assertSame( 'Campaign can only be sent from draft status.', $result->get_error_message() );
		$this->assertSame( 422, $result->get_error_data()['status'] );
	}
}
