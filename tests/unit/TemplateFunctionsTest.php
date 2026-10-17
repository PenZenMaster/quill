<?php
/**
 * Characterization tests for the theme-facing template helpers.
 *
 * @package Quill
 */

use PHPUnit\Framework\TestCase;

final class TemplateFunctionsTest extends TestCase {

	protected function setUp(): void {
		quill_test_reset();
	}

	private function capture( callable $fn ): string {
		ob_start();
		$fn();
		return ob_get_clean();
	}

	public function test_text_outputs_escaped_default_for_visitors(): void {
		$html = $this->capture(
			function () {
				pencil_text( 'hero.title', array( 'default' => 'Fish & Chips' ) );
			}
		);

		$this->assertSame( 'Fish &amp; Chips', $html );
	}

	public function test_text_wraps_in_editable_span_for_editors(): void {
		quill_test_login();

		$html = $this->capture(
			function () {
				pencil_text( 'hero.title', array( 'default' => 'Hi', 'label' => 'Title' ) );
			}
		);

		$this->assertStringContainsString( '<span class="pencil-field"', $html );
		$this->assertStringContainsString( 'data-pencil-field="hero.title"', $html );
		$this->assertStringContainsString( 'data-pencil-label="Title"', $html );
		$this->assertStringContainsString( 'data-pencil-max-length="160"', $html );
		$this->assertStringContainsString( 'tabindex="-1"', $html );
	}

	public function test_text_with_invalid_id_renders_default_without_edit_attributes(): void {
		quill_test_login();

		$html = $this->capture(
			function () {
				pencil_text( 'BAD ID', array( 'default' => 'Safe' ) );
			}
		);

		$this->assertSame( 'Safe', $html );
	}

	public function test_richtext_always_wrapped_in_pencil_richtext_div(): void {
		$html = $this->capture(
			function () {
				pencil_richtext( 'intro', array( 'default' => 'Hello <strong>you</strong>', 'class' => 'lead big' ) );
			}
		);

		$this->assertStringStartsWith( '<div class="pencil-richtext lead big">', $html );
		$this->assertStringContainsString( '<strong>you</strong>', $html );
		$this->assertStringNotContainsString( 'data-pencil-field', $html );
	}

	public function test_richtext_adds_edit_attributes_for_editors(): void {
		quill_test_login();

		$html = $this->capture(
			function () {
				pencil_richtext( 'intro', array( 'default' => 'Hello' ) );
			}
		);

		$this->assertStringContainsString( 'pencil-richtext pencil-field', $html );
		$this->assertStringContainsString( 'data-pencil-type="richtext"', $html );
	}

	public function test_button_renders_nothing_when_empty_for_visitors(): void {
		$html = $this->capture(
			function () {
				pencil_button( 'cta' );
			}
		);

		$this->assertSame( '', $html );
	}

	public function test_button_renders_link_with_default_text_and_url(): void {
		$html = $this->capture(
			function () {
				pencil_button( 'cta', array( 'default' => array( 'text' => 'Call us', 'url' => '/contact' ), 'class' => 'btn' ) );
			}
		);

		$this->assertSame( '<a class="btn" href="/contact">Call us</a>', $html );
	}

	public function test_button_blank_target_adds_noopener(): void {
		$html = $this->capture(
			function () {
				pencil_button( 'cta', array( 'default' => array( 'text' => 'Go', 'url' => 'https://x.test' ), 'target' => '_blank' ) );
			}
		);

		$this->assertStringContainsString( ' target="_blank" rel="noopener"', $html );
	}

	public function test_button_before_after_wraps_text_in_span(): void {
		$html = $this->capture(
			function () {
				pencil_button( 'cta', array( 'default' => array( 'text' => 'Go', 'url' => '/x' ), 'before' => '<i>*</i>' ) );
			}
		);

		$this->assertStringContainsString( '<span class="pencil-button__text">Go</span>', $html );
	}

	public function test_button_empty_shows_for_editors_with_hash_href(): void {
		quill_test_login();

		$html = $this->capture(
			function () {
				pencil_button( 'cta' );
			}
		);

		$this->assertStringContainsString( 'href="#"', $html );
		$this->assertStringContainsString( 'data-pencil-type="button"', $html );
	}

	public function test_image_renders_nothing_without_valid_attachment(): void {
		$html = $this->capture(
			function () {
				pencil_image( 'hero.image', array( 'default' => 99 ) );
			}
		);

		$this->assertSame( '', $html );
	}

	public function test_image_outputs_attachment_with_style_for_visitors(): void {
		$GLOBALS['_test_images'][7] = true;

		$html = $this->capture(
			function () {
				pencil_image( 'hero.image', array( 'default' => 7, 'aspect_ratio' => '16/9', 'class' => 'hero', 'loading' => 'lazy' ) );
			}
		);

		$this->assertStringContainsString( 'class="hero"', $html );
		$this->assertStringContainsString( 'style="aspect-ratio:16 / 9;object-fit:cover"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringNotContainsString( 'data-pencil-field', $html );
	}

	public function test_image_adds_edit_attributes_for_editors(): void {
		quill_test_login();
		$GLOBALS['_test_images'][7] = true;

		$html = $this->capture(
			function () {
				pencil_image( 'hero.image', array( 'default' => 7 ) );
			}
		);

		$this->assertStringContainsString( 'pencil-field pencil-field--image', $html );
		$this->assertStringContainsString( 'data-pencil-attachment-id="7"', $html );
	}

	public function test_class_list_sanitizes_dedupes_and_prepends_extras(): void {
		$this->assertSame(
			'base a bscript',
			pencil_internal_class_list( array( 'class' => 'a  b<script> a' ), array( 'base' ) )
		);
	}

	public function test_image_style_handles_ratio_fit_and_position(): void {
		$this->assertSame( '', pencil_internal_image_style( array() ) );
		$this->assertSame(
			'aspect-ratio:4 / 3;object-fit:contain;object-position:top left',
			pencil_internal_image_style(
				array(
					'aspect_ratio'    => '4:3',
					'object_fit'      => 'contain',
					'object_position' => 'top left',
				)
			)
		);
		$this->assertSame( 'aspect-ratio:2;object-fit:cover', pencil_internal_image_style( array( 'aspect_ratio' => '2' ) ) );
	}

	public function test_image_style_rejects_unsafe_values(): void {
		$style = pencil_internal_image_style(
			array(
				'aspect_ratio'    => 'url(evil)',
				'object_fit'      => 'bogus',
				'object_position' => 'red;background:url(x)',
			)
		);

		$this->assertSame( '', $style );
	}

	public function test_managed_region_hides_edit_metadata_from_visitors(): void {
		$html = $this->capture(
			function () {
				pencil_managed_region_open( array( 'provider' => 'acf', 'class' => 'box' ) );
				pencil_managed_region_close();
			}
		);

		$this->assertStringContainsString( 'class="pencil-managed-region box"', $html );
		$this->assertStringNotContainsString( 'data-pencil-managed', $html );
		$this->assertStringEndsWith( '</div>', $html );
	}

	public function test_managed_region_shows_provider_label_and_edit_url_for_editors(): void {
		quill_test_login();

		$html = $this->capture(
			function () {
				pencil_managed_region_open( array( 'provider' => 'acf', 'edit_url' => 'https://x.test/edit' ) );
			}
		);

		$this->assertStringContainsString( 'data-pencil-managed="acf"', $html );
		$this->assertStringContainsString( 'data-pencil-managed-label="Editable in ACF"', $html );
		$this->assertStringContainsString( 'data-pencil-managed-edit-url="https://x.test/edit"', $html );
	}
}
