<?php

declare( strict_types=1 );

namespace Lettr\Tests;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verb + path + body coverage across the rest of the client surface. Each case
 * names the method, its arguments, and the expected HTTP method / path / body
 * so a wrong verb, a missing rawurlencode(), or a mis-shaped body is caught.
 */
final class ApiClientTest extends LettrTestCase {

	/**
	 * @param mixed[] $args
	 * @param mixed   $expected_body
	 */
	#[DataProvider('endpointProvider')]
	public function test_endpoint_builds_expected_request( string $method_name, array $args, string $http_method, string $path, $expected_body ): void {
		call_user_func_array( array( $this->client(), $method_name ), $args );

		$this->assertSame( $http_method, $this->lastMethod(), "$method_name HTTP verb" );
		$this->assertSame( $path, $this->lastPath(), "$method_name path" );
		$this->assertSame( $expected_body, $this->lastBody(), "$method_name body" );
	}

	/**
	 * @return array<string,array{0:string,1:mixed[],2:string,3:string,4:mixed}>
	 */
	public static function endpointProvider(): array {
		return array(
			// Health / Auth.
			'health'                => array( 'health', array(), 'GET', '/health', null ),
			'auth_check'            => array( 'auth_check', array(), 'GET', '/auth/check', null ),

			// Emails.
			'send_email'            => array( 'send_email', array( array( 'to' => 'a@b.c' ) ), 'POST', '/emails', array( 'to' => 'a@b.c' ) ),
			'list_emails'           => array( 'list_emails', array(), 'GET', '/emails', null ),
			'list_email_events'     => array( 'list_email_events', array(), 'GET', '/emails/events', null ),
			'get_email'             => array( 'get_email', array( 'req 1' ), 'GET', '/emails/req%201', null ),
			'schedule_email'        => array( 'schedule_email', array( array( 'scheduled_at' => 't' ) ), 'POST', '/emails/scheduled', array( 'scheduled_at' => 't' ) ),
			'get_scheduled_email'   => array( 'get_scheduled_email', array( 'tx1' ), 'GET', '/emails/scheduled/tx1', null ),
			'cancel_scheduled'      => array( 'cancel_scheduled_email', array( 'tx1' ), 'DELETE', '/emails/scheduled/tx1', null ),

			// Domains.
			'list_domains'          => array( 'list_domains', array(), 'GET', '/domains', null ),
			'create_domain'         => array( 'create_domain', array( 'ex.com' ), 'POST', '/domains', array( 'domain' => 'ex.com' ) ),
			'get_domain'            => array( 'get_domain', array( 'ex.com' ), 'GET', '/domains/ex.com', null ),
			'delete_domain'         => array( 'delete_domain', array( 'ex.com' ), 'DELETE', '/domains/ex.com', null ),
			'verify_domain'         => array( 'verify_domain', array( 'ex.com' ), 'POST', '/domains/ex.com/verify', null ),

			// Webhooks.
			'list_webhooks'         => array( 'list_webhooks', array(), 'GET', '/webhooks', null ),
			'create_webhook'        => array( 'create_webhook', array( array( 'name' => 'wh' ) ), 'POST', '/webhooks', array( 'name' => 'wh' ) ),
			'get_webhook'           => array( 'get_webhook', array( 'wh1' ), 'GET', '/webhooks/wh1', null ),
			'update_webhook'        => array( 'update_webhook', array( 'wh1', array( 'active' => false ) ), 'PUT', '/webhooks/wh1', array( 'active' => false ) ),
			'delete_webhook'        => array( 'delete_webhook', array( 'wh1' ), 'DELETE', '/webhooks/wh1', null ),

			// Templates.
			'list_templates'        => array( 'list_templates', array(), 'GET', '/templates', null ),
			'create_template'       => array( 'create_template', array( array( 'name' => 't' ) ), 'POST', '/templates', array( 'name' => 't' ) ),
			'get_template'          => array( 'get_template', array( 'slug' ), 'GET', '/templates/slug', null ),
			'update_template'       => array( 'update_template', array( 'slug', array( 'name' => 'x' ) ), 'PUT', '/templates/slug', array( 'name' => 'x' ) ),
			'delete_template'       => array( 'delete_template', array( 'slug' ), 'DELETE', '/templates/slug', null ),
			'merge_tags'            => array( 'get_template_merge_tags', array( 'slug' ), 'GET', '/templates/slug/merge-tags', null ),

			// Projects.
			'list_projects'         => array( 'list_projects', array(), 'GET', '/projects', null ),

			// Audience: lists.
			'list_audience_lists'   => array( 'list_audience_lists', array(), 'GET', '/audience/lists', null ),
			'create_audience_list'  => array( 'create_audience_list', array( array( 'name' => 'L' ) ), 'POST', '/audience/lists', array( 'name' => 'L' ) ),
			'bulk_delete_lists'     => array( 'bulk_delete_audience_lists', array( array( 'a', 'b' ) ), 'DELETE', '/audience/lists/bulk', array( 'list_ids' => array( 'a', 'b' ) ) ),
			'get_audience_list'     => array( 'get_audience_list', array( 'l1' ), 'GET', '/audience/lists/l1', null ),
			'update_audience_list'  => array( 'update_audience_list', array( 'l1', array( 'name' => 'N' ) ), 'PATCH', '/audience/lists/l1', array( 'name' => 'N' ) ),
			'delete_audience_list'  => array( 'delete_audience_list', array( 'l1' ), 'DELETE', '/audience/lists/l1', null ),

			// Audience: contacts.
			'list_contacts'         => array( 'list_audience_contacts', array(), 'GET', '/audience/contacts', null ),
			'create_contact'        => array( 'create_audience_contact', array( array( 'email' => 'a@b.c' ) ), 'POST', '/audience/contacts', array( 'email' => 'a@b.c' ) ),
			'bulk_attach_lists'     => array( 'bulk_attach_audience_contacts_to_lists', array( array( 'contact_ids' => array( 'c1' ), 'list_ids' => array( 'l1' ) ) ), 'POST', '/audience/contacts/lists/bulk', array( 'contact_ids' => array( 'c1' ), 'list_ids' => array( 'l1' ) ) ),
			'bulk_detach_lists'     => array( 'bulk_detach_audience_contacts_from_lists', array( array( 'contact_ids' => array( 'c1' ), 'list_ids' => array( 'l1' ) ) ), 'DELETE', '/audience/contacts/lists/bulk', array( 'contact_ids' => array( 'c1' ), 'list_ids' => array( 'l1' ) ) ),
			'get_contact'           => array( 'get_audience_contact', array( 'c1' ), 'GET', '/audience/contacts/c1', null ),
			'update_contact'        => array( 'update_audience_contact', array( 'c1', array( 'email' => 'x@y.z' ) ), 'PATCH', '/audience/contacts/c1', array( 'email' => 'x@y.z' ) ),
			'delete_contact'        => array( 'delete_audience_contact', array( 'c1' ), 'DELETE', '/audience/contacts/c1', null ),
			'attach_contact'        => array( 'attach_audience_contact_to_list', array( 'c1', 'l1' ), 'POST', '/audience/contacts/c1/lists/l1', null ),
			'detach_contact'        => array( 'detach_audience_contact_from_list', array( 'c1', 'l1' ), 'DELETE', '/audience/contacts/c1/lists/l1', null ),
			'subscribe_topic'       => array( 'subscribe_audience_contact_to_topic', array( 'c1', 't1' ), 'POST', '/audience/contacts/c1/topics/t1', null ),
			'unsubscribe_topic'     => array( 'unsubscribe_audience_contact_from_topic', array( 'c1', 't1' ), 'DELETE', '/audience/contacts/c1/topics/t1', null ),

			// Audience: topics / properties / segments.
			'list_topics'           => array( 'list_audience_topics', array(), 'GET', '/audience/topics', null ),
			'create_topic'          => array( 'create_audience_topic', array( array( 'name' => 'T' ) ), 'POST', '/audience/topics', array( 'name' => 'T' ) ),
			'get_topic'             => array( 'get_audience_topic', array( 't1' ), 'GET', '/audience/topics/t1', null ),
			'update_topic'          => array( 'update_audience_topic', array( 't1', array( 'name' => 'N' ) ), 'PATCH', '/audience/topics/t1', array( 'name' => 'N' ) ),
			'delete_topic'          => array( 'delete_audience_topic', array( 't1' ), 'DELETE', '/audience/topics/t1', null ),
			'list_properties'       => array( 'list_audience_properties', array(), 'GET', '/audience/properties', null ),
			'create_property'       => array( 'create_audience_property', array( array( 'name' => 'P' ) ), 'POST', '/audience/properties', array( 'name' => 'P' ) ),
			'get_property'          => array( 'get_audience_property', array( 'p1' ), 'GET', '/audience/properties/p1', null ),
			'update_property'       => array( 'update_audience_property', array( 'p1', array( 'name' => 'N' ) ), 'PATCH', '/audience/properties/p1', array( 'name' => 'N' ) ),
			'delete_property'       => array( 'delete_audience_property', array( 'p1' ), 'DELETE', '/audience/properties/p1', null ),
			'list_segments'         => array( 'list_audience_segments', array(), 'GET', '/audience/segments', null ),
			'create_segment'        => array( 'create_audience_segment', array( array( 'name' => 'S' ) ), 'POST', '/audience/segments', array( 'name' => 'S' ) ),
			'get_segment'           => array( 'get_audience_segment', array( 's1' ), 'GET', '/audience/segments/s1', null ),
			'update_segment'        => array( 'update_audience_segment', array( 's1', array( 'name' => 'N' ) ), 'PATCH', '/audience/segments/s1', array( 'name' => 'N' ) ),
			'delete_segment'        => array( 'delete_audience_segment', array( 's1' ), 'DELETE', '/audience/segments/s1', null ),
		);
	}

	public function test_get_template_html_passes_both_query_params(): void {
		$this->client()->get_template_html( 'proj 1', 'slug-1' );

		$this->assertSame( 'GET', $this->lastMethod() );
		$this->assertStringStartsWith( '/templates/html?', $this->lastPath() );
		$this->assertSame(
			array(
				'project_id' => 'proj 1',
				'slug'       => 'slug-1',
			),
			$this->lastQuery()
		);
	}

	public function test_bulk_create_contacts_wraps_payload_in_body(): void {
		$payload = array( 'contacts' => array( array( 'email' => 'a@b.c' ) ) );
		$this->client()->bulk_create_audience_contacts( $payload );

		$this->assertSame( 'POST', $this->lastMethod() );
		$this->assertSame( '/audience/contacts/bulk', $this->lastPath() );
		$this->assertSame( $payload, $this->lastBody() );
	}
}
