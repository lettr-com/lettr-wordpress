<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lettr_Admin {

	public const NONCE = 'lettr-nonce';

	private static $initiated = false;

	private static $status  = array();
	private static $allowed = array(
		'strong' => array(),
	);

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	public static function init_hooks() {
		self::$initiated = true;

		// Admin
		add_action( 'admin_init', array( 'Lettr_Admin', 'admin_init' ) );
		add_action( 'admin_menu', array( 'Lettr_Admin', 'admin_menu' ), 5 );
		add_action( 'admin_enqueue_scripts', array( 'Lettr_Admin', 'load_resources' ) );

		// AJAX handlers
		add_action( 'wp_ajax_lettr_enter_key', array( 'Lettr_Admin', 'ajax_enter_api_key' ) );
		add_action( 'wp_ajax_lettr_settings', array( 'Lettr_Admin', 'ajax_settings' ) );
		add_action( 'wp_ajax_lettr_send_test', array( 'Lettr_Admin', 'ajax_send_test_email' ) );

		// Plugin links
		add_filter( 'plugin_action_links', array( 'Lettr_Admin', 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __FILE__ ) . '/lettr.php' ), array( 'Lettr_Admin', 'admin_plugin_settings_link' ) );
	}

	public static function admin_init() {
		if ( get_option( 'Activated_Lettr' ) ) {
			delete_option( 'Activated_Lettr' );
			if ( ! headers_sent() ) {
				$admin_url = self::get_page_url( 'init' );
				wp_safe_redirect( $admin_url );
				exit;
			}
		}
	}

	public static function admin_menu() {
		$hook = add_options_page( __( 'Lettr', 'lettr' ), __( 'Lettr', 'lettr' ), 'manage_options', 'lettr', array( 'Lettr_Admin', 'display_page' ) );

		if ( $hook ) {
			add_action( "load-$hook", array( 'Lettr_Admin', 'admin_help' ) );
		}
	}

	public static function admin_plugin_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( self::get_page_url() ) . '">' . __( 'Settings', 'lettr' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public static function load_resources() {
		global $hook_suffix;

		if ( in_array(
			$hook_suffix,
			apply_filters(
				'lettr_admin_page_hook_suffixes',
				array_merge(
					array(
						'index.php',
						'settings_page_lettr',
					)
				)
			),
			true
		) ) {
			$lettr_css_path = 'public/lettr.css';
			wp_register_style( 'lettr', plugin_dir_url( __FILE__ ) . $lettr_css_path, array(), self::get_asset_file_version( $lettr_css_path ) );
			wp_enqueue_style( 'lettr' );

			$lettr_font_inter_css_path = 'public/fonts/inter.css';
			wp_register_style( 'lettr-font-inter', plugin_dir_url( __FILE__ ) . $lettr_font_inter_css_path, array(), self::get_asset_file_version( $lettr_font_inter_css_path ) );
			wp_enqueue_style( 'lettr-font-inter' );

			$lettr_admin_css_path = 'public/lettr-admin.css';
			wp_register_style( 'lettr-admin', plugin_dir_url( __FILE__ ) . $lettr_admin_css_path, array(), self::get_asset_file_version( $lettr_admin_css_path ) );
			wp_enqueue_style( 'lettr-admin' );

			$lettr_admin_js_path = 'public/lettr-admin.js';
			wp_register_script( 'lettr-admin', plugin_dir_url( __FILE__ ) . $lettr_admin_js_path, array( 'jquery' ), self::get_asset_file_version( $lettr_admin_js_path ), array( 'in_footer' => true ) );
			wp_enqueue_script( 'lettr-admin' );
			wp_localize_script(
				'lettr-admin',
				'lettrAjax',
				array(
					'lettr_url' => self::get_page_url(),
					'ajax_url'  => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( self::NONCE ),
				)
			);
		}
	}

	public static function display_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view routing.
		if ( ! Lettr::get_api_key() || ( isset( $_GET['view'] ) && 'start' === $_GET['view'] ) ) {
			self::display_start_page();
		} else {
			self::display_configuration_page();
		}
	}

	public static function display_start_page() {
		$api_key = Lettr::get_api_key();

		if ( $api_key ) {
			self::display_configuration_page();
			return;
		}

		Lettr::view( 'start' );
	}

	public static function display_configuration_page() {
		$status = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view routing.
		if ( isset( $_GET['status'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view routing.
			$status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}

		$args = array();

		if ( 'connected' === $status ) {
			$args = array(
				'notice' => array(
					'message' => self::json_status( 'connected' )['message'],
					'success' => true,
				),
			);
		}

		Lettr::view( 'config', $args );
	}

	/**
	 * Add a notice with the given type and message.
	 *
	 * @param string $type
	 * @param null|string $message
	 * @return void
	 */
	public static function add_status( $type, $message ) {
		self::$status = array(
			'type'    => $type,
			'message' => $message,
		);
	}

	/**
	 * Get the JSON status payload for the given type.
	 */
	public static function json_status( $type, $message = null ) {
		if ( ! empty( self::$status ) ) {
			$message = self::get_status_message( self::$status['type'], self::$status['message'] );
		} else {
			$message = self::get_status_message( $type, $message );
		}

		return array(
			'type'    => $type,
			'message' => $message,
		);
	}

	/**
	 * Get the status message based on the given type or use the provided message.
	 *
	 * @return string
	 */
	protected static function get_status_message( $type, $message = null ) {
		if ( $message ) {
			if ( is_array( $message ) ) {
				$message = $message['message'] ?? $message['error'];
			}

			$message = wp_kses( esc_html( $message ), self::$allowed );

			return $message;
		}

		$message = '';

		switch ( $type ) {
			case 'connected':
				$message = __( 'Lettr is now connected to your site.', 'lettr' );
				break;
			case 'not-allowed':
				$message = __( 'You are not allowed to perform this action!', 'lettr' );
				break;
			case 'new-key-valid':
				$message = __( 'Lettr API key has been updated successfully.', 'lettr' );
				break;
			case 'no-change-to-key':
				$message = __( 'Unable to update your API key.', 'lettr' );
				break;
			case 'new-key-empty':
				$message = __( 'You did not enter an API key. Please try again.', 'lettr' );
				break;
			case 'new-key-invalid':
				$message = __( 'The API key you entered is invalid. Please double-check it.', 'lettr' );
				break;
			case 'test-email-not-set':
				$message = __( 'Please provide a valid email address to send a test email.', 'lettr' );
				break;
			case 'test-email-sent':
				$message = __( 'Test email sent!', 'lettr' );
				break;
			case 'test-email-failed':
				$message = __( 'Failed to send a test email.', 'lettr' );
				break;
			case 'from-email-invalid':
				$message = __( 'Failed to update sender email address', 'lettr' );
				break;
			case 'from-name-invalid':
				$message = __( 'Failed to update sender name', 'lettr' );
				break;
			case 'settings-updated':
				$message = __( 'Settings updated successfully', 'lettr' );
				break;
			default:
				$message = $type;
		}

		return $message;
	}

	/**
	 * Add help to the Lettr page.
	 */
	public static function admin_help() {
		$current_screen = get_current_screen();

		if ( current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view routing.
			if ( ! Lettr::get_api_key() || ( isset( $_GET['view'] ) && 'start' === $_GET['view'] ) ) {
				// Setup page
				$current_screen->add_help_tab(
					array(
						'id'      => 'overview',
						'title'   => __( 'Overview', 'lettr' ),
						'content' =>
							'<p>' . esc_html__( 'Lettr is the email API for developers. Send transactional emails at scale with reliable delivery.', 'lettr' ) . '</p>' .
							'<p>' . esc_html__( 'On this page, you are able to connect Lettr to your site.', 'lettr' ) . '</p>',
					)
				);

				$current_screen->add_help_tab(
					array(
						'id'      => 'setup-signup',
						'title'   => __( 'New to Lettr', 'lettr' ),
						'content' =>
							'<p>' . esc_html__( 'You need to enter an API key to connect Lettr to your site.', 'lettr' ) . '</p>' .
							/* translators: %s: sign up link */
							'<p>' . sprintf( __( 'Sign up for an account on %s to get an API key.', 'lettr' ), '<a href="https://lettr.com" target="_blank">Lettr.com</a>' ) . '</p>',

					)
				);
			} else {
				// Configuration page
				$current_screen->add_help_tab(
					array(
						'id'      => 'overview',
						'title'   => __( 'Overview', 'lettr' ),
						'content' =>
							'<p>' . esc_html__( 'Lettr is the email API for developers. Send transactional emails at scale with reliable delivery.', 'lettr' ) . '</p>' .
							'<p>' . esc_html__( 'On this page, you are able to update your Lettr settings and view your email history.', 'lettr' ) . '</p>',
					)
				);
			}
		}

		$current_screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'lettr' ) . '</strong></p>' .
			'<p><a href="https://docs.lettr.com" target="_blank">' . esc_html__( 'Lettr Documentation', 'lettr' ) . '</a></p>' .
			'<p><a href="https://lettr.com" target="_blank">' . esc_html__( 'Lettr Website', 'lettr' ) . '</a></p>'
		);
	}

	public static function ajax_enter_api_key() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( self::json_status( 'not-allowed' ) );
		}

		check_admin_referer( self::NONCE );

		$new_key = sanitize_text_field( isset( $_POST['key'] ) ? wp_unslash( $_POST['key'] ) : '' );
		$old_key = Lettr::get_api_key();

		$result = array( false, 'no-change-to-key' );

		if ( empty( $new_key ) ) {
			if ( ! empty( $old_key ) ) {
				delete_option( 'lettr_api_key' );
			}
			$result = array( false, 'new-key-empty' );
		} elseif ( $new_key !== $old_key ) {
			if ( ! Lettr::is_valid_key( $new_key ) ) {
				$result = array( false, 'new-key-invalid' );
			} else {
				$verified = Lettr::verify_key( $new_key );
				if ( true === $verified ) {
					update_option( 'lettr_api_key', $new_key );
					$result = array( true, 'new-key-valid' );
				} elseif ( false === $verified ) {
					$result = array( false, 'new-key-invalid' );
				} else {
					self::add_status( 'lettr-error', $verified->get_error_message() );
					$result = array( false, 'lettr-error' );
				}
			}
		}

		list($is_successful, $status) = $result;

		$is_successful
			? wp_send_json_success( self::json_status( $status ) )
			: wp_send_json_error( self::json_status( $status ) );
	}

	public static function ajax_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( self::json_status( 'not-allowed' ) );
		}

		check_admin_referer( self::NONCE );

		$new_from_email = sanitize_email( isset( $_POST['from_email'] ) ? wp_unslash( $_POST['from_email'] ) : '' );
		$old_from_email = Lettr::get_from_address();

		if ( ! $new_from_email || ! is_email( $new_from_email ) ) {
			wp_send_json_error( self::json_status( 'from-email-invalid' ) );
		}

		$new_from_name = sanitize_text_field( isset( $_POST['from_name'] ) ? wp_unslash( $_POST['from_name'] ) : '' );
		$old_from_name = Lettr::get_from_name();

		if ( ! $new_from_name ) {
			wp_send_json_error( self::json_status( 'from-name-invalid' ) );
		}

		// Update the from email address
		if ( $new_from_email !== $old_from_email ) {
			update_option( 'lettr_from_address', $new_from_email );
		}

		// Update the from name
		if ( $new_from_name !== $old_from_name ) {
			update_option( 'lettr_from_name', $new_from_name );
		}

		wp_send_json_success( self::json_status( 'settings-updated' ) );
	}

	public static function ajax_send_test_email() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( self::json_status( 'not-allowed' ) );
		}

		check_admin_referer( self::NONCE );

		$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
		if ( ! $email || ! is_email( $email ) ) {
			wp_send_json_error( self::json_status( 'test-email-not-set' ) );
		}

		$subject = 'Lettr Test: ' . html_entity_decode( get_bloginfo( 'name' ) );
		$message = 'This is a test email sent using the Lettr plugin.';
		$result  = wp_mail( $email, $subject, $message );

		$result
			? wp_send_json_success( self::json_status( 'test-email-sent' ) )
			: wp_send_json_error( self::json_status( 'test-email-failed' ) );
	}

	public static function plugin_action_links( $links, $file ) {
		if ( plugin_basename( plugin_dir_url( __FILE__ ) . '/lettr.php' ) === $file ) {
			$links[] = '<a href="' . esc_url( self::get_page_url() ) . '">' . esc_html__( 'Settings', 'lettr' ) . '</a>';
		}

		return $links;
	}

	public static function get_page_url( $page = 'config' ) {
		$base_url = admin_url( 'options-general.php' );
		$args     = array( 'page' => 'lettr' );

		if ( 'init' === $page ) {
			$args = array(
				'page' => 'lettr',
				'view' => 'start',
			);
		}

		return add_query_arg( $args, $base_url );
	}

	public static function get_asset_file_version( $relative_path ) {
		$full_path = LETTR__PLUGIN_DIR . $relative_path;

		return LETTR_VERSION;
	}
}
