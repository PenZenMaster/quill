<?php
/**
 * Theme-facing Quill helpers.
 *
 * @package Quill
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return edit-mode attributes for a registered field, or an empty string for visitors.
 *
 * @internal
 *
 * @param array $field Registered field from Quill_Fields::register().
 * @return string Escaped attribute string with a leading space.
 */
function quill_internal_edit_attributes( $field ) {
	if ( '' === $field['id'] || ! Quill_Plugin::can_edit() ) {
		return '';
	}

	$attrs = array(
		'data-quill-field' => $field['id'],
		'data-quill-label' => $field['schema']['label'],
		'data-quill-type'  => $field['schema']['type'],
		'tabindex'         => '-1',
	);

	if ( ! empty( $field['schema']['max_length'] ) ) {
		$attrs['data-quill-max-length'] = (string) absint( $field['schema']['max_length'] );
	}

	$html = '';

	foreach ( $attrs as $name => $value ) {
		$html .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
	}

	return $html;
}

/**
 * Return sanitized theme classes from a helper's `class` argument.
 *
 * @internal
 *
 * @param array    $args          Helper arguments.
 * @param string[] $extra_classes Classes to add.
 * @return string
 */
function quill_internal_class_list( $args, $extra_classes = array() ) {
	$classes = isset( $args['class'] ) ? preg_split( '/\s+/', (string) $args['class'] ) : array();
	$classes = array_merge( $extra_classes, array_map( 'sanitize_html_class', $classes ) );

	return implode( ' ', array_unique( array_filter( $classes ) ) );
}

/**
 * Return the inline image-slot style declared by a theme.
 *
 * @internal
 *
 * @param array $args Image settings.
 * @return string
 */
function quill_internal_image_style( $args ) {
	$rules = array();

	if (
		! empty( $args['aspect_ratio'] )
		&& preg_match( '#^\s*(\d+(?:\.\d+)?)\s*(?:[/:]\s*(\d+(?:\.\d+)?))?\s*$#', (string) $args['aspect_ratio'], $matches )
	) {
		$rules[] = 'aspect-ratio:' . $matches[1] . ( ! empty( $matches[2] ) ? ' / ' . $matches[2] : '' );
	}

	$fits = array( 'cover', 'contain', 'fill', 'none', 'scale-down' );

	if ( isset( $args['object_fit'] ) && in_array( $args['object_fit'], $fits, true ) ) {
		$rules[] = 'object-fit:' . $args['object_fit'];
	} elseif ( $rules ) {
		$rules[] = 'object-fit:cover';
	}

	if ( ! empty( $args['object_position'] ) && preg_match( '/^[a-z0-9.%\s-]+$/i', (string) $args['object_position'] ) ) {
		$rules[] = 'object-position:' . trim( $args['object_position'] );
	}

	return implode( ';', $rules );
}

if ( ! function_exists( 'quill_text' ) ) {
	/**
	 * Render a Quill-owned single-line text field.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Field settings: label, default, max_length, scope.
	 * @return void
	 */
	function quill_text( $id, $args = array() ) {
		$field = Quill_Fields::register( 'text', $id, $args );
		$attrs = quill_internal_edit_attributes( $field );

		if ( '' === $attrs ) {
			echo esc_html( $field['value'] );
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped by quill_internal_edit_attributes().
		printf( '<span class="quill-field"%s>%s</span>', $attrs, esc_html( $field['value'] ) );
	}
}

if ( ! function_exists( 'quill_richtext' ) ) {
	/**
	 * Render a Quill-owned rich text field with bold, italic, links, and lists.
	 *
	 * Always rendered inside `<div class="quill-richtext">` so layout is the same
	 * for visitors and editors.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Field settings: label, default, max_length, class, scope.
	 * @return void
	 */
	function quill_richtext( $id, $args = array() ) {
		$field   = Quill_Fields::register( 'richtext', $id, $args );
		$classes = quill_internal_class_list( $args, array( 'quill-richtext' ) );
		$attrs   = quill_internal_edit_attributes( $field );

		if ( '' !== $attrs ) {
			$classes .= ' quill-field';
		}

		printf(
			'<div class="%s"%s>%s</div>',
			esc_attr( $classes ),
			$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by quill_internal_edit_attributes().
			wp_kses( $field['value'], Quill_Fields::richtext_tags() )
		);
	}
}

if ( ! function_exists( 'quill_button' ) ) {
	/**
	 * Render a Quill-owned link button with editable text and URL.
	 *
	 * `before` and `after` hold theme-controlled markup inside the link, such as
	 * a decorative icon. The editable text is then wrapped in
	 * `<span class="quill-button__text">`.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Field settings: label, default (array with text and url),
	 *                     max_length, class, target, before, after, scope.
	 * @return void
	 */
	function quill_button( $id, $args = array() ) {
		$field = Quill_Fields::register( 'button', $id, $args );
		$value = $field['value'];
		$attrs = quill_internal_edit_attributes( $field );

		if ( '' === $value['text'] && '' === $attrs ) {
			return;
		}

		$classes = quill_internal_class_list( $args, '' !== $attrs ? array( 'quill-field' ) : array() );
		$target  = isset( $args['target'] ) && '_blank' === $args['target'] ? ' target="_blank" rel="noopener"' : '';
		$before  = isset( $args['before'] ) ? wp_kses_post( $args['before'] ) : '';
		$after   = isset( $args['after'] ) ? wp_kses_post( $args['after'] ) : '';
		$text    = esc_html( $value['text'] );

		if ( '' !== $before || '' !== $after ) {
			$text = '<span class="quill-button__text">' . $text . '</span>';
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Every part is escaped above or by quill_internal_edit_attributes().
		printf(
			'<a%s href="%s"%s%s>%s%s%s</a>',
			$classes ? ' class="' . esc_attr( $classes ) . '"' : '',
			esc_url( '' !== $value['url'] ? $value['url'] : '#' ),
			$target,
			$attrs,
			$before,
			$text,
			$after
		);
		// phpcs:enable
	}
}

if ( ! function_exists( 'quill_image' ) ) {
	/**
	 * Render a Media Library-backed image field.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Image settings: label, default (attachment ID), size, class,
	 *                     loading, fetchpriority, aspect_ratio, object_fit,
	 *                     object_position, scope.
	 * @return void
	 */
	function quill_image( $id, $args = array() ) {
		$field         = Quill_Fields::register( 'image', $id, $args );
		$attachment_id = $field['value'];

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$attrs = array(
			'class' => quill_internal_class_list( $args ),
		);
		$style = quill_internal_image_style( $args );

		if ( $style ) {
			$attrs['style'] = $style;
		}

		if ( isset( $args['loading'] ) ) {
			$attrs['loading'] = sanitize_key( $args['loading'] );
		}

		if ( isset( $args['fetchpriority'] ) ) {
			$attrs['fetchpriority'] = sanitize_key( $args['fetchpriority'] );
		}

		if ( '' !== $field['id'] && Quill_Plugin::can_edit() ) {
			$attrs['class']                    = trim( $attrs['class'] . ' quill-field quill-field--image' );
			$attrs['data-quill-field']         = $field['id'];
			$attrs['data-quill-label']         = $field['schema']['label'];
			$attrs['data-quill-type']          = 'image';
			$attrs['data-quill-attachment-id'] = (string) $attachment_id;
			$attrs['tabindex']                 = '-1';
		}

		echo wp_get_attachment_image( $attachment_id, $field['schema']['size'], false, $attrs );
	}
}

if ( ! function_exists( 'quill_managed_region_open' ) ) {
	/**
	 * Open a read-only region owned by ACF or another content provider.
	 *
	 * @param array $args Managed-region settings.
	 * @return void
	 */
	function quill_managed_region_open( $args = array() ) {
		$provider = isset( $args['provider'] ) ? sanitize_key( $args['provider'] ) : 'provider';
		$names    = array(
			'acf'         => 'ACF',
			'jetengine'   => 'JetEngine',
			'woocommerce' => 'WooCommerce',
			'wordpress'   => 'WordPress',
		);
		$name     = isset( $names[ $provider ] ) ? $names[ $provider ] : ucfirst( $provider );
		$label    = isset( $args['label'] ) && $args['label']
			? sanitize_text_field( $args['label'] )
			: sprintf(
				/* translators: %s: content provider name. */
				__( 'Editable in %s', 'quill' ),
				$name
			);
		$action_label = isset( $args['action_label'] ) && $args['action_label']
			? sanitize_text_field( $args['action_label'] )
			: __( 'Edit', 'quill' );
		$classes      = array( 'quill-managed-region' );

		if ( ! empty( $args['class'] ) ) {
			$extra_classes = preg_split( '/\s+/', (string) $args['class'] );
			$classes       = array_merge( $classes, array_map( 'sanitize_html_class', $extra_classes ) );
		}

		$classes = array_filter( array_unique( $classes ) );
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			<?php if ( Quill_Plugin::can_edit() ) : ?>
				data-quill-managed="<?php echo esc_attr( $provider ); ?>"
				data-quill-managed-label="<?php echo esc_attr( $label ); ?>"
				data-quill-managed-action-label="<?php echo esc_attr( $action_label ); ?>"
				<?php if ( ! empty( $args['edit_url'] ) ) : ?>
					data-quill-managed-edit-url="<?php echo esc_url( $args['edit_url'] ); ?>"
				<?php endif; ?>
			<?php endif; ?>
		>
		<?php
	}
}

if ( ! function_exists( 'quill_managed_region_close' ) ) {
	/**
	 * Close a read-only provider region.
	 *
	 * @return void
	 */
	function quill_managed_region_close() {
		echo '</div>';
	}
}
