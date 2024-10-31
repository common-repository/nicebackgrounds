<?php
/**
 * The functions in this file are reusable HTML code generators.
 */

/**
 * Returns the HTML code for a WordPress dashicons font icon.
 *
 * @param string $icon The last part of a dashicons font icon name.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_icon( $icon ) {
	return nicebackgrounds_wrap( null, array( 'dashicons', 'dashicons-' . $icon ), null, $tag = 'span', null );
}

/**
 * Returns the HTML code for an element with an opening and closing tag, such as a div.
 *
 * @param string $content The inner HTML or text to be wrapped.
 * @param mixed $classes (Optional) A CSS class name or array of classes to set in the class attribute.
 * @param array $attrs (Optional) An associative array of key-value pairs to use as attributes.
 * @param string $tag (Optional) The name of the HTML tag e.g. 'div', 'span', etc. Defaults to 'div'.
 * @param string $class_prefix (Optional) The string with which to prefix the classes.  Defaults to 'nicebackgrounds-'.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_wrap( $content, $classes = null, $attrs = null, $tag = 'div', $class_prefix = 'nicebackgrounds-' ) {
	return nicebackgrounds_make_tag( $tag, $classes, $attrs, $class_prefix, false ) . $content . "</$tag>";
}

/**
 * Returns the HTML code for a hyperlink / anchor.
 *
 * @param string $content The inner HTML or text to be wrapped.
 * @param mixed $classes (Optional) A CSS class name or array of classes to set in the class attribute.
 * @param string $href (Optional) The value of the href attribute.
 * @param string $title (Optional) The value of the title attribute.
 * @param array $attrs (Optional) An associative array of key-value pairs to use as attributes.
 * @param string $class_prefix (Optional) The string with which to prefix the classes.  Defaults to 'nicebackgrounds-'.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_link( $content, $classes = null, $href = null, $title = null, $attrs = null, $class_prefix = 'nicebackgrounds-' ) {
	$attrs          = ! empty( $attrs ) ? $attrs : null;
	$attrs['href']  = $href;
	$attrs['title'] = $title;

	return nicebackgrounds_wrap( $content, $classes, $attrs, 'a', $class_prefix );
}

/**
 * Returns the HTML code for a paragraph.
 *
 * @param string $content The inner HTML or text to be wrapped.
 * @param mixed $classes (Optional) A CSS class name or array of classes to set in the class attribute.
 * @param array $attrs (Optional) An associative array of key-value pairs to use as attributes.
 * @param string $class_prefix (Optional) The string with which to prefix the classes.  Defaults to 'nicebackgrounds-'.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_p( $content, $classes = null, $attrs = null, $class_prefix = 'nicebackgrounds-' ) {
	return nicebackgrounds_wrap( $content, $classes, $attrs, 'p', $class_prefix );
}

/**
 * Returns the HTML code for any opening or self-closing tag.
 *
 * @param string $tag (Optional) The name of the HTML tag e.g. 'div', 'span', etc. Defaults to 'div'.
 * @param mixed $classes (Optional) A CSS class name or array of classes to set in the class attribute.
 * @param array $attrs (Optional) An associative array of key-value pairs to use as attributes.
 * @param string $class_prefix (Optional) The string with which to prefix the classes. Defaults to 'nicebackgrounds-'.
 * @param bool $self_close (Optional) Whether the tag is self closing. Defaults to true.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_make_tag( $tag, $classes = null, $attrs = null, $class_prefix = 'nicebackgrounds-', $self_close = true ) {
	$attributes = array();
	foreach ( (array) $attrs as $attr => $attr_val ) {
		if ( 'class' === $attr ) {
			// Disallow class attribute because there is a separate param for it.
			continue;
		}
		if ( 'id' === $attr ) {
			$attr_val = nicebackgrounds_id( $attr_val );
		} elseif ( 'src' === $attr || 'href' === $attr ) {
			$attr_val = esc_url( $attr_val );
		} else {
			$attr_val = esc_attr( $attr_val );
		}
		$attributes[] = $attr . '="' . $attr_val . '"';
	}
	if ( ! empty( $classes ) ) {
		$classes = is_array( $classes ) ? $classes : explode( ' ', $classes );
		foreach ( $classes as &$class ) {
			$class = nicebackgrounds_id( $class_prefix . $class );
		}
		$attributes[] = 'class="' . implode( ' ', $classes ) . '"';
	}
	$attributes = ! empty( $attributes ) ? ' ' . implode( ' ', $attributes ) : null;
	$close      = $self_close ? ' /' : '';
	$tag        = esc_attr( $tag );

	return "<$tag$attributes$close>";
}

/**
 * Returns the HTML code for a set of tabs and their corresponding content panels.
 *
 * @param string $name The identifier for the tab group, should be compatible for use as a class name.
 * @param array $item_list An associative array of tab configurations where the keys are identifiers for the items and
 * the values are associate arrays containing keys 'title' and 'content' with appropriate values in each.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_tabs( $name, $item_list ) {
	$ret  = '';
	$name = nicebackgrounds_id( $name );
	foreach ( $item_list as $item_key => $item ) {
		$ret .= nicebackgrounds_wrap(
			nicebackgrounds_wrap( $item['title'], null, null, 'span' ),
			'tab-' . $name . '-' . $item_key,
			array( 'data-target' => 'nicebackgrounds-tab-panel-' . $name . '-' . $item_key ),
			'li'
		);
	}
	$ret = nicebackgrounds_wrap( $ret, 'tabs', null, 'ul' );

	$panels = '';
	foreach ( $item_list as $item_key => $item ) {
		$panels .= nicebackgrounds_wrap(
			nicebackgrounds_wrap( $item['content'], null, null, 'span' ),
			'tab-panel-' . $name . '-' . $item_key,
			array( 'data-tab-key' => $item_key )
		);
	}
	$ret .= nicebackgrounds_wrap( $panels, 'tab-panels' );

	return nicebackgrounds_wrap( $ret, array( 'tab-group', 'tab-group-' . $name ) );
}

/**
 * Returns the HTML code for a form input.
 *
 * @param string $type Any of 'options' for a set of checkboxes or radios, 'option' for a single radio/checkbox,
 * 'select' for a select list, or the name of any valid input type such as 'text'.
 * @param string $name An identifier to be used in class names, and may also be used in a name attribute.
 * @param mixed $value The value of the input.
 * @param string $label The label of the input.
 * @param array $params (It's complicated) An associative array of extra parameters.  The supported values depend on
 * the value of $type; 'options' is needed for options/select types, 'multiple' is supported for options/option/select
 * this is how to distinguish between radios and checkboxes, 'checked_val' is needed for option, 'prefix' 'suffix'
 * 'attrs' and 'class' have varying support and the function should be amended as needed but do not apply to the actual
 * input tag.  Additionally 'iattrs' and 'iclass' are used for attributes and classes directly on input tags.
 * @param bool $nowrap (Optional) When set to true will omit the wrapping div.  Defaults to false.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_input( $type, $name, $value, $label, $params = array(), $nowrap = false ) {
	$ret = "";
	switch ( $type ) {
		case 'options':
			if ( ! empty( $label ) ) {
				$ret .= nicebackgrounds_wrap( $label, null, array( 'for' => $name ), 'label' );
			}
			if ( ! empty( $params['options'] ) ) {
				foreach ( $params['options'] as $k => $v ) {
					$attributes = ! empty( $v['attrs'] ) ? $v['attrs'] : array();
					$ret .= nicebackgrounds_input( 'option', $name, $k, $v['label'],
						array( 'checked_val' => $value, 'iattrs' => $attributes ) );
				}
			}
			break;

		case 'option':
			$params['suffix'] = nicebackgrounds_wrap( $label, null, null, 'span' );
			if ( ! empty( $params['checked_val'] ) && strval( $params['checked_val'] ) === strval( $value ) ) {
				$params['iattrs']['checked'] = 'checked';
			}
			$ret .= nicebackgrounds_input( ! empty( $params['multiple'] ) ? 'checkbox' : 'radio', $name, $value, null, $params, true );
			$ret = nicebackgrounds_wrap( $ret, null, null, 'label' );
			break;

		case 'select':
			if ( ! empty( $params['options'] ) ) {
				foreach ( $params['options'] as $k => $v ) {
					$attributes          = ! empty( $v['attrs'] ) ? $v['attrs'] : array();
					$attributes['value'] = $k;
					if ( $k === $value ) {
						$attributes['selected'] = 'selected';
					}
					$ret .= nicebackgrounds_wrap( $v['label'], null, $attributes, 'option' );
				}
			}
			$attributes = array( 'name' => $name );
			if ( ! empty( $params['multiple'] ) ) {
				$attributes['multiple'] = 'multiple';
			}
			$ret = nicebackgrounds_wrap( $ret, null, array( 'name' => $name ), 'select' );
			if ( ! empty( $label ) ) {
				$ret = nicebackgrounds_wrap( $label, null, array( 'for' => $name ), 'label' ) . $ret;
			}
			break;

		default:
			if ( ! empty( $label ) ) {
				$ret .= nicebackgrounds_wrap( $label, null, array( 'for' => $name ), 'label' );
			}
			$iattrs = array_merge( ( ! empty( $params['iattrs'] ) ? $params['iattrs'] : array() ), array(
				'type'  => $type,
				'name'  => $name,
				'value' => $value
			) );
			$ret .= ! empty( $params['prefix'] ) ? $params['prefix'] : '';
			$ret .= nicebackgrounds_make_tag( 'input', ( ! empty( $params['iclass'] ) ? $params['iclass'] : array() ), $iattrs );
			$ret .= ! empty( $params['suffix'] ) ? $params['suffix'] : '';
	}
	if ( ! $nowrap ) {
		$class = array_merge( array(
			'form-row',
			'input-type-' . $type,
			'input-' . $name
		), ( ! empty( $params['class'] ) ? $params['class'] : array() ) );
		$ret   = nicebackgrounds_wrap( $ret, $class, ( ! empty( $params['attrs'] ) ? $params['attrs'] : array() ) );
	}

	return $ret;
}

/**
 * Returns the HTML code for the Picklist widget.
 *
 * @param string $name An identifier to be used in class names, and may also be used in a name attribute.
 * @param string $label The label of the input.
 * @param mixed $value The value of the input. Can be a numeric array or a comma separated string of values.
 * @param array $options The list of potential values to display as choices.
 * @param string $placeholder The value to use in the placeholder attribute.  No one ever sees this if everything is working right.
 * @param bool $multiple (Optional) Whether to allow multiple values to be selected.  Defaults to true.
 * @param array $attrs (Optional) An associative array of key-value pairs to use as attributes.
 * @param string $option_save_callback (Optional) The name of a JavaScript function to call when a user types a new
 * value into the freetext input.  Can be supplied as 'namespace.function_name'.
 *
 * @para string $description (Optional) Some help text to output directly below the widget.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_picklist( $name, $label, $value, $options, $placeholder, $multiple = true, $attrs = null, $option_save_callback = null, $description = null ) {
	if ( is_array( $value ) ) {
		$values = $value;
		$value  = implode( ',', $value );
	} else {
		$values = explode( ',', trim( $value ) );
	}

	$ret = nicebackgrounds_wrap( $label, null, array( 'for' => $name ), 'label' );
	$ret .= nicebackgrounds_make_tag( 'input', 'picklist-value', array(
		'type'        => 'text',
		'name'        => $name,
		'value'       => $value,
		'placeholder' => $placeholder
	), '' );
	sort( $values );

	$vals = '';
	foreach ( $values as $v ) {
		$vals .= trim( $v ) === '' ? '' : nicebackgrounds_wrap( trim( $v ) . nicebackgrounds_icon( 'no' ), 'picklist-picked-value', null, 'span', '' );
		if ( ( $key = array_search( $v, $options ) ) !== false ) {
			unset( $options[ $key ] );
		}
	}
	$vals .= nicebackgrounds_make_tag( 'input', 'picklist-freetext', array( 'type' => 'text' ), '' );
	$ret .= nicebackgrounds_wrap( $vals, 'picklist-picked-values', null, 'div', '' );

	sort( $options );
	$opts = '';
	foreach ( $options as $option ) {
		$opts .= nicebackgrounds_wrap( trim( $option ) . nicebackgrounds_icon( 'trash' ), 'picklist-option', null, 'span', '' );
	}
	$ret .= nicebackgrounds_wrap( $opts, 'picklist-options', null, 'div', '' );
	if ( ! empty( $description ) ) {
		$ret .= nicebackgrounds_wrap( $description, 'description' );
	}

	$ret .= nicebackgrounds_wrap(
		nicebackgrounds_wrap( __( 'Tip: to remove items permanently; press and hold the delete icon', 'nicebackgrounds' ), null, null, 'em' ),
		'picklist-delete-hint',
		null,
		'div',
		''
	);

	if ( ! empty( $option_save_callback ) ) {
		$attrs['data-option-save-callback'] = $option_save_callback;
	}

	$classes = array( 'form-row', 'picklist', 'picklist-' . $name );
	if ( $multiple ) {
		$classes[] = 'multiple';
	}

	return nicebackgrounds_wrap( $ret, $classes, $attrs );
}

/**
 * Returns the HTML code for a thumbnail.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param array $image The associative array representing a set image.
 * @param string $src (Optional) Override the thumbnail image URL.
 * @param string $full_src (Optional) Override the original size image link.
 * @param string $class (Optional) A CSS class name or array of classes to set in the class attribute.  Defaults to 'thumb'.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_thumb( $set_id, $set, $image, $src = null, $full_src = null, $class = 'thumb' ) {

	if ( is_null( $full_src ) ) {
		$full_src = nicebackgrounds_get_full_image_src( $set_id, $set, $image );
	}
	if ( is_null( $src ) ) {
		$src = nicebackgrounds_get_image_src( $set_id, $set, $image, nicebackgrounds_thumb_size( $set_id, $set ) );
	}

	$dimensions = $image['w'] . 'x' . $image['h'];
	$ret        = '';
	if ( ! empty( $src ) ) {
		$ret .= nicebackgrounds_make_tag( 'img', null, array( 'src' => $src ) );
	} else {
		// This image will be broken, but it allows for deletion.
		$ret .= nicebackgrounds_make_tag( 'img', null, array( 'src' => $image['file'] ) );
		$full_src = $image['file'];
	}
	$ret .= nicebackgrounds_wrap( $dimensions, 'dimensions', null, 'span' );
	$wait = nicebackgrounds_wrap( nicebackgrounds_icon( 'update' ), 'modal-wait', null, 'span' );
	$ret .= nicebackgrounds_link( nicebackgrounds_icon( 'external' ) . $wait, 'modal', $full_src, __( 'View original', 'nicebackgrounds' ) );
	$ret .= nicebackgrounds_link( nicebackgrounds_icon( 'no' ), 'remove', '', __( 'Remove', 'nicebackgrounds' ) );

	return nicebackgrounds_wrap( $ret, $class );
}

/**
 * Returns the HTML code for an Unsplash preload thumbnail.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param array $image The associative array representing a set image.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_preload_thumb( $set_id, $set, $image ) {
	// Just generate thumbnail size.
	nicebackgrounds_generate_image_adaptive_sizes( $set_id, $set, $image, 10, true );

	// Get a thumbnail for the result.
	$source_file_full = nicebackgrounds_image_url( $set_id, 'preloads', $image['file'] );
	$source_file      = nicebackgrounds_image_url( $set_id, nicebackgrounds_thumb_size( $set_id, $set ), $image['file'] );

	return nicebackgrounds_thumb( $set_id, $set, $image, $source_file, $source_file_full );
}

