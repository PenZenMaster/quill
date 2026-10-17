<?php
/**
 * Characterization tests for Quill_Activity.
 *
 * @package Quill
 */

use PHPUnit\Framework\TestCase;

final class ActivityTest extends TestCase {

	protected function setUp(): void {
		quill_test_reset();
	}

	public function test_init_hooks_maybe_install_on_init_at_priority_5(): void {
		Quill_Activity::init();

		$this->assertSame(
			array( array( array( 'Quill_Activity', 'maybe_install' ), 5 ) ),
			$GLOBALS['_test_actions']['init']
		);
	}

	public function test_maybe_install_skips_when_db_version_current(): void {
		update_option( 'quill_activity_db_version', Quill_Activity::DB_VERSION );

		// install() would require wp-admin/includes/upgrade.php, which does not exist here.
		Quill_Activity::maybe_install();

		$this->assertSame( '1', get_option( 'quill_activity_db_version' ) );
	}

	public function test_record_returns_false_when_value_unchanged(): void {
		$result = Quill_Activity::record( 'hero.title', array( 'type' => 'text' ), 'Same', 'Same', 3 );

		$this->assertFalse( $result );
		$this->assertSame( array(), $GLOBALS['wpdb']->inserts );
	}

	public function test_record_inserts_row_with_user_and_page(): void {
		quill_test_login();

		$result = Quill_Activity::record(
			'hero.title',
			array( 'type' => 'text', 'label' => 'Hero title' ),
			'Old',
			'New',
			12
		);

		$this->assertTrue( $result );
		list( $table, $data, $formats ) = $GLOBALS['wpdb']->inserts[0];
		$this->assertSame( 'wp_quill_activity', $table );
		$this->assertSame( 'hero.title', $data['field_id'] );
		$this->assertSame( 'Hero title', $data['field_label'] );
		$this->assertSame( 'text', $data['field_type'] );
		$this->assertSame( 'Old', $data['old_value'] );
		$this->assertSame( 'New', $data['new_value'] );
		$this->assertSame( 5, $data['user_id'] );
		$this->assertSame( 'Edie Editor', $data['user_name'] );
		$this->assertSame( 12, $data['page_id'] );
		$this->assertCount( count( $data ), $formats );
	}

	public function test_record_label_falls_back_to_field_id(): void {
		Quill_Activity::record( 'x.y', array( 'type' => 'text' ), 'a', 'b' );

		$this->assertSame( 'x.y', $GLOBALS['wpdb']->inserts[0][1]['field_label'] );
	}

	public function test_record_returns_false_when_insert_fails(): void {
		$GLOBALS['wpdb']->insert_result = false;

		$this->assertFalse( Quill_Activity::record( 'a', array( 'type' => 'text' ), 'x', 'y' ) );
	}

	public function test_record_normalises_button_values_to_json(): void {
		Quill_Activity::record(
			'cta',
			array( 'type' => 'button' ),
			array( 'text' => 'Old', 'url' => '/a' ),
			array( 'text' => 'New', 'url' => '/b' )
		);

		$data = $GLOBALS['wpdb']->inserts[0][1];
		$this->assertSame( '{"text":"Old","url":"\/a"}', $data['old_value'] );
		$this->assertSame( '{"text":"New","url":"\/b"}', $data['new_value'] );
	}

	public function test_record_normalises_image_values_to_attachment_id_strings(): void {
		Quill_Activity::record( 'img', array( 'type' => 'image' ), null, '7' );

		$data = $GLOBALS['wpdb']->inserts[0][1];
		$this->assertSame( '0', $data['old_value'] );
		$this->assertSame( '7', $data['new_value'] );
	}

	public function test_record_anonymous_user_has_empty_name(): void {
		Quill_Activity::record( 'a', array( 'type' => 'text' ), 'x', 'y' );

		$this->assertSame( '', $GLOBALS['wpdb']->inserts[0][1]['user_name'] );
	}

	public function test_get_recent_clamps_limit_between_1_and_200(): void {
		Quill_Activity::get_recent( 5000 );
		Quill_Activity::get_recent( 0 );
		Quill_Activity::get_recent( 50 );

		$limits = array_map(
			function ( $call ) {
				return $call[1][1];
			},
			$GLOBALS['wpdb']->prepared
		);
		$this->assertSame( array( 200, 1, 50 ), $limits );
	}

	public function test_get_recent_uses_prepared_query_on_activity_table(): void {
		$GLOBALS['wpdb']->rows = array( (object) array( 'activity_id' => 1 ) );

		$rows = Quill_Activity::get_recent();

		$this->assertCount( 1, $rows );
		$this->assertSame( 'wp_quill_activity', $GLOBALS['wpdb']->prepared[0][1][0] );
		$this->assertStringContainsString( 'ORDER BY created_at DESC', $GLOBALS['wpdb']->prepared[0][0] );
	}
}
