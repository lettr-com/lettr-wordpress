<?php

/**
 * @package Lettr
 */
/**
 * @wordpress-plugin
 * Plugin Name: Lettr
 * Plugin URI: https://lettr.com
 * Description: The email API for developers. Send transactional emails at scale with reliable delivery.
 * Requires at least: 5.8
 * Version: 1.0.0
 * Requires PHP: 7.2
 * Author: Lettr
 * Author URI: https://lettr.com
 * License: GPL-2.0-or-later
 * Text Domain: lettr
 */

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'LETTR_VERSION', '1.0.0' );
define( 'LETTR__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

if ( function_exists( 'wp_mail' ) ) {
	function wp_mail_already_declared_notice() {
		$class   = 'notice notice-error';
		$message = __( 'Lettr is active, but something else is blocking it from sending emails. Another plugin or custom code is taking over email handling (wp_mail). To use Lettr, you\'ll need to disable the conflict.', 'lettr' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	add_action( 'admin_notices', 'wp_mail_already_declared_notice' );
}

register_activation_hook( __FILE__, array( 'Lettr', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Lettr', 'plugin_deactivation' ) );

require_once LETTR__PLUGIN_DIR . 'class-lettr.php';

add_action( 'init', array( 'Lettr', 'init' ) );

if ( is_admin() ) {
	require_once LETTR__PLUGIN_DIR . 'class-lettr-admin.php';
	add_action( 'init', array( 'Lettr_Admin', 'init' ) );
}

if ( ! function_exists( 'wp_mail' ) ) {
	include LETTR__PLUGIN_DIR . 'wp-mail.php';
}
