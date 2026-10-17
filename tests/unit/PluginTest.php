<?php
/**
 * Characterization tests for Quill_Plugin.
 *
 * @package Quill
 */

use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase {

	protected function setUp(): void {
		quill_test_reset();
	}

	public function test_init_registers_rest_enqueue_and_footer_hooks(): void {
		Quill_Plugin::init();

		$this->assertArrayHasKey( 'rest_api_init', $GLOBALS['_test_actions'] );
		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['_test_actions'] );
		$this->assertSame( 5, $GLOBALS['_test_actions']['wp_footer'][0][1] );
	}

	public function test_visitors_cannot_edit(): void {
		$this->assertFalse( Quill_Plugin::can_edit() );
	}

	public function test_logged_in_user_without_edit_pages_cannot_edit(): void {
		quill_test_login( array( 'edit_posts' ) );

		$this->assertFalse( Quill_Plugin::can_edit() );
	}

	public function test_user_with_edit_pages_can_edit(): void {
		quill_test_login( array( 'edit_pages' ) );

		$this->assertTrue( Quill_Plugin::can_edit() );
	}

	public function test_can_edit_is_filterable(): void {
		$GLOBALS['_test_filter_overrides']['quill_can_edit'] = true;

		$this->assertTrue( Quill_Plugin::can_edit() );
	}

	public function test_rest_route_requires_edit_permission_and_accepts_id_pattern(): void {
		Quill_Plugin::register_rest_routes();

		$route = $GLOBALS['_test_routes']['quill/v1/fields/(?P<id>[a-z0-9._-]+)'];
		$this->assertSame( array( 'Quill_Plugin', 'can_edit' ), $route['permission_callback'] );
		$this->assertSame( array( 'Quill_Plugin', 'save_field' ), $route['callback'] );
		$this->assertTrue( $route['args']['value']['required'] );
		$this->assertSame( 'absint', $route['args']['page_id']['sanitize_callback'] );
	}

	public function test_save_field_unknown_id_returns_404(): void {
		$result = Quill_Plugin::save_field( new WP_REST_Request( array( 'id' => 'missing', 'value' => 'x' ) ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_save_field_validation_error_is_returned_and_not_logged(): void {
		Quill_Fields::register( 'text', 'short', array( 'max_length' => 3 ) );

		$result = Quill_Plugin::save_field( new WP_REST_Request( array( 'id' => 'short', 'value' => 'toolong' ) ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array(), $GLOBALS['wpdb']->inserts );
	}

	public function test_save_field_saves_logs_and_returns_payload(): void {
		quill_test_login();
		Quill_Fields::register( 'text', 'hero.title', array( 'default' => 'Welcome' ) );

		$result = Quill_Plugin::save_field(
			new WP_REST_Request( array( 'id' => 'hero.title', 'value' => 'Hi there', 'page_id' => '9' ) )
		);

		$this->assertSame( array( 'id' => 'hero.title', 'type' => 'text', 'value' => 'Hi there' ), $result );
		$data = $GLOBALS['wpdb']->inserts[0][1];
		$this->assertSame( 'Welcome', $data['old_value'] );
		$this->assertSame( 'Hi there', $data['new_value'] );
		$this->assertSame( 9, $data['page_id'] );
	}

	public function test_save_field_image_response_includes_image_data(): void {
		Quill_Fields::register( 'image', 'hero.image', array( 'size' => 'large' ) );
		$GLOBALS['_test_post_types'][7] = 'attachment';
		$GLOBALS['_test_images'][7]     = true;

		$result = Quill_Plugin::save_field( new WP_REST_Request( array( 'id' => 'hero.image', 'value' => 7 ) ) );

		$this->assertSame( 7, $result['value'] );
		$this->assertSame( 7, $result['image']['id'] );
		$this->assertSame( 'http://example.test/img-7.jpg', $result['image']['url'] );
		$this->assertSame( 'Alt 7', $result['image']['alt'] );
	}

	public function test_enqueue_does_nothing_for_visitors(): void {
		Quill_Plugin::enqueue_editor_assets();

		$this->assertSame( array(), $GLOBALS['_test_enqueued'] );
	}

	public function test_enqueue_loads_assets_and_localizes_for_editors(): void {
		quill_test_login();

		Quill_Plugin::enqueue_editor_assets();

		$this->assertSame( array( 'quill-editor' ), $GLOBALS['_test_enqueued']['script'] );
		$this->assertSame( array( 'quill-editor' ), $GLOBALS['_test_enqueued']['style'] );
		$localized = $GLOBALS['_test_enqueued']['localized']['quillEditor'];
		$this->assertSame( 'nonce-wp_rest', $localized['nonce'] );
		$this->assertStringEndsWith( 'quill/v1/fields/', $localized['restUrl'] );
	}

	public function test_render_editor_outputs_nothing_for_visitors(): void {
		ob_start();
		Quill_Plugin::render_editor();

		$this->assertSame( '', ob_get_clean() );
	}

	public function test_render_editor_outputs_ui_for_editors(): void {
		quill_test_login();

		ob_start();
		Quill_Plugin::render_editor();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'data-quill-toolbar', $html );
		$this->assertStringContainsString( 'data-quill-popup', $html );
	}
}
