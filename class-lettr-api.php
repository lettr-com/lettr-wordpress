<?php

/**
 * Lettr API client.
 *
 * Thin PHP wrapper around the Lettr REST API. The single source of truth for
 * the surface here is `lettr/resources/openapi/openapi.json` (Lettr API v1.0.0).
 * Every operation in the spec has exactly one public method on this class.
 */
class Lettr_Api {

	const BASE_URL = 'https://app.lettr.com/api';

	/** @var string */
	private $api_key;

	public function __construct( $api_key = null ) {
		$this->api_key = null === $api_key ? Lettr::get_api_key() : $api_key;
	}

	// -- Health / Auth ---------------------------------------------------

	public function health() {
		return $this->request( 'GET', '/health' );
	}

	public function auth_check() {
		return $this->request( 'GET', '/auth/check' );
	}

	// -- Emails ----------------------------------------------------------

	/**
	 * Send an email.
	 *
	 * @param array $payload SendEmailRequest. Required: `from`, `to`, plus one
	 *                       of `html` / `text` / `template_slug`. See the
	 *                       OpenAPI `SendEmailRequest` schema for the full set
	 *                       of accepted keys (from_name, subject, cc, bcc,
	 *                       reply_to, reply_to_name, html, text, amp_html,
	 *                       project_id, template_slug, template_version, tag,
	 *                       metadata, headers, substitution_data, options,
	 *                       attachments).
	 */
	public function send_email( array $payload ) {
		return $this->request( 'POST', '/emails', array( 'body' => $payload ) );
	}

	/**
	 * @param array $query per_page, cursor, recipients, from, to
	 */
	public function list_emails( array $query = array() ) {
		return $this->request( 'GET', '/emails', array( 'query' => $query ) );
	}

	/**
	 * @param array $query events, recipients, from, to, per_page, cursor, transmissions, bounce_classes
	 */
	public function list_email_events( array $query = array() ) {
		return $this->request( 'GET', '/emails/events', array( 'query' => $query ) );
	}

	/**
	 * @param array $query from, to
	 */
	public function get_email( $request_id, array $query = array() ) {
		return $this->request( 'GET', '/emails/' . rawurlencode( $request_id ), array( 'query' => $query ) );
	}

	/**
	 * @param array $payload ScheduleEmailRequest — same as send_email plus required `scheduled_at` (ISO 8601).
	 */
	public function schedule_email( array $payload ) {
		return $this->request( 'POST', '/emails/scheduled', array( 'body' => $payload ) );
	}

	public function get_scheduled_email( $transmission_id ) {
		return $this->request( 'GET', '/emails/scheduled/' . rawurlencode( $transmission_id ) );
	}

	public function cancel_scheduled_email( $transmission_id ) {
		return $this->request( 'DELETE', '/emails/scheduled/' . rawurlencode( $transmission_id ) );
	}

	// -- Domains ---------------------------------------------------------

	public function list_domains() {
		return $this->request( 'GET', '/domains' );
	}

	public function create_domain( $domain ) {
		return $this->request( 'POST', '/domains', array( 'body' => array( 'domain' => $domain ) ) );
	}

	public function get_domain( $domain ) {
		return $this->request( 'GET', '/domains/' . rawurlencode( $domain ) );
	}

	public function delete_domain( $domain ) {
		return $this->request( 'DELETE', '/domains/' . rawurlencode( $domain ) );
	}

	public function verify_domain( $domain ) {
		return $this->request( 'POST', '/domains/' . rawurlencode( $domain ) . '/verify' );
	}

	// -- Webhooks --------------------------------------------------------

	public function list_webhooks() {
		return $this->request( 'GET', '/webhooks' );
	}

	/**
	 * @param array $payload Required: name, url, auth_type, events_mode.
	 *                       Optional: auth_username, auth_password,
	 *                       oauth_client_id, oauth_client_secret,
	 *                       oauth_token_url, events.
	 */
	public function create_webhook( array $payload ) {
		return $this->request( 'POST', '/webhooks', array( 'body' => $payload ) );
	}

	public function get_webhook( $webhook_id ) {
		return $this->request( 'GET', '/webhooks/' . rawurlencode( $webhook_id ) );
	}

	/**
	 * @param array $payload Optional: name, url, target, auth_type, auth_username,
	 *                       auth_password, oauth_token_url, oauth_client_id,
	 *                       oauth_client_secret, events, active.
	 */
	public function update_webhook( $webhook_id, array $payload ) {
		return $this->request( 'PUT', '/webhooks/' . rawurlencode( $webhook_id ), array( 'body' => $payload ) );
	}

	public function delete_webhook( $webhook_id ) {
		return $this->request( 'DELETE', '/webhooks/' . rawurlencode( $webhook_id ) );
	}

	// -- Templates -------------------------------------------------------

	/**
	 * @param array $query project_id, per_page, page
	 */
	public function list_templates( array $query = array() ) {
		return $this->request( 'GET', '/templates', array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Required: name. Optional: project_id, folder_id, html, json.
	 */
	public function create_template( array $payload ) {
		return $this->request( 'POST', '/templates', array( 'body' => $payload ) );
	}

	/**
	 * @param array $query project_id
	 */
	public function get_template( $slug, array $query = array() ) {
		return $this->request( 'GET', '/templates/' . rawurlencode( $slug ), array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Optional: project_id, name, html, json.
	 */
	public function update_template( $slug, array $payload ) {
		return $this->request( 'PUT', '/templates/' . rawurlencode( $slug ), array( 'body' => $payload ) );
	}

	/**
	 * @param array $query project_id
	 */
	public function delete_template( $slug, array $query = array() ) {
		return $this->request( 'DELETE', '/templates/' . rawurlencode( $slug ), array( 'query' => $query ) );
	}

	/**
	 * @param array $query project_id, version
	 */
	public function get_template_merge_tags( $slug, array $query = array() ) {
		return $this->request( 'GET', '/templates/' . rawurlencode( $slug ) . '/merge-tags', array( 'query' => $query ) );
	}

	public function get_template_html( $project_id, $slug ) {
		return $this->request(
			'GET',
			'/templates/html',
			array(
				'query' => array(
					'project_id' => $project_id,
					'slug'       => $slug,
				),
			)
		);
	}

	// -- Projects --------------------------------------------------------

	/**
	 * @param array $query per_page, page
	 */
	public function list_projects( array $query = array() ) {
		return $this->request( 'GET', '/projects', array( 'query' => $query ) );
	}

	// -- Audience: Lists -------------------------------------------------

	/**
	 * @param array $query page, per_page
	 */
	public function list_audience_lists( array $query = array() ) {
		return $this->request( 'GET', '/audience/lists', array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Required: name.
	 */
	public function create_audience_list( array $payload ) {
		return $this->request( 'POST', '/audience/lists', array( 'body' => $payload ) );
	}

	/**
	 * @param array $list_ids List IDs to delete.
	 */
	public function bulk_delete_audience_lists( array $list_ids ) {
		return $this->request( 'DELETE', '/audience/lists/bulk', array( 'body' => array( 'list_ids' => $list_ids ) ) );
	}

	public function get_audience_list( $list_id ) {
		return $this->request( 'GET', '/audience/lists/' . rawurlencode( $list_id ) );
	}

	/**
	 * @param array $payload Optional: name.
	 */
	public function update_audience_list( $list_id, array $payload ) {
		return $this->request( 'PATCH', '/audience/lists/' . rawurlencode( $list_id ), array( 'body' => $payload ) );
	}

	public function delete_audience_list( $list_id ) {
		return $this->request( 'DELETE', '/audience/lists/' . rawurlencode( $list_id ) );
	}

	// -- Audience: Contacts ----------------------------------------------

	/**
	 * @param array $query page, per_page, search, status, list_id, segment_id
	 */
	public function list_audience_contacts( array $query = array() ) {
		return $this->request( 'GET', '/audience/contacts', array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Required: email. Optional: list_id, properties, double_opt_in.
	 */
	public function create_audience_contact( array $payload ) {
		return $this->request( 'POST', '/audience/contacts', array( 'body' => $payload ) );
	}

	/**
	 * @param array $payload Required: emails. Optional: list_id, properties.
	 */
	public function bulk_create_audience_contacts( array $payload ) {
		return $this->request( 'POST', '/audience/contacts/bulk', array( 'body' => $payload ) );
	}

	/**
	 * @param array $payload Required: contact_ids, list_ids.
	 */
	public function bulk_attach_audience_contacts_to_lists( array $payload ) {
		return $this->request( 'POST', '/audience/contacts/lists/bulk', array( 'body' => $payload ) );
	}

	/**
	 * @param array $payload Required: contact_ids, list_ids.
	 */
	public function bulk_detach_audience_contacts_from_lists( array $payload ) {
		return $this->request( 'DELETE', '/audience/contacts/lists/bulk', array( 'body' => $payload ) );
	}

	public function get_audience_contact( $contact_id ) {
		return $this->request( 'GET', '/audience/contacts/' . rawurlencode( $contact_id ) );
	}

	/**
	 * @param array $payload Optional: email, status (subscribed|unsubscribed), properties.
	 */
	public function update_audience_contact( $contact_id, array $payload ) {
		return $this->request( 'PATCH', '/audience/contacts/' . rawurlencode( $contact_id ), array( 'body' => $payload ) );
	}

	public function delete_audience_contact( $contact_id ) {
		return $this->request( 'DELETE', '/audience/contacts/' . rawurlencode( $contact_id ) );
	}

	public function attach_audience_contact_to_list( $contact_id, $list_id ) {
		return $this->request( 'POST', '/audience/contacts/' . rawurlencode( $contact_id ) . '/lists/' . rawurlencode( $list_id ) );
	}

	public function detach_audience_contact_from_list( $contact_id, $list_id ) {
		return $this->request( 'DELETE', '/audience/contacts/' . rawurlencode( $contact_id ) . '/lists/' . rawurlencode( $list_id ) );
	}

	public function subscribe_audience_contact_to_topic( $contact_id, $topic_id ) {
		return $this->request( 'POST', '/audience/contacts/' . rawurlencode( $contact_id ) . '/topics/' . rawurlencode( $topic_id ) );
	}

	public function unsubscribe_audience_contact_from_topic( $contact_id, $topic_id ) {
		return $this->request( 'DELETE', '/audience/contacts/' . rawurlencode( $contact_id ) . '/topics/' . rawurlencode( $topic_id ) );
	}

	// -- Audience: Topics ------------------------------------------------

	/**
	 * @param array $query page, per_page
	 */
	public function list_audience_topics( array $query = array() ) {
		return $this->request( 'GET', '/audience/topics', array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Required: name. Optional: description,
	 *                       default_subscription (opt_in|opt_out),
	 *                       visibility (public|private).
	 */
	public function create_audience_topic( array $payload ) {
		return $this->request( 'POST', '/audience/topics', array( 'body' => $payload ) );
	}

	public function get_audience_topic( $topic_id ) {
		return $this->request( 'GET', '/audience/topics/' . rawurlencode( $topic_id ) );
	}

	/**
	 * @param array $payload Optional: name, description, visibility (public|private).
	 */
	public function update_audience_topic( $topic_id, array $payload ) {
		return $this->request( 'PATCH', '/audience/topics/' . rawurlencode( $topic_id ), array( 'body' => $payload ) );
	}

	public function delete_audience_topic( $topic_id ) {
		return $this->request( 'DELETE', '/audience/topics/' . rawurlencode( $topic_id ) );
	}

	// -- Audience: Properties --------------------------------------------

	/**
	 * @param array $query page, per_page
	 */
	public function list_audience_properties( array $query = array() ) {
		return $this->request( 'GET', '/audience/properties', array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Required: name, type (string|number|boolean|date|json).
	 *                       Optional: fallback_value.
	 */
	public function create_audience_property( array $payload ) {
		return $this->request( 'POST', '/audience/properties', array( 'body' => $payload ) );
	}

	public function get_audience_property( $property_id ) {
		return $this->request( 'GET', '/audience/properties/' . rawurlencode( $property_id ) );
	}

	/**
	 * @param array $payload Optional: fallback_value.
	 */
	public function update_audience_property( $property_id, array $payload ) {
		return $this->request( 'PATCH', '/audience/properties/' . rawurlencode( $property_id ), array( 'body' => $payload ) );
	}

	public function delete_audience_property( $property_id ) {
		return $this->request( 'DELETE', '/audience/properties/' . rawurlencode( $property_id ) );
	}

	// -- Audience: Segments ----------------------------------------------

	/**
	 * @param array $query page, per_page, list_id
	 */
	public function list_audience_segments( array $query = array() ) {
		return $this->request( 'GET', '/audience/segments', array( 'query' => $query ) );
	}

	/**
	 * @param array $payload Required: name, conditions. Optional: list_id.
	 */
	public function create_audience_segment( array $payload ) {
		return $this->request( 'POST', '/audience/segments', array( 'body' => $payload ) );
	}

	public function get_audience_segment( $segment_id ) {
		return $this->request( 'GET', '/audience/segments/' . rawurlencode( $segment_id ) );
	}

	/**
	 * @param array $payload Optional: name, list_id, conditions.
	 */
	public function update_audience_segment( $segment_id, array $payload ) {
		return $this->request( 'PATCH', '/audience/segments/' . rawurlencode( $segment_id ), array( 'body' => $payload ) );
	}

	public function delete_audience_segment( $segment_id ) {
		return $this->request( 'DELETE', '/audience/segments/' . rawurlencode( $segment_id ) );
	}

	// -- Campaigns -------------------------------------------------------

	/**
	 * @param array $query page, per_page, status
	 */
	public function list_campaigns( array $query = array() ) {
		return $this->request( 'GET', '/campaigns', array( 'query' => $query ) );
	}

	public function get_campaign( $campaign_id ) {
		return $this->request( 'GET', '/campaigns/' . rawurlencode( $campaign_id ) );
	}

	/**
	 * @param array $query event_type, email, start_date, end_date, limit, cursor
	 */
	public function list_campaign_events( $campaign_id, array $query = array() ) {
		return $this->request( 'GET', '/campaigns/' . rawurlencode( $campaign_id ) . '/events', array( 'query' => $query ) );
	}

	public function send_campaign( $campaign_id ) {
		return $this->request( 'POST', '/campaigns/' . rawurlencode( $campaign_id ) . '/send' );
	}

	/**
	 * @param array $payload Required: scheduled_at (ISO 8601, future).
	 */
	public function schedule_campaign( $campaign_id, array $payload ) {
		return $this->request( 'POST', '/campaigns/' . rawurlencode( $campaign_id ) . '/schedule', array( 'body' => $payload ) );
	}

	public function unschedule_campaign( $campaign_id ) {
		return $this->request( 'POST', '/campaigns/' . rawurlencode( $campaign_id ) . '/unschedule' );
	}

	// -- Internal --------------------------------------------------------

	/**
	 * @param string $method GET|POST|PUT|PATCH|DELETE
	 * @param string $path   Path beginning with `/`
	 * @param array  $args   Optional: `body` (array, JSON-encoded) and `query` (array).
	 * @return array|true|WP_Error
	 *                       Decoded JSON body on 2xx with content; `true` on 204;
	 *                       WP_Error otherwise. For send_email, the response array
	 *                       carries an additional `_quota` key with rate-limit
	 *                       headers when the API returned them (free tier only).
	 */
	private function request( $method, $path, array $args = array() ) {
		$url = self::BASE_URL . $path;

		$query = isset( $args['query'] ) ? array_filter(
			$args['query'],
			static function ( $v ) {
				return null !== $v && '' !== $v && array() !== $v;
			}
		) : array();
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$headers = array(
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		);

		$request_args = array(
			'method'     => $method,
			'headers'    => $headers,
			'user-agent' => 'lettr-wordpress/' . ( defined( 'LETTR_VERSION' ) ? LETTR_VERSION : '0' ),
			'timeout'    => 15,
		);

		if ( array_key_exists( 'body', $args ) ) {
			$headers['Content-Type'] = 'application/json';
			$request_args['headers'] = $headers;
			$request_args['body']    = wp_json_encode( $args['body'] );
		}

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = '' === $body ? null : json_decode( $body, true );

		if ( 204 === $status ) {
			return true;
		}

		if ( $status >= 200 && $status < 300 ) {
			$result = is_array( $data ) ? $data : array();

			$quota = array();
			foreach ( array( 'X-Monthly-Limit', 'X-Monthly-Remaining', 'X-Monthly-Reset', 'X-Daily-Limit', 'X-Daily-Remaining', 'X-Daily-Reset' ) as $h ) {
				$value = wp_remote_retrieve_header( $response, $h );
				if ( '' !== $value && null !== $value ) {
					$quota[ $h ] = $value;
				}
			}
			if ( ! empty( $quota ) ) {
				$result['_quota'] = $quota;
			}

			return $result;
		}

		// Error response — try to parse the documented envelope.
		$message    = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : sprintf( 'Lettr API HTTP %d', $status );
		$error_code = is_array( $data ) && isset( $data['error_code'] ) ? $data['error_code'] : (string) $status;
		$err_data   = array(
			'status'  => $status,
			'message' => $message,
		);
		if ( is_array( $data ) && isset( $data['errors'] ) ) {
			$err_data['errors'] = $data['errors'];
		}

		return new WP_Error( 'lettr_api_' . $error_code, $message, $err_data );
	}
}
