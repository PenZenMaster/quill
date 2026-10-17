<?php
/**
 * Plugin bootstrap and editor interface.
 *
 * @package Quill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend editor bootstrap and REST save endpoint.
 */
final class Quill_Plugin {
	/**
	 * Register plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_editor' ), 5 );
	}

	/**
	 * Whether the current visitor may use Quill.
	 *
	 * Administrators and Editors by default. Contributors and Authors have
	 * `edit_posts` but not `edit_pages`, so they are left out.
	 *
	 * @return bool
	 */
	public static function can_edit() {
		$can_edit = is_user_logged_in() && current_user_can( 'edit_pages' );

		/**
		 * Filter who may open Quill and save content.
		 *
		 * @param bool $can_edit Whether the current user may edit.
		 */
		return (bool) apply_filters( 'quill_can_edit', $can_edit );
	}

	/**
	 * Register save endpoints.
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'quill/v1',
			'/fields/(?P<id>[a-z0-9._-]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'save_field' ),
				'permission_callback' => array( __CLASS__, 'can_edit' ),
				'args'                => array(
					'id'      => array(
						'required' => true,
					),
					'value'   => array(
						'required' => true,
					),
					'page_id' => array(
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
				),
			)
		);
	}

	/**
	 * Save an editable field.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_field( WP_REST_Request $request ) {
		$id     = (string) $request->get_param( 'id' );
		$schema = Quill_Fields::get_schema( $id );

		if ( ! $schema ) {
			return new WP_Error(
				'quill_unknown_field',
				__( 'This content field is no longer available.', 'quill' ),
				array( 'status' => 404 )
			);
		}

		$old_value = Quill_Fields::get_current_value( $id );
		$value     = Quill_Fields::save( $id, $request->get_param( 'value' ) );

		if ( is_wp_error( $value ) ) {
			return $value;
		}

		Quill_Activity::record(
			$id,
			$schema,
			$old_value,
			$value,
			absint( $request->get_param( 'page_id' ) )
		);

		$response = array(
			'id'    => $id,
			'type'  => $schema['type'],
			'value' => $value,
		);

		if ( 'image' === $schema['type'] ) {
			$response['image'] = Quill_Fields::get_image_data(
				$value,
				isset( $schema['size'] ) ? $schema['size'] : 'full'
			);
		}

		return rest_ensure_response(
			$response
		);
	}

	/**
	 * Load editor-only assets for authorised users.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets() {
		if ( ! self::can_edit() ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'quill-editor',
			QUILL_PLUGIN_URL . 'assets/css/editor.css',
			array( 'dashicons' ),
			self::asset_version( 'assets/css/editor.css' )
		);

		wp_enqueue_script(
			'quill-editor',
			QUILL_PLUGIN_URL . 'assets/js/editor.js',
			array(),
			self::asset_version( 'assets/js/editor.js' ),
			true
		);

		wp_localize_script(
			'quill-editor',
			'quillEditor',
			array(
				'restUrl' => esc_url_raw( rest_url( 'quill/v1/fields/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'pageId'  => get_queried_object_id(),
				'labels'  => array(
					'openQuill'   => __( 'Open Quill', 'quill' ),
					'closeQuill'  => __( 'Close Quill', 'quill' ),
					'saved'       => __( 'Saved', 'quill' ),
					'error'       => __( 'Something went wrong. Please try again.', 'quill' ),
					'chooseImage' => __( 'Choose image', 'quill' ),
					'useImage'    => __( 'Use image', 'quill' ),
					'selectText'  => __( 'Select the text you want to turn into a link first.', 'quill' ),
				),
			)
		);
	}

	/**
	 * Render the minimal editor interface.
	 *
	 * @return void
	 */
	public static function render_editor() {
		if ( ! self::can_edit() ) {
			return;
		}
		?>
		<div class="quill-ui quill-toolbar" data-quill-toolbar hidden>
			<button class="quill-toolbar__select" type="button" data-quill-toggle aria-pressed="false">
				<span data-quill-toggle-label><?php esc_html_e( 'Open Quill', 'quill' ); ?></span>
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
			</button>
		</div>

		<div class="quill-ui quill-status" data-quill-status role="status" aria-live="polite" hidden></div>

		<div class="quill-ui quill-highlight" data-quill-highlight hidden>
			<div class="quill-highlight__label">
				<span class="quill-highlight__text" data-quill-highlight-label></span>
				<a
					class="quill-highlight__action"
					data-quill-managed-edit
					href="#"
					target="_blank"
					rel="noopener noreferrer"
					hidden
				></a>
			</div>
		</div>

		<div
			class="quill-ui quill-popup"
			data-quill-popup
			role="dialog"
			aria-labelledby="quill-popup-title"
			hidden
		>
			<form data-quill-form>
				<div class="quill-popup__label" id="quill-popup-title" data-quill-popup-label></div>
				<div data-quill-text-editor>
					<textarea class="quill-popup__input" id="quill-popup-value" name="value" rows="1" wrap="soft" autocomplete="off" aria-labelledby="quill-popup-title" data-quill-input></textarea>
				</div>
				<div class="quill-popup__richtext-editor" data-quill-richtext-editor hidden>
					<div class="quill-popup__toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Formatting', 'quill' ); ?>">
						<?php
						$tools = array(
							'bold'                => array( 'editor-bold', __( 'Bold', 'quill' ) ),
							'italic'              => array( 'editor-italic', __( 'Italic', 'quill' ) ),
							'link'                => array( 'admin-links', __( 'Link', 'quill' ) ),
							'insertUnorderedList' => array( 'editor-ul', __( 'Bulleted list', 'quill' ) ),
							'insertOrderedList'   => array( 'editor-ol', __( 'Numbered list', 'quill' ) ),
						);

						foreach ( $tools as $command => $tool ) :
							?>
							<button class="quill-popup__tool" type="button" data-quill-command="<?php echo esc_attr( $command ); ?>" aria-label="<?php echo esc_attr( $tool[1] ); ?>" title="<?php echo esc_attr( $tool[1] ); ?>" aria-pressed="false">
								<span class="dashicons dashicons-<?php echo esc_attr( $tool[0] ); ?>" aria-hidden="true"></span>
							</button>
						<?php endforeach; ?>
					</div>
					<div class="quill-popup__richtext" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="quill-popup-title" data-quill-richtext-input></div>
					<div class="quill-popup__link" data-quill-link-row hidden>
						<input class="quill-popup__input" type="text" autocomplete="off" inputmode="url" placeholder="<?php esc_attr_e( 'https://example.com or /contact', 'quill' ); ?>" aria-label="<?php esc_attr_e( 'Link address', 'quill' ); ?>" data-quill-link-input>
						<button class="quill-popup__button quill-popup__button--quiet" type="button" data-quill-link-apply>
							<?php esc_html_e( 'Apply', 'quill' ); ?>
						</button>
					</div>
				</div>
				<div class="quill-popup__button-editor" data-quill-button-editor hidden>
					<label class="quill-popup__field">
						<span class="quill-popup__field-label"><?php esc_html_e( 'Text', 'quill' ); ?></span>
						<input class="quill-popup__input" type="text" autocomplete="off" data-quill-button-text>
					</label>
					<label class="quill-popup__field">
						<span class="quill-popup__field-label"><?php esc_html_e( 'Link', 'quill' ); ?></span>
						<input class="quill-popup__input" type="text" autocomplete="off" inputmode="url" placeholder="<?php esc_attr_e( 'https://example.com or /contact', 'quill' ); ?>" data-quill-button-url>
					</label>
				</div>
				<div class="quill-popup__image-editor" data-quill-image-editor hidden>
					<div class="quill-popup__image-preview">
						<img src="" alt="" data-quill-image-preview>
					</div>
					<button class="quill-popup__replace" type="button" data-quill-replace-image>
						<?php esc_html_e( 'Replace image', 'quill' ); ?>
					</button>
				</div>
				<p class="quill-popup__error" data-quill-error hidden></p>
				<div class="quill-popup__actions">
					<button class="quill-popup__button quill-popup__button--quiet" type="button" data-quill-cancel>
						<?php esc_html_e( 'Cancel', 'quill' ); ?>
					</button>
					<button class="quill-popup__button quill-popup__button--primary" type="submit" data-quill-save>
						<?php esc_html_e( 'Save', 'quill' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Return a cache-busting asset version.
	 *
	 * @param string $relative_path Plugin-relative path.
	 * @return string
	 */
	private static function asset_version( $relative_path ) {
		$path = QUILL_PLUGIN_PATH . $relative_path;

		return file_exists( $path ) ? (string) filemtime( $path ) : QUILL_VERSION;
	}
}
