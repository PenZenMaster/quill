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
	$GLOBALS['_test_actions']        = array();
	$GLOBALS['_test_routes']         = array();
	$GLOBALS['_test_enqueued']       = array();
	$GLOBALS['_test_filter_overrides'] = array();
	$GLOBALS['_test_user']           = array(
		'logged_in' => false,
		'caps'      => array(),
		'id'        => 0,
		'name'      => '',
	);
	$GLOBALS['wpdb']                 = new Quill_Test_Wpdb();
}

/**
 * Log in a stub user with the given capabilities.
 *
 * @param string[] $caps Capabilities.
 * @return void
 */
function quill_test_login( array $caps = array( 'edit_pages' ) ) {
	$GLOBALS['_test_user'] = array(
		'logged_in' => true,
		'caps'      => $caps,
		'id'        => 5,
		'name'      => 'Edie Editor',
	);
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
	if ( ! preg_match( '#^(https?://|/|\#|mailto:|tel:)#i', $url ) ) {
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

define( 'PENCIL_PLUGIN_PATH', ABSPATH );
define( 'PENCIL_PLUGIN_URL', 'http://example.test/wp-content/plugins/quill/' );

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr__( $text ) {
	return esc_attr( $text );
}

function esc_html_e( $text ) {
	echo esc_html( $text );
}

function esc_attr_e( $text ) {
	echo esc_attr( $text );
}

function sanitize_html_class( $class ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
}

function wp_kses_post( $html ) {
	return wp_kses( $html, Pencil_Fields::richtext_tags() + array( 'span' => array() ) );
}

function wp_json_encode( $data ) {
	return json_encode( $data );
}

function add_action( $hook, $callback, $priority = 10 ) {
	$GLOBALS['_test_actions'][ $hook ][] = array( $callback, $priority );
}

function apply_filters( $hook, $value ) {
	if ( isset( $GLOBALS['_test_filter_overrides'][ $hook ] ) ) {
		return $GLOBALS['_test_filter_overrides'][ $hook ];
	}
	return $value;
}

function is_user_logged_in() {
	return $GLOBALS['_test_user']['logged_in'];
}

function current_user_can( $cap ) {
	return in_array( $cap, $GLOBALS['_test_user']['caps'], true );
}

function get_current_user_id() {
	return $GLOBALS['_test_user']['id'];
}

function wp_get_current_user() {
	return new Quill_Test_User( $GLOBALS['_test_user'] );
}

function current_time( $type, $gmt = 0 ) {
	return '2026-01-01 00:00:00';
}

function wp_get_attachment_image_src( $id, $size = 'full' ) {
	return isset( $GLOBALS['_test_images'][ $id ] ) ? array( 'http://example.test/img-' . $id . '.jpg', 800, 600 ) : false;
}

function wp_get_attachment_image_srcset( $id, $size = 'full' ) {
	return 'srcset-' . $id;
}

function wp_get_attachment_image_sizes( $id, $size = 'full' ) {
	return 'sizes-' . $id;
}

function get_post_meta( $id, $key, $single = false ) {
	return 'Alt ' . $id;
}

/**
 * Stub: renders a predictable <img> tag including the supplied attributes.
 */
function wp_get_attachment_image( $id, $size, $icon, $attrs ) {
	$out = '<img src="http://example.test/img-' . $id . '.jpg"';
	foreach ( $attrs as $name => $value ) {
		$out .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
	return $out . ' />';
}

function register_rest_route( $namespace, $route, $args ) {
	$GLOBALS['_test_routes'][ $namespace . $route ] = $args;
}

function rest_ensure_response( $data ) {
	return $data;
}

function rest_url( $path = '' ) {
	return 'http://example.test/wp-json/' . $path;
}

function wp_create_nonce( $action ) {
	return 'nonce-' . $action;
}

function wp_enqueue_media() {
	$GLOBALS['_test_enqueued']['media'] = true;
}

function wp_enqueue_style( $handle ) {
	$GLOBALS['_test_enqueued']['style'][] = $handle;
}

function wp_enqueue_script( $handle ) {
	$GLOBALS['_test_enqueued']['script'][] = $handle;
}

function wp_localize_script( $handle, $name, $data ) {
	$GLOBALS['_test_enqueued']['localized'][ $name ] = $data;
}

class WP_REST_Server {
	const EDITABLE = 'POST, PUT, PATCH';
}

class WP_REST_Request {
	private $params;

	public function __construct( array $params = array() ) {
		$this->params = $params;
	}

	public function get_param( $key ) {
		return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
	}
}

class Quill_Test_User {
	public $display_name;
	private $id;

	public function __construct( array $data ) {
		$this->id           = $data['id'];
		$this->display_name = $data['name'];
	}

	public function exists() {
		return $this->id > 0;
	}
}

/**
 * Fake $wpdb recording inserts and returning canned query results.
 */
class Quill_Test_Wpdb {
	public $prefix  = 'wp_';
	public $inserts = array();
	public $prepared = array();
	public $rows    = array();
	public $insert_result = 1;

	public function insert( $table, $data, $formats ) {
		$this->inserts[] = array( $table, $data, $formats );
		return $this->insert_result;
	}

	public function prepare( $query, ...$args ) {
		$this->prepared[] = array( $query, $args );
		return $query;
	}

	public function get_results( $query ) {
		return $this->rows;
	}
}

require_once ABSPATH . 'includes/class-pencil-fields.php';
require_once ABSPATH . 'includes/class-pencil-activity.php';
require_once ABSPATH . 'includes/class-pencil-plugin.php';
require_once ABSPATH . 'includes/template-functions.php';
