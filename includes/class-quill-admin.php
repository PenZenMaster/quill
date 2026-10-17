<?php
/**
 * Quill admin page: Changes, Get started, About, Changelog, Support.
 *
 * @package Quill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Quill admin screens and assets.
 */
final class Quill_Admin {
	const MENU_SLUG = 'quill';

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private static $page_hook = '';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_admin_bar_shortcut' ), 80 );
	}

	/**
	 * Add the Quill top-level menu.
	 *
	 * The icon is passed as a data URI so WordPress recolours it to match the
	 * user's admin colour scheme.
	 *
	 * @return void
	 */
	public static function register_menu() {
		$svg  = file_get_contents( QUILL_PLUGIN_PATH . 'admin/img/quill-icon-white.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$icon = 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		self::$page_hook = add_menu_page(
			__( 'Quill', 'quill' ),
			__( 'Quill', 'quill' ),
			'edit_pages',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			$icon,
			58
		);
	}

	/**
	 * Add an "Open Quill" shortcut to the admin bar while viewing the site.
	 *
	 * @param WP_Admin_Bar $admin_bar WordPress admin bar instance.
	 * @return void
	 */
	public static function register_admin_bar_shortcut( $admin_bar ) {
		if ( is_admin() || ! Quill_Plugin::can_edit() ) {
			return;
		}

		$current_url = home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) );
		$editor_url  = add_query_arg( 'quill-edit', '1', $current_url );

		$admin_bar->add_node(
			array(
				'id'    => 'quill-open-editor',
				'title' => __( 'Open Quill', 'quill' ),
				'href'  => esc_url( $editor_url ),
				'meta'  => array(
					'class' => 'quill-admin-bar-shortcut',
				),
			)
		);
	}

	/**
	 * Load styles and scripts only on the Quill admin page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( self::$page_hook !== $hook_suffix ) {
			return;
		}

		$css_path = QUILL_PLUGIN_PATH . 'admin/css/admin.css';
		$js_path  = QUILL_PLUGIN_PATH . 'admin/js/admin.js';

		wp_enqueue_style(
			'quill-admin',
			QUILL_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : QUILL_VERSION
		);
		wp_enqueue_script(
			'quill-admin',
			QUILL_PLUGIN_URL . 'admin/js/admin.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : QUILL_VERSION,
			true
		);
	}

	/**
	 * Render the page. Every tab is in the DOM; JS switches between them.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'quill' ) );
		}

		$tabs = array(
			'changes'     => __( 'History', 'quill' ),
			'get-started' => __( 'Get started', 'quill' ),
			'about'       => __( 'About', 'quill' ),
			'changelog'   => __( 'Release notes', 'quill' ),
		);
		?>
		<div class="quill-wrap">

			<header class="quill-header">
				<div class="quill-header__left">
					<img src="<?php echo esc_url( QUILL_PLUGIN_URL . 'admin/img/quill-icon-color.svg' ); ?>" alt="" aria-hidden="true" class="quill-header__icon" width="22" height="22">
					<h1 class="quill-header__title"><?php esc_html_e( 'Quill', 'quill' ); ?></h1>
					<span class="quill-header__version">v<?php echo esc_html( QUILL_VERSION ); ?></span>
				</div>
				<nav class="quill-header__nav">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<a href="#<?php echo esc_attr( $slug ); ?>" data-tab="<?php echo esc_attr( $slug ); ?>" class="quill-header__link">
							<?php echo esc_html( $label ); ?>
						</a>
					<?php endforeach; ?>
					<a class="quill-header__open" href="<?php echo esc_url( add_query_arg( 'quill-edit', '1', home_url( '/' ) ) ); ?>">
						<?php esc_html_e( 'Open Quill', 'quill' ); ?>
					</a>
				</nav>
			</header>

			<div data-tab-panel="changes"><?php self::render_changes(); ?></div>
			<div data-tab-panel="get-started" hidden><?php self::render_get_started(); ?></div>
			<div data-tab-panel="about" hidden><?php self::render_about(); ?></div>
			<div data-tab-panel="changelog" hidden><?php self::render_changelog(); ?></div>

		</div>
		<?php
	}

	/**
	 * Changes tab: open button and the activity log.
	 *
	 * @return void
	 */
	private static function render_changes() {
		$activities = Quill_Activity::get_recent( 100 );
		?>
		<div class="quill-content quill-content--onboarding">
			<div class="quill-card quill-card--full quill-changes">
				<div class="quill-page-intro">
					<span class="quill-eyebrow"><?php esc_html_e( 'Changelog', 'quill' ); ?></span>
					<h2><?php esc_html_e( 'History', 'quill' ); ?></h2>
				</div>

				<?php if ( empty( $activities ) ) : ?>
					<div class="quill-admin__empty">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<h3><?php esc_html_e( 'No changes yet', 'quill' ); ?></h3>
						<p><?php esc_html_e( 'Every change saved through Quill appears here, with the value before and after.', 'quill' ); ?></p>
					</div>
				<?php else : ?>
					<ul class="quill-admin__list">
						<?php foreach ( $activities as $activity ) : ?>
							<li class="quill-admin__record">
								<?php self::render_activity_row( $activity ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Get started tab: one part for people, one for the AI agent.
	 *
	 * @return void
	 */
	private static function render_get_started() {
		$instructions_file = QUILL_PLUGIN_PATH . 'docs/agent-instructions.md';
		$instructions      = file_exists( $instructions_file ) ? file_get_contents( $instructions_file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$prompt_intro      = __( 'We are going to build a custom WordPress theme that works with the Quill plugin. Quill has specific theme-building instructions that you must follow throughout the project. Find and read the complete instructions in WordPress admin under Quill > Get Started > For AI Agents, or locate docs/agent-instructions.md inside the active Quill plugin. If your environment cannot access them, ask me to copy them into this conversation.', 'quill' );
		$idea_prompt       = implode(
			"\n\n",
			array(
				$prompt_intro,
				__( 'Generate the website design and code inside the theme according to the prompt I provide. Build the first complete version and follow the Quill instructions throughout the project.', 'quill' ),
				__( 'Website link: [Insert link]', 'quill' ),
				__( 'Website idea or design brief:', 'quill' ),
			)
		);
		$html_prompt       = implode(
			"\n\n",
			array(
				$prompt_intro,
				__( 'I am providing an HTML template. Start with the complete template package I provide and convert it into a maintainable custom WordPress theme by following the Quill instructions.', 'quill' ),
				__( 'Website link: [Insert link]', 'quill' ),
				__( 'HTML template location or files:', 'quill' ),
			)
		);
		?>
		<div class="quill-content quill-content--onboarding">
			<div class="quill-card quill-card--full">
				<div data-subtabs>
					<div class="quill-subtabs" role="tablist">
						<button type="button" class="quill-subtabs__btn quill-subtabs__btn--active" data-subtab="humans" role="tab" aria-selected="true"><?php esc_html_e( 'For you', 'quill' ); ?></button>
						<button type="button" class="quill-subtabs__btn" data-subtab="agents" role="tab" aria-selected="false"><?php esc_html_e( 'For AI agents', 'quill' ); ?></button>
					</div>

					<div data-subtab-panel="humans">
						<div class="quill-get-started__intro">
							<span class="quill-eyebrow"><?php esc_html_e( 'Choose your starting point', 'quill' ); ?></span>
							<h2><?php esc_html_e( 'Build a custom theme with your AI coding tool', 'quill' ); ?></h2>
							<p><?php esc_html_e( 'Quill provides the editable content layer and the rules your agent follows. Your coding tool builds the WordPress theme.', 'quill' ); ?></p>
						</div>

						<div class="quill-agent-tools" aria-label="<?php esc_attr_e( 'Compatible AI coding tools', 'quill' ); ?>">
							<span class="quill-agent-tools__label"><?php esc_html_e( 'Works with', 'quill' ); ?></span>
							<span class="quill-agent-tool"><?php esc_html_e( 'Codex', 'quill' ); ?></span>
							<span class="quill-agent-tool"><?php esc_html_e( 'Claude', 'quill' ); ?></span>
							<span class="quill-agent-tool"><?php esc_html_e( 'Cursor', 'quill' ); ?></span>
							<span class="quill-agent-tool"><?php esc_html_e( 'Any other coding agents', 'quill' ); ?></span>
						</div>

						<div class="quill-paths">
							<article class="quill-path">
								<div class="quill-path__icon"><span class="dashicons dashicons-art" aria-hidden="true"></span></div>
								<span class="quill-path__number"><?php esc_html_e( 'Path 1', 'quill' ); ?></span>
								<h3><?php esc_html_e( 'Start from an idea', 'quill' ); ?></h3>
								<p><?php esc_html_e( 'Describe the website and let your agent design and build the complete theme from scratch.', 'quill' ); ?></p>
								<button type="button" class="quill-button" data-copy="quill-idea-prompt" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'quill' ); ?>">
									<?php esc_html_e( 'Copy starter prompt', 'quill' ); ?>
								</button>
								<textarea id="quill-idea-prompt" class="quill-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( $idea_prompt ); ?></textarea>
							</article>

							<article class="quill-path">
								<div class="quill-path__icon"><span class="dashicons dashicons-media-code" aria-hidden="true"></span></div>
								<span class="quill-path__number"><?php esc_html_e( 'Path 2', 'quill' ); ?></span>
								<h3><?php esc_html_e( 'Convert an HTML template', 'quill' ); ?></h3>
								<p><?php esc_html_e( 'Give your agent the complete HTML package and have it preserve the design while turning it into a proper WordPress theme.', 'quill' ); ?></p>
								<button type="button" class="quill-button" data-copy="quill-html-prompt" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'quill' ); ?>">
									<?php esc_html_e( 'Copy conversion prompt', 'quill' ); ?>
								</button>
								<textarea id="quill-html-prompt" class="quill-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( $html_prompt ); ?></textarea>
							</article>
						</div>

						<h3 class="quill-section-heading"><?php esc_html_e( 'How to use it', 'quill' ); ?></h3>
						<ol class="quill-steps">
							<li>
								<strong><?php esc_html_e( 'Connect your coding tool to WordPress', 'quill' ); ?></strong>
								<?php esc_html_e( 'Connect it to this website through MCP or another WordPress integration, or open your local WordPress installation as a project folder.', 'quill' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Use a starter prompt', 'quill' ); ?></strong>
								<?php esc_html_e( 'Copy the prompt for your starting point, then add the website link and your idea or HTML template location.', 'quill' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Let the agent build or convert the theme', 'quill' ); ?></strong>
								<?php esc_html_e( 'The agent builds the theme, creates the pages, imports editable images, and follows the Quill instructions.', 'quill' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Review and refine the result', 'quill' ); ?></strong>
								<?php esc_html_e( 'Check every page, test text, buttons and images in Quill, and ask your agent to fix anything that needs work.', 'quill' ); ?>
							</li>
						</ol>

						<div class="quill-get-started__details">
							<div>
								<h3><?php esc_html_e( 'Later design changes', 'quill' ); ?></h3>
								<p><?php esc_html_e( 'Return to your agent with the same instructions. Saved content remains intact as long as the field IDs and types stay the same.', 'quill' ); ?></p>
							</div>
							<div>
								<h3><?php esc_html_e( 'Client access', 'quill' ); ?></h3>
								<p><?php esc_html_e( 'Administrators and Editors can use Quill. The Editor role gives clients frontend editing without access to plugins, themes or settings.', 'quill' ); ?></p>
							</div>
						</div>
					</div>

					<div data-subtab-panel="agents" hidden>
						<div class="quill-agent-instructions__intro">
							<span class="quill-eyebrow"><?php esc_html_e( 'The complete contract', 'quill' ); ?></span>
							<h2><?php esc_html_e( 'Instructions for the coding agent', 'quill' ); ?></h2>
							<p><?php esc_html_e( 'Copy these instructions into any coding agent, or let a connected agent read them through the WordPress files or MCP environment.', 'quill' ); ?></p>
						</div>
						<div class="quill-instructions__actions">
							<button type="button" class="quill-button" data-copy="quill-agent-instructions" data-copied-label="<?php esc_attr_e( 'Copied', 'quill' ); ?>">
								<?php esc_html_e( 'Copy instructions', 'quill' ); ?>
							</button>
							<span class="quill-instructions__path"><?php esc_html_e( 'Bundled with Quill:', 'quill' ); ?> <code>docs/agent-instructions.md</code></span>
						</div>
						<textarea id="quill-agent-instructions" class="quill-instructions__text" readonly spellcheck="false" aria-label="<?php esc_attr_e( 'Instructions for AI agents', 'quill' ); ?>"><?php echo esc_textarea( $instructions ); ?></textarea>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * About tab.
	 *
	 * @return void
	 */
	private static function render_about() {
		?>
		<div class="quill-content">
			<div class="quill-card quill-about">
				<div class="quill-page-intro">
					<span class="quill-eyebrow"><?php esc_html_e( 'About', 'quill' ); ?></span>
					<h2><?php esc_html_e( 'About Quill', 'quill' ); ?></h2>
				</div>

				<p><?php esc_html_e( 'Quill gives clients frontend editing for text, rich text, buttons and images, while the custom theme keeps full control of layout and design.', 'quill' ); ?></p>

				<p><?php esc_html_e( 'One field, one owner: content that belongs to ACF, WooCommerce or WordPress stays there, and Quill never stores a second copy.', 'quill' ); ?></p>

				<p>
					<?php esc_html_e( 'Quill is a fork of Pencil by Rino de Boer, released under the GPL-2.0-or-later license.', 'quill' ); ?>
					<a href="https://github.com/rinothecoder/pencilino" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View the original project', 'quill' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Changelog tab.
	 *
	 * @return void
	 */
	private static function render_changelog() {
		?>
		<div class="quill-content">
			<div class="quill-card">
				<div class="quill-page-intro">
					<span class="quill-eyebrow"><?php esc_html_e( 'Release notes', 'quill' ); ?></span>
					<h2><?php esc_html_e( 'What is new in Quill', 'quill' ); ?></h2>
				</div>

				<div class="quill-changelog__ideas">
					<p><?php esc_html_e( 'Got an idea, or feedback on something that could be better? Open an issue and tell us.', 'quill' ); ?></p>
					<a href="<?php echo esc_url( QUILL_ISSUES_URL ); ?>" target="_blank" rel="noopener noreferrer" class="quill-button">
						<?php esc_html_e( 'Share an idea or feedback', 'quill' ); ?>
					</a>
				</div>

				<div class="quill-changelog__release">
					<div class="quill-changelog__release-header">
						<span class="quill-changelog__version">v0.9.2</span>
					</div>
					<ul class="quill-changelog__list">
						<li><?php esc_html_e( 'Initial Quill release, forked from Pencil by Rino v0.9.2.', 'quill' ); ?></li>
						<li><?php esc_html_e( 'Renamed from Pencil: helper functions are now quill_text(), quill_richtext(), quill_button(), quill_image() and quill_managed_region_open() / quill_managed_region_close().', 'quill' ); ?></li>
					</ul>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Support tab.
	 *
	 * @return void
	 */
	private static function render_support() {
		?>
		<div class="quill-content">
			<div class="quill-card">
				<h2><?php esc_html_e( 'Found a bug?', 'quill' ); ?></h2>
				<p><?php esc_html_e( 'Describe what happened and which AI tool built the theme, and we will take a look.', 'quill' ); ?></p>
				<a href="<?php echo esc_url( QUILL_ISSUES_URL ); ?>" target="_blank" rel="noopener noreferrer" class="quill-button">
					<?php esc_html_e( 'Report an issue', 'quill' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one activity record.
	 *
	 * @param object $activity Activity database record.
	 * @return void
	 */
	private static function render_activity_row( $activity ) {
		$timestamp  = strtotime( $activity->created_at . ' UTC' );
		$when       = $timestamp ? wp_date( 'j M Y, H:i', $timestamp ) : $activity->created_at;
		$user_name  = $activity->user_name ? $activity->user_name : __( 'Unknown user', 'quill' );
		$page_id    = absint( $activity->page_id );
		$page_title = $page_id ? get_the_title( $page_id ) : '';
		$page_url   = $page_id ? get_permalink( $page_id ) : '';
		$edit_url   = add_query_arg(
			array(
				'quill-edit'  => '1',
				'quill-field' => $activity->field_id,
			),
			$page_url ? $page_url : home_url( '/' )
		);

		$who_markup = sprintf(
			'<span class="quill-admin__user">%s<span>%s</span></span>',
			get_avatar( absint( $activity->user_id ), 20, '', '', array( 'class' => 'quill-admin__avatar' ) ),
			esc_html( $user_name )
		);

		if ( $page_title && $page_url ) {
			$where_markup = sprintf( '<a href="%s">%s</a>', esc_url( $page_url ), esc_html( $page_title ) );
		} else {
			$where_markup = sprintf( '<span class="quill-admin__muted">%s</span>', esc_html__( 'a site-wide field', 'quill' ) );
		}

		$when_markup = sprintf( '<span class="quill-admin__when">%s</span>', esc_html( $when ) );
		?>
		<div class="quill-admin__record-head">
			<div class="quill-admin__record-col quill-admin__record-col--before">
				<strong class="quill-admin__field" title="<?php echo esc_attr( $activity->field_id ); ?>"><?php echo esc_html( $activity->field_label ); ?></strong>
			</div>
			<span class="quill-admin__record-gutter" aria-hidden="true"></span>
			<div class="quill-admin__record-col quill-admin__record-col--after">
				<p class="quill-admin__meta-line">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: user name with avatar, 2: page or location, 3: date and time. */
							__( '%1$s updated %2$s on %3$s', 'quill' ),
							$who_markup,
							$where_markup,
							$when_markup
						)
					);
					?>
				</p>
				<a class="quill-admin__edit" href="<?php echo esc_url( $edit_url ); ?>">
					<?php esc_html_e( 'Edit', 'quill' ); ?>
				</a>
			</div>
		</div>
		<div class="quill-admin__change">
			<?php self::render_value( __( 'Before:', 'quill' ), $activity->old_value, $activity->field_type ); ?>
			<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
			<?php self::render_value( __( 'After:', 'quill' ), $activity->new_value, $activity->field_type ); ?>
		</div>
		<?php
	}

	/**
	 * Render one before or after value box.
	 *
	 * Rich text keeps its formatting so a change such as adding bold is visible.
	 *
	 * @param string $label      Screen-reader label.
	 * @param string $value      Stored value.
	 * @param string $field_type Field type.
	 * @return void
	 */
	private static function render_value( $label, $value, $field_type ) {
		$is_rich = 'richtext' === $field_type && '' !== trim( wp_strip_all_tags( (string) $value ) );
		?>
		<div class="quill-admin__value-text<?php echo $is_rich ? ' quill-admin__value-text--rich' : ''; ?>">
			<span class="screen-reader-text"><?php echo esc_html( $label ); ?> </span>
			<?php
			if ( $is_rich ) {
				echo wp_kses( $value, Quill_Fields::richtext_tags() );
			} else {
				echo esc_html( self::display_value( $value, $field_type ) );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Return a readable plain-text activity value.
	 *
	 * @param string $value      Stored value.
	 * @param string $field_type Field type.
	 * @return string
	 */
	private static function display_value( $value, $field_type ) {
		if ( 'image' === $field_type ) {
			$attachment_id = absint( $value );

			if ( ! $attachment_id ) {
				return __( 'No image', 'quill' );
			}

			$title = get_the_title( $attachment_id );

			return $title ? $title : sprintf(
				/* translators: %d: Media attachment ID. */
				__( 'Media #%d', 'quill' ),
				$attachment_id
			);
		}

		if ( 'button' === $field_type ) {
			$button = json_decode( (string) $value, true );

			if ( is_array( $button ) && ! empty( $button['text'] ) ) {
				return sprintf( '%s (%s)', $button['text'], isset( $button['url'] ) ? $button['url'] : '' );
			}

			return __( 'Empty', 'quill' );
		}

		$value = trim( wp_strip_all_tags( (string) $value ) );

		if ( '' === $value ) {
			return __( 'Empty', 'quill' );
		}

		return $value;
	}
}
