<?php
/**
 * PHPUnit bootstrap.
 *
 * Defines minimal in-memory WordPress stubs so the plugin classes can be
 * unit tested outside a real WordPress install. Stubs record state in
 * $GLOBALS['_test_*'] arrays; call quill_test_reset() in setUp().
 *
 * @package Quill
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'PENCIL_VERSION', '0.9.2' );

/**
 * Reset all stub state between tests.
 *
 * @return void
 */
function quill_test_reset() {
	$GLOBALS['_test_options']        = array();
	$GLOBALS['_test_doing_it_wrong'] = array();
	$GLOBALS['_test_queried_id']     = 0;
	$GLOBALS['_test_post_types']     = array();
	$GLOBALS['_test_images']         = array();
}

quill_test_reset();

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function __( $text ) {
	return $text;
}

function esc_html__( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_text_field( $value ) {
	return trim( preg_replace( '/\s+/', ' ', strip_tags( (string) $value ) ) );
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function esc_url_raw( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || preg_match( '/^\s*(javascript|data):/i', $url ) ) {
		return '';
	}
	if ( ! preg_match( '#^(https?://|/|mailto:|tel:)#i', $url ) ) {
		$url = 'http://' . $url;
	}
	return $url;
}

function esc_url( $url ) {
	return esc_url_raw( $url );
}

function wp_specialchars_decode( $text ) {
	return htmlspecialchars_decode( $text, ENT_QUOTES );
}

function wp_strip_all_tags( $text ) {
	return trim( strip_tags( (string) $text ) );
}

/**
 * Simplified wp_kses: keeps only allowed tags and drops attributes not allowed.
 */
function wp_kses( $html, $allowed ) {
	$tags = '<' . implode( '><', array_keys( $allowed ) ) . '>';
	$html = strip_tags( (string) $html, $tags );
	return preg_replace_callback(
		'/<(\w+)([^>]*)>/',
		function ( $m ) use ( $allowed ) {
			$tag = strtolower( $m[1] );
			$out = '';
			if ( ! empty( $allowed[ $tag ] ) && preg_match( '/href="[^"]*"/i', $m[2], $a ) ) {
				$out = ' ' . $a[0];
			}
			return '<' . $tag . $out . '>';
		},
		$html
	);
}

/**
 * Simplified wpautop: wraps bare text blocks in paragraphs.
 */
function wpautop( $text ) {
	if ( preg_match( '/^\s*<(p|ul|ol)[\s>]/i', $text ) ) {
		return $text;
	}
	return '<p>' . $text . '</p>';
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['_test_options'] ) ? $GLOBALS['_test_options'][ $name ] : $default;
}

function add_option( $name, $value ) {
	$GLOBALS['_test_options'][ $name ] = $value;
	return true;
}

function update_option( $name, $value ) {
	$GLOBALS['_test_options'][ $name ] = $value;
	return true;
}

function get_queried_object_id() {
	return $GLOBALS['_test_queried_id'];
}

function _doing_it_wrong( $function_name, $message, $version ) {
	$GLOBALS['_test_doing_it_wrong'][] = array( $function_name, $message, $version );
}

function get_post_type( $id ) {
	return isset( $GLOBALS['_test_post_types'][ $id ] ) ? $GLOBALS['_test_post_types'][ $id ] : false;
}

function wp_attachment_is_image( $id ) {
	return ! empty( $GLOBALS['_test_images'][ $id ] );
}

require_once ABSPATH . 'includes/class-pencil-fields.php';
