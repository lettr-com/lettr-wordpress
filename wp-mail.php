<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse an address line into [email, name].
 * Accepts both `user@example.com` and `Name <user@example.com>` forms.
 *
 * @param string $address
 * @return array{0:string,1:?string}
 */
function lettr_parse_address( $address ) {
	$address = trim( $address );

	if ( preg_match( '/^(.*)<(.+)>\s*$/', $address, $m ) ) {
		$name  = trim( $m[1] );
		$email = trim( $m[2] );
		// Strip surrounding quotes from name if present.
		if ( strlen( $name ) >= 2 && '"' === $name[0] && '"' === substr( $name, -1 ) ) {
			$name = substr( $name, 1, -1 );
		}
		return array( $email, '' === $name ? null : $name );
	}

	return array( $address, null );
}

/**
 * Headers that the Lettr API manages itself and refuses in the `headers` body
 * field. Comparison is case-insensitive.
 */
function lettr_forbidden_passthrough_headers() {
	return array(
		'from',
		'to',
		'cc',
		'bcc',
		'subject',
		'reply-to',
		'content-type',
		'x-msys-api',
		'list-unsubscribe',
		'list-unsubscribe-post',
	);
}

/**
 * WP Mail
 *
 * @param string|string[] $to
 * @param string $subject
 * @param string $message
 * @param string|string[] $headers
 * @param string|string[] $attachments
 * @return bool
 */
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail() core hook contract.
	$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail() core hook contract.
	$pre_wp_mail = apply_filters( 'pre_wp_mail', null, $atts );

	if ( null !== $pre_wp_mail ) {
		return $pre_wp_mail;
	}

	if ( isset( $atts['to'] ) ) {
		$to = $atts['to'];
	}
	if ( isset( $atts['subject'] ) ) {
		$subject = $atts['subject'];
	}
	if ( isset( $atts['message'] ) ) {
		$message = $atts['message'];
	}
	if ( isset( $atts['headers'] ) ) {
		$headers = $atts['headers'];
	}
	if ( isset( $atts['attachments'] ) ) {
		$attachments = $atts['attachments'];
	}

	if ( ! is_array( $attachments ) ) {
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	}

	if ( ! is_array( $to ) ) {
		$to = explode( ',', $to );
	}

	$content_type  = 'text/plain';
	$cc            = array();
	$bcc           = array();
	$reply_to      = null;
	$reply_name    = null;
	$extra_headers = array();

	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( ! is_array( $headers ) ) {
			$temp_headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$temp_headers = $headers;
		}

		$headers = array();

		if ( ! empty( $temp_headers ) ) {
			foreach ( (array) $temp_headers as $header ) {
				if ( false === strpos( $header, ':' ) ) {
					continue;
				}

				list($name, $content) = explode( ':', trim( $header ), 2 );

				$name    = trim( $name );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					case 'content-type':
						if ( strpos( $content, ';' ) ) {
							list($type, $charset_content) = explode( ';', $content );
							$content_type                 = trim( $type );
						} elseif ( '' !== trim( $content ) ) {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						foreach ( explode( ',', $content ) as $addr ) {
							list($email) = lettr_parse_address( $addr );
							if ( '' !== $email ) {
								$cc[] = $email;
							}
						}
						break;
					case 'bcc':
						foreach ( explode( ',', $content ) as $addr ) {
							list($email) = lettr_parse_address( $addr );
							if ( '' !== $email ) {
								$bcc[] = $email;
							}
						}
						break;
					case 'reply-to':
						list($reply_to, $reply_name) = lettr_parse_address( $content );
						break;
					default:
						$extra_headers[ $name ] = $content;
						break;
				}
			}
		}
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail() core hook contract.
	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	// The Lettr-configured sender is authoritative. We deliberately do NOT
	// apply the `wp_mail_from` / `wp_mail_from_name` filters here so that
	// other mail plugins (e.g. wp-mail-smtp) cannot override the sender that
	// the user explicitly set in the Lettr settings page.
	$from_name  = Lettr::get_from_name();
	$from_email = Lettr::get_from_address();

	// Normalize $to: accept both bare emails and "Name <email>".
	$to_emails = array();
	foreach ( (array) $to as $addr ) {
		list($email) = lettr_parse_address( $addr );
		if ( '' !== $email ) {
			$to_emails[] = $email;
		}
	}

	$body = array(
		'from'    => $from_email,
		'to'      => $to_emails,
		'subject' => $subject,
	);

	if ( ! empty( $from_name ) ) {
		$body['from_name'] = $from_name;
	}

	if ( ! empty( $cc ) ) {
		$body['cc'] = $cc;
	}

	if ( ! empty( $bcc ) ) {
		$body['bcc'] = $bcc;
	}

	if ( null !== $reply_to ) {
		$body['reply_to'] = $reply_to;
		if ( null !== $reply_name ) {
			$body['reply_to_name'] = $reply_name;
		}
	}

	if ( 'text/html' === $content_type ) {
		$body['html'] = $message;
	} else {
		$body['text'] = $message;
	}

	// Custom headers — drop anything the API manages itself, cap at 10 per spec.
	if ( ! empty( $extra_headers ) ) {
		$forbidden   = lettr_forbidden_passthrough_headers();
		$passthrough = array();
		foreach ( $extra_headers as $name => $value ) {
			if ( in_array( strtolower( $name ), $forbidden, true ) ) {
				continue;
			}
			$passthrough[ $name ] = $value;
			if ( count( $passthrough ) >= 10 ) {
				break;
			}
		}
		if ( ! empty( $passthrough ) ) {
			$body['headers'] = $passthrough;
		}
	}

	if ( ! empty( $attachments ) ) {
		$body['attachments'] = array();
		foreach ( $attachments as $attachment ) {
			if ( ! is_readable( $attachment ) ) {
				continue;
			}
			$contents = file_get_contents( $attachment );
			if ( false === $contents ) {
				continue;
			}
			$filename  = basename( $attachment );
			$mime_info = wp_check_filetype( $filename );
			$mime      = ! empty( $mime_info['type'] ) ? $mime_info['type'] : 'application/octet-stream';

			$body['attachments'][] = array(
				'name' => $filename,
				'type' => $mime,
				'data' => str_replace( array( "\r", "\n" ), '', base64_encode( $contents ) ),
			);
		}
	}

	$api      = new Lettr_Api();
	$response = $api->send_email( $body );

	if ( is_wp_error( $response ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail() core hook contract.
		do_action( 'wp_mail_failed', $response );
		Lettr_Admin::add_status( 'lettr-error', $response->get_error_data() );
		return false;
	}

	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail() core hook contract.
	do_action(
		'wp_mail_succeeded',
		array(
			'to'          => $body['to'],
			'subject'     => $body['subject'],
			'message'     => isset( $body['html'] ) ? $body['html'] : ( isset( $body['text'] ) ? $body['text'] : '' ),
			'headers'     => $headers,
			'attachments' => isset( $body['attachments'] ) ? $body['attachments'] : null,
		)
	);
	// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

	return true;
}
