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
