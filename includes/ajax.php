<?php
/**
 * This functions in this file are dynamically called by nicebackgrounds_ajax() based on the 'func' parameter of
 * an Ajax request.
 */

/**
 * Handles loading an Unsplash preload.
 *
 * Returns the ajax result for an Unsplash preload thumbnail, either from the CDN
 * or by leeching the file, depending on the settings.  For a CDN result, it returns the thumbnail markup without
 * the image and the url to the image is supplied separately. For the leeched file the thumbnail markup contains
 * the urls to the stored files.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_load_unsplash() {
	$search = array();
	parse_str( $_POST['search'], $search );

	// Sanitize the search form values.  They won't be saved by this plugin, but they are used to make requests so there is
	// potential they could be logged somewhere.
	foreach ( $search as $key => $value ) {
		$search[ $key ] = sanitize_text_field( $value );
	}

	// Fix up some form submission issues.
	if ( empty( $search['search-has-keywords'] ) ) {
		unset( $search['search-keywords'] );
	}
	if ( 'category' !== $search['search-filters'] ) {
		unset( $search['search-category'] );
	}
	if ( 'user' !== $search['search-filters'] && 'likes' !== $search['search-filters'] ) {
		unset( $search['search-user'] );
	}

	$set = nicebackgrounds_get_set( $_POST['set_id'] );

	// Some size magic for optimisation.
	$search['size'] = '100x100';
	$large_size     = nicebackgrounds_largest_size( $_POST['set_id'], $set );
	$unsplash_url   = nicebackgrounds_resolve_unsplash_url( $search, $large_size, null );

	if ( $unsplash_url ) {
		// Determine whether to use Unsplash CDN or local.
		if ( ! empty( $set['cdn'] ) ) {
			// Use Unsplash CDN.
			return nicebackgrounds_unsplash_cdn_result( $unsplash_url );
		} else {
			// Attempt storage.
			$result = nicebackgrounds_leech( $_POST['set_id'], $unsplash_url, true );
			if ( ! $result['success'] ) {
				// Use Unsplash CDN.
				return nicebackgrounds_unsplash_cdn_result( $unsplash_url );
			}

			return $result;
		}
	}

	return array( 'success' => false );
}

/**
 * Handles an Unsplash image being picked for a collection and returns the thumbnail code.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_save_unsplash() {
	// $_POST['file'] is form data that will be stored.
	$_POST['file'] = sanitize_text_field( $_POST['file'] );

	if ( ! empty( $_POST['cdn'] ) ) {
		$url_parts = explode( '?', $_POST['file'] );

		// Get from CDN. This is done via the leech process in case the settings change.
		$return = nicebackgrounds_leech( $_POST['set_id'], $_POST['file'], false, $url_parts[0] );

	} else {
		// Get from Preload.  The Preload path is kind of like a temp path.
		$return = nicebackgrounds_preload_to_full( $_POST['set_id'], $_POST['file'] );
	}

	// Just in case a setting has changed between the preload and now.
	nicebackgrounds_remove_preload( $_POST['set_id'], $_POST['file'] );

	return $return;
}

/**
 * Handles an Unsplash image being removed from a collection.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_remove_unsplash() {
	return array( nicebackgrounds_remove_preload( $_POST['set_id'], $_POST['file'] ) );
}

/**
 * Handles an image being added from a URL to a collection and returns the thumbnail code.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_save_url() {
	// $_POST['url'] is sanitized here as it is a form value, filenames derived from it are sanitized again later,
	// and the image data resulting from requesting the URL is validated.
	return nicebackgrounds_leech( $_POST['set_id'], sanitize_text_field( $_POST['url'] ) );
}

/**
 * Handles an image being added by upload to a collection and returns the thumbnail code.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_save_upload() {
	$dir       = nicebackgrounds_image_path( $_POST['set_id'], 'full' );
	$result    = array();
	$timelimit = 20 / max( 1, count( $_FILES ) );
	foreach ( $_FILES as $file ) {
		if ( $error = nicebackgrounds_file_upload_error( $file ) ) {
			return array( 'success' => false, 'message' => $error );
		}
		$file_type = nicebackgrounds_check_mime( $file['tmp_name'] );
		if ( ! $file_type ) {
			return array( 'success' => false, 'message' => __( 'Invalid file type.', 'nicebackgrounds' ) );
		}
		$pathinfo = pathinfo( $file['name'] );
		$filename = nicebackgrounds_resolve_file_save_path( $dir, $pathinfo['filename'], $pathinfo['extension'], $file_type );
		if ( ! move_uploaded_file( $file['tmp_name'], $dir . $filename ) ) {
			return array( 'success' => false, 'message' => __( 'Could not save file.', 'nicebackgrounds' ) );
		}
		list( $width, $height ) = getimagesize( $dir . $filename );

		if ( ! nicebackgrounds_estimate_image_memory( $file_type, $width, $height ) ) {
			return array(
				'success' => false,
				'message' => __( 'File size and memory limit do not jive.', 'nicebackgrounds' )
			);
		}

		$image = array(
			'file' => $filename,
			'w'    => $width,
			'h'    => $height,
			'mime' => $file_type,
		);

		$set = nicebackgrounds_get_set( $_POST['set_id'] );
		nicebackgrounds_store_image( $_POST['set_id'], $image );

		// Generate sizes.
		nicebackgrounds_generate_image_adaptive_sizes( $_POST['set_id'], $set, $image, $timelimit );

		// Get a thumbnail for the result.
		$result[] = nicebackgrounds_thumb( $_POST['set_id'], $set, $image );
	}
	if ( empty( $result ) ) {
		return array( 'success' => false, 'message' => __( 'Could not process your files.', 'nicebackgrounds' ) );
	} else {
		return array( 'success' => true, 'result' => implode( ' ', $result ), 'message' => nicebackgrounds_success() );
	}
}

/**
 * Handles the auto saves on the admin page.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_save_set() {
	$sets = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );
	$set  = &$sets[ $_POST['set_id'] ];

	// Arrayify, sanitize, and sort sizes.
	if ( 'sizes' === $_POST['key'] ) {
		$sizes           = explode( ',', $_POST['value'] );
		$sanitized_sizes = array();
		foreach ( $sizes as $size ) {
			$size = intval( trim( $size ) );
			if ( $size > 0 ) {
				$sanitized_sizes[] = $size;
			}
		}
		rsort( $sanitized_sizes );
		$_POST['value'] = $sanitized_sizes;
	} else {
		// Not all of these will come from an actual text input, but the radios/checkboxes/selects should pass this
		// sanitization as though they did.  Values not recognised as valid options will still be stored, but there will
		// be no adverse effects as configurable functionality in this plugin tends to fall back to a reasonable default.
		$_POST['value'] = sanitize_text_field( $_POST['value'] );

	}

	// Special case for "has-keywords"; rather than saving that value, remove the keywords.
	if ( 'has-keywords' === $_POST['key'] ) {
		$_POST['key']   = 'keywords';
		$_POST['value'] = '';
	}

	// Figure out where to store the value.
	if ( isset( $set['unsplash'][ $_POST['key'] ] ) ) {
		$set['unsplash'][ $_POST['key'] ] = $_POST['value'];
	} else {
		$set[ $_POST['key'] ] = $_POST['value'];
	}

	$result = update_option( 'nicebackgrounds_sets', $sets );

	return array( 'success' => true, 'result' => $result );
}

/**
 * Handles an image being removed from a collection.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_remove_image() {
	nicebackgrounds_remove_image( $_POST['set_id'], $_POST['file'] );

	return array( 'success' => true );
}

/**
 * Handles requests to process the adaptive image generation while the user is looking at the admin page.  The ajax
 * result is indicative of whether the process was fully completed or not.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_generate_set() {
	$result = nicebackgrounds_find_and_generate_missing_adaptives( $_POST['set_id'], nicebackgrounds_get_set( $_POST['set_id'] ) );

	return array( 'success' => true, 'result' => $result );
}

/**
 * Handles responding to the use of the action links in the top right corner of the set on the admin page.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_set_action() {
	switch ( $_POST['set_action'] ) {
		case 'clear':
			nicebackgrounds_clear_adaptive_sizes( $_POST['set_id'], nicebackgrounds_get_set( $_POST['set_id'] ) );
			// Clear the preloads.
			nicebackgrounds_rmdir( nicebackgrounds_image_path( $_POST['set_id'], 'preloads' ) );
			break;
		case 'rename':
			nicebackgrounds_rename_set( $_POST['set_id'], sanitize_text_field( $_POST['value'] ) );
			break;
		case 'clone':
			nicebackgrounds_clone_set( $_POST['set_id'], sanitize_text_field( $_POST['value'] ) );
			break;
		case 'delete':
			nicebackgrounds_delete_set( $_POST['set_id'] );
			break;
	}

	return array( 'success' => true, 'result' => true );
}

/**
 * Handles responding to the use of the 'Create set' link in the top right corner of the admin page.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_new_set() {
	nicebackgrounds_create_set( sanitize_text_field( $_POST['value'] ) );

	return array( 'success' => true, 'result' => true );
}

/**
 * Returns the ajax result that contains the HTML of the Reserves panel in a collection admin screen.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_load_reserves() {
	nicebackgrounds_update_set_reserves( $_POST['set_id'] );
	$reserves = nicebackgrounds_get_set_reserves( $_POST['set_id'] );
	if ( empty( $reserves ) ) {
		$html = nicebackgrounds_icon( 'warning' );
		$html .= __( 'There are no reserves in this set.', 'nicebackgrounds' );
		$html = nicebackgrounds_wrap( $html );
	} else {
		$html = '';
		$set  = nicebackgrounds_get_set( $_POST['set_id'] );
		foreach ( $reserves as $reserve ) {
			$html .= nicebackgrounds_thumb( $_POST['set_id'], $set, $reserve );
		}
	}

	return array( 'success' => true, 'result' => $html );
}

/**
 * Handles adding an image from the Reserves pool and returns the thumbnail code.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_save_reserves() {
	return nicebackgrounds_reserves_to_full( $_POST['set_id'], sanitize_text_field( $_POST['file'] ) );
}

/**
 * Handles permanently deleting a collection item from the Reserves pool.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_remove_reserves() {
	nicebackgrounds_remove_reserves( $_POST['set_id'], $_POST['file'] );

	return array( 'success' => true );
}

/**
 * Handles saving changes to Picklist options.
 *
 * @return array The Ajax response as required by the JS that fires off the request.
 */
function nicebackgrounds_ajax_save_picklist_option() {
	$defaults = array();
	if ( function_exists( 'nicebackgrounds_' . $_POST['option'] . '_defaults' ) ) {
		$defaults = call_user_func( 'nicebackgrounds_' . $_POST['option'] . '_defaults' );

		$_POST['value'] = sanitize_text_field( $_POST['value'] );
		$options        = get_option( 'nicebackgrounds_' . $_POST['option'], $defaults );
		if ( ! $_POST['remove'] && ! in_array( $_POST['value'], $options ) ) {
			$options[] = $_POST['value'];
		} else if ( $_POST['remove'] && ( $key = array_search( $_POST['value'], $options ) ) !== false ) {
			unset( $options[ $key ] );
		}
		update_option( 'nicebackgrounds_' . $_POST['option'], $options );

		return array( 'success' => true );
	}

	// If nicebackgrounds_{option}_defaults() isn't a function, then that is a tell-tale sign that an invalid option was
	// provided.
	return array( 'success' => false );
}

