<?php
/**
 * Characterization tests for Pencil_Fields (imported from Pencil by Rino v0.9.2).
 *
 * @package Quill
 */

use PHPUnit\Framework\TestCase;

final class FieldsTest extends TestCase {

	protected function setUp(): void {
		quill_test_reset();
	}

	public function test_register_rejects_invalid_id_and_returns_default(): void {
		$result = Pencil_Fields::register( 'text', 'Bad ID!', array( 'default' => 'Hello' ) );

		$this->assertSame( '', $result['id'] );
		$this->assertSame( 'Hello', $result['value'] );
		$this->assertNotEmpty( $GLOBALS['_test_doing_it_wrong'] );
	}

	public function test_register_text_field_persists_schema_and_returns_default(): void {
		$result = Pencil_Fields::register( 'text', 'hero.title', array( 'default' => 'Welcome', 'label' => 'Hero title' ) );

		$this->assertSame( 'hero.title', $result['id'] );
		$this->assertSame( 'Welcome', $result['value'] );
		$this->assertSame( 160, $result['schema']['max_length'] );
		$this->assertArrayHasKey( 'hero.title', get_option( 'pencil_field_registry' ) );
	}

	public function test_unknown_type_falls_back_to_text(): void {
		$result = Pencil_Fields::register( 'mystery', 'x.field' );

		$this->assertSame( 'text', $result['schema']['type'] );
	}

	public function test_page_scope_prefixes_id_with_queried_page(): void {
		$GLOBALS['_test_queried_id'] = 42;

		$result = Pencil_Fields::register( 'text', 'intro', array( 'scope' => 'page' ) );

		$this->assertSame( 'page-42.intro', $result['id'] );
	}

	public function test_page_scope_without_page_falls_back_to_site_wide_and_warns(): void {
		$result = Pencil_Fields::register( 'text', 'intro', array( 'scope' => 'page' ) );

		$this->assertSame( 'intro', $result['id'] );
		$this->assertNotEmpty( $GLOBALS['_test_doing_it_wrong'] );
	}

	public function test_save_unknown_field_returns_404_error(): void {
		$result = Pencil_Fields::save( 'never.registered', 'x' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pencil_unknown_field', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_save_text_stores_value_and_current_value_reflects_it(): void {
		Pencil_Fields::register( 'text', 'hero.title', array( 'default' => 'Welcome' ) );

		$saved = Pencil_Fields::save( 'hero.title', '  <b>New</b>   title ' );

		$this->assertSame( 'New title', $saved );
		$this->assertSame( 'New title', Pencil_Fields::get_current_value( 'hero.title' ) );
	}

	public function test_save_text_over_max_length_is_rejected(): void {
		Pencil_Fields::register( 'text', 'short', array( 'max_length' => 5 ) );

		$result = Pencil_Fields::save( 'short', 'too long value' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pencil_value_too_long', $result->get_error_code() );
	}

	public function test_button_requires_text_and_url(): void {
		Pencil_Fields::register( 'button', 'cta' );

		$no_text = Pencil_Fields::save( 'cta', array( 'text' => '', 'url' => '/contact' ) );
		$no_url  = Pencil_Fields::save( 'cta', array( 'text' => 'Go', 'url' => '' ) );

		$this->assertSame( 'pencil_button_text_required', $no_text->get_error_code() );
		$this->assertSame( 'pencil_button_url_required', $no_url->get_error_code() );
	}

	public function test_button_saves_sanitized_text_and_url(): void {
		Pencil_Fields::register( 'button', 'cta' );

		$saved = Pencil_Fields::save( 'cta', array( 'text' => 'Call <i>now</i>', 'url' => '/contact' ) );

		$this->assertSame( array( 'text' => 'Call now', 'url' => '/contact' ), $saved );
	}

	public function test_button_rejects_javascript_url(): void {
		Pencil_Fields::register( 'button', 'cta' );

		$result = Pencil_Fields::save( 'cta', array( 'text' => 'Go', 'url' => 'javascript:alert(1)' ) );

		$this->assertSame( 'pencil_button_url_required', $result->get_error_code() );
	}

	public function test_image_requires_a_real_image_attachment(): void {
		Pencil_Fields::register( 'image', 'hero.image' );

		$bad = Pencil_Fields::save( 'hero.image', 7 );
		$this->assertSame( 'pencil_invalid_image', $bad->get_error_code() );

		$GLOBALS['_test_post_types'][7] = 'attachment';
		$GLOBALS['_test_images'][7]     = true;

		$this->assertSame( 7, Pencil_Fields::save( 'hero.image', '7' ) );
		$this->assertSame( 7, Pencil_Fields::get_current_value( 'hero.image' ) );
	}

	public function test_richtext_strips_disallowed_tags_and_wraps_paragraphs(): void {
		$html = Pencil_Fields::sanitize_richtext( 'Hello <script>alert(1)</script><strong>world</strong>' );

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '<strong>world</strong>', $html );
		$this->assertStringStartsWith( '<p>', $html );
	}

	public function test_richtext_empty_input_returns_empty_string(): void {
		$this->assertSame( '', Pencil_Fields::sanitize_richtext( "  \n " ) );
	}

	public function test_richtext_tags_allowlist_is_restricted(): void {
		$tags = Pencil_Fields::richtext_tags();

		$this->assertSame( array( 'p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'a' ), array_keys( $tags ) );
		$this->assertSame( array( 'href' => true ), $tags['a'] );
	}

	public function test_get_current_value_unknown_field_is_null(): void {
		$this->assertNull( Pencil_Fields::get_current_value( 'nope' ) );
	}

	public function test_types_constant_lists_supported_types(): void {
		$this->assertSame( array( 'text', 'richtext', 'image', 'button' ), Pencil_Fields::TYPES );
	}
}
