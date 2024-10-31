<?php
/**
 * The functions in this file are helpers that don't really fit in anywhere else.
 */

/**
 * Initialise options.
 *
 * In order for Ajax one-at-a-time saving to work, all options have to be saved once with defaults before the user performs any
 * interaction with the admin form.  This function serves to perform these tasks and is called once each time the admin
 * page is loaded.
 */
function nicebackgrounds_init_options() {
	$options = nicebackgrounds_option_keys();
	foreach ( $options as $o ) {
		$defaults = array();
		if ( function_exists( 'nicebackgrounds_' . $o . '_defaults' ) ) {
			$defaults = call_user_func( 'nicebackgrounds_' . $o . '_defaults' );
		}
		$value = get_option( 'nicebackgrounds_' . $o, $defaults );

		// Force defaults if all options are emptied.
		// Note: to reset the plugin if it has bad data make the following line "if ( true || ...".
		if ( empty( $value ) ) {
			$value = $defaults;
		}

		// Save immediately so that one-at-a-time ajax saving can work.
		update_option( 'nicebackgrounds_' . $o, $value );
	}
}

function nicebackgrounds_debug_options() {
	$ret     = '';
	$options = nicebackgrounds_option_keys();
	foreach ( $options as $o ) {
		$defaults = array();
		if ( function_exists( 'nicebackgrounds_' . $o . '_defaults' ) ) {
			$defaults = call_user_func( 'nicebackgrounds_' . $o . '_defaults' );
		}
		$value = get_option( 'nicebackgrounds_' . $o, $defaults );

		$ret .= $o . "\n";
		$ret .= str_repeat( '=', strlen( $o ) ) . "\n";
		$ret .= print_r( $value, true );
		$ret .= "\n\n";
	}

	return $ret;
}

/**
 * Prepares a string to be used as a CSS identifier, such as a class name.
 *
 * @param string $id The string to prepare.
 *
 * @return string The prepared identifier.
 */
function nicebackgrounds_id( $id ) {
	// Replace certain chars with hyphens and then filter according to https://www.w3.org/TR/CSS21/syndata.html#characters
	$id = strtolower( preg_replace(
		'/[^\x{002D}\x{0030}-\x{0039}\x{0041}-\x{005A}\x{005F}\x{0061}-\x{007A}\x{00A1}-\x{FFFF}]/u',
		'',
		strtr( $id, array( ' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => '' ) )
	) );
	// Disallow empty string or id beginning with non-alpha.
	if ( empty( $id ) || ! ctype_alpha( $id[0] ) ) {
		$id = 'n' . $id;
	}

	return $id;
}

/**
 * Performs a curl operation that saves a remote file to the file system.
 *
 * @param string $url The URL of the remote resource.
 * @param string $dest The directory in the file system in which to write the file.
 * @param string $filename The filename to use when writing the file.
 *
 * @return bool Indicating success.
 *
 * @todo Is Wp_Http:request() capable of this functionality?
 */
function nicebackgrounds_curl_pull( $url, $dest, $filename ) {
	//$ua       = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.103 Safari/537.36;";
	$filename = $dest . '/' . $filename;
	$ch       = curl_init( $url );
	$fp       = fopen( $filename, 'wb' );
	if ( false !== $ch && false !== $fp ) {
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, 'cookie.txt' );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_exec( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		fclose( $fp );
		curl_close( $ch );
		if ( 200 !== $httpCode ) {
			return false;
		}

		return true;
	}

	return false;
}

/**
 * Recursively creates directories and adds a blank index.php to prevent snooping.
 *
 * @param string $path The full path to the directory to be created.
 *
 * @return true|string True on success or otherwise an error message.
 * @todo Reimplement/replace with wp_mkdir_p().
 */
function nicebackgrounds_mkdir( $path ) {
	if ( ! file_exists( $path ) ) {
		$res = @mkdir( $path, 0755, true );
		if ( ! $res ) {
			return __( 'Could not make directory:', 'nicebackgrounds' ) . ' ' . $path;
		}
		if ( ! is_writable( $path ) ) {
			return __( 'Cannot write to directory:', 'nicebackgrounds' ) . ' ' . $path;
		}
	}
	$file = $path . '/index.php';
	if ( ! file_exists( $file ) ) {
		$res = file_put_contents( $file, '<' . '?php' );
		if ( ! $res ) {
			return __( 'Could not create file:', 'nicebackgrounds' ) . ' ' . $file;
		}
	}

	return true;
}

/**
 * Determines whether one string ends in another.
 *
 * @param string $haystack The string in which to search.
 * @param string $needle The string to find.
 *
 * @return bool Indicating whether $haystack ends in $needle.
 */
function nicebackgrounds_ends_with( $haystack, $needle ) {
	// Search forward starting from end minus needle length characters.
	return $needle === "" || ( ( $temp = strlen( $haystack ) - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== false );
}

/**
 * Builds a URL to Unsplash's Source API.
 *
 * @param array $search An associative array containing the form values of an Unsplash search form and additionally
 * supports a size parameter which is a string like "200x200".  For a random full sized image no search parameters are
 * required.  The supported search parameters are size, filters, fixed, featured, keywords, user, category.  The key
 * in the associative array need only end with the parameter name, allowing for handling prefixes.
 *
 * @return string The URL.
 */
function nicebackgrounds_build_unsplash_url( $search ) {
	$unsplash_url = 'https://source.unsplash.com';
	$append       = '';

	$keys = array( 'size', 'filters', 'fixed', 'featured', 'keywords', 'user', 'category' );
	foreach ( $search as $key => $value ) {
		foreach ( $keys as $k ) {
			if ( nicebackgrounds_ends_with( $key, $k ) ) {
				$search[ $k ] = $value;
			}
		}
	}

	if ( ! empty( $search['filters'] ) ) {
		if ( 'likes' === $search['filters'] && ! empty( $search['user'] ) ) {
			$append .= '/user/' . $search['user'] . '/likes';
		} else if ( 'user' === $search['filters'] && ! empty( $search['user'] ) ) {
			$append .= '/user/' . $search['user'];
		} else if ( 'category' === $search['filters'] && ! empty( $search['category'] ) ) {
			$append .= '/category/' . $search['category'];
		}
	}

	if ( ! empty( $search['featured'] ) ) {
		$append .= '/featured';
	}

	if ( ! empty( $search['size'] ) ) {
		$append .= '/' . $search['size'];
	}

	if ( ! empty( $search['fixed'] ) && in_array( $search['fixed'], array( 'weekly', 'daily' ) ) ) {
		$append .= '/' . $search['fixed'];
	}

	if ( ! empty( $search['keywords'] ) ) {
		$append .= '/?' . $search['keywords'];
	}

	return $unsplash_url . ( ! empty( $append ) ? $append : '/random' );
}

/**
 * Resolves a 301 or 302 redirect so that CURL can work properly.
 *
 * @param string $url The URL to resolve.
 *
 * @return bool|string The resolved URL or false on failure.
 *
 * @todo Will the Wp_Http class perform this?
 */
function nicebackgrounds_resolve_redirects( $url ) {
	$furl    = false;
	$headers = get_headers( $url );
	if ( preg_match( '/^HTTP\/\d\.\d\s+(301|302)/', $headers[0] ) ) {
		foreach ( $headers as $value ) {
			if ( "location:" === substr( strtolower( $value ), 0, 9 ) ) {
				$furl = trim( substr( $value, 9, strlen( $value ) ) );
			}
		}
	}

	return ( $furl ) ? $furl : $url;
}

/**
 * Resolves an Unsplash Source API URL to a URL that can be reusably used to get the same image.
 *
 * @param array $search The $search param as needed by nicebackgrounds_build_unsplash_url().
 * @param int $max_width (Optional) If set will return the URL with an argument to restrict the width.
 * @param int $max_height (Optional) If set will return the URL with an argument to restrict the height.
 *
 * @return string The URL.
 */
function nicebackgrounds_resolve_unsplash_url( $search, $max_width = null, $max_height = null ) {
	$url_parts = explode( '?', nicebackgrounds_resolve_redirects( nicebackgrounds_build_unsplash_url( $search ) ) );
	$params    = array();
	parse_str( ! empty( $url_parts[1] ) ? $url_parts[1] : '', $params );
	if ( isset( $max_width ) ) {
		$params["w"] = $max_width;
	} else {
		unset( $params["w"] );
	}
	if ( isset( $max_height ) ) {
		$params["h"] = $max_height;
	} else {
		unset( $params["h"] );
	}

	return $url_parts[0] . "?" . build_query( $params );
}

/**
 * Determines whether an upload made via HTTP post is in error.
 *
 * @param array $file The associative array representing the file from the post operation.
 *
 * @return string|false An error string or false if no errors were determined.
 */
function nicebackgrounds_file_upload_error( $file ) {
	if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
		return __( 'Invalid parameters.', 'nicebackgrounds' );
	}
	switch ( $file['error'] ) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_NO_FILE:
			return __( 'No file sent.', 'nicebackgrounds' );
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return __( 'Exceeded filesize limit.', 'nicebackgrounds' );
		default:
			return __( 'Unknown errors.', 'nicebackgrounds' );
	}
	if ( $file['size'] > 100000000 ) {
		return __( 'Exceeded filesize limit.', 'nicebackgrounds' );
	}

	return false;
}

/**
 * Determines whether a file is of an image format supported by this plugin.
 *
 * @param string $file_path The path to the file.
 *
 * @return string|false The file extension of the MIME type, or false if the MIME type is not supported.
 */
function nicebackgrounds_check_mime( $file_path ) {
	$finfo = new finfo( FILEINFO_MIME_TYPE );

	return array_search( $finfo->file( $file_path ), nicebackgrounds_image_types() );
}

/**
 * Returns the filename of a nonexistent file in order to allow writing to the file system without conflicts.
 *
 * @param string $dir The directory where a file is intended to be saved.
 * @param string $filename_without_extension The desired filename of the file.
 * @param string $ext The file extension of the file.  Can be totally bogus to maintain consistency with the file's original name.
 * @param string $mime_ext The file extension of the file's MIME type. Must be based on a legitimate check. Defaults to
 * 'jpg' but generally is not optional.
 *
 * @return string The resolved filename.
 */
function nicebackgrounds_resolve_file_save_path( $dir, $filename_without_extension, $ext, $mime_ext = 'jpg' ) {
	$resolver                   = 2;
	$filename                   = null;
	$filename_without_extension = sanitize_file_name( $filename_without_extension );
	$ext                        = sanitize_file_name( $ext );
	if ( strlen( $filename_without_extension ) === 0 ) {
		$filename_without_extension = uniqid();
	}
	if ( strlen( $ext ) === 0 ) {
		$ext = $mime_ext;
	}
	$filename = $filename_without_extension . "." . $ext;
	while ( file_exists( $dir . $filename ) ) {
		$filename = $filename_without_extension . "." . $resolver . "." . $ext;
		$resolver += 1;
	}

	return $filename;
}

/**
 * Performs additional validation of a written file.
 *
 * Currently only checks that the file is not zero sized.
 *
 * @param string $file_path The path to the file.
 *
 * @return bool Indicating whether the file passed verification.
 */
function nicebackgrounds_verify_file( $file_path ) {
	$filesize = filesize( $file_path );
	if ( ! $filesize ) {
		wp_delete_file( $file_path );

		return false;
	}

	return true;
}

/**
 * Forcibly removed a directory by removing it's contents first.
 *
 * @param string $dir The path to the directory.
 *
 * @return bool Indicating success as per the result of rmdir().
 *
 * @todo Look into using Filesystem API.
 */
function nicebackgrounds_rmdir( $dir ) {
	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	foreach ( $files as $file ) {
		( is_dir( "$dir/$file" ) ) ? nicebackgrounds_rmdir( "$dir/$file" ) : unlink( "$dir/$file" );
	}

	return rmdir( $dir );
}

/**
 * Copies a directory and it's contents.
 *
 * @param string $src The path to the source directory.
 * @param string $dst The path to the destination directory.
 *
 * @todo Look into using Filesystem API.
 */
function nicebackgrounds_copydir( $src, $dst ) {
	$dir = opendir( $src );
	@mkdir( $dst );
	while ( false !== ( $file = readdir( $dir ) ) ) {
		if ( ( $file != '.' ) && ( $file != '..' ) ) {
			if ( is_dir( $src . '/' . $file ) ) {
				nicebackgrounds_copydir( $src . '/' . $file, $dst . '/' . $file );
			} else {
				copy( $src . '/' . $file, $dst . '/' . $file );
			}
		}
	}
	closedir( $dir );
}

/**
 * Returns the name of a nonexistent set to avoid conflicts with existing sets.
 *
 * @param string $new_name The desired name of a set.
 *
 * @return string The resolved set name.
 */
function nicebackgrounds_resolve_set_name( $new_name ) {
	$new_id   = nicebackgrounds_id( $new_name );
	$resolver = 2;
	$test_id  = $new_id;
	while ( ! empty( $sets[ $test_id ] ) ) {
		$test_id = $new_id . ' ' . $resolver;
	}
	if ( $test_id != $new_id ) {
		$new_id   = $test_id;
		$new_name = $new_name . ' ' . $resolver;
	}

	// If something went wrong here, just pick a name automatically and do it again.
	if ( empty( $new_id ) || empty( $new_name ) ) {
		list( $new_id, $new_name ) = nicebackgrounds_resolve_set_name( __( 'New set', 'nicebackgrounds' ) );
	}

	return array( $new_id, $new_name );
}

/**
 * Generates a resized version of an image and saves it in the image cache folder.
 *
 * @param string $source_file The original image to be resized.
 * @param string $cache_file The target file where the resized version will be cached.
 * @param int $new_width The resolution breakpoint at which the given image is to be resized.
 * @param int $jpg_quality The JPEG quality that will be used for resizing the images.
 * @param bool $sharpen Whether to sharpen the resized images or not.
 * @param string $file_type The file extension (should be based on an actual mime check).
 *
 * @return bool Indicating success.
 *
 * Based on adaptive_images_script_generate_image() in plugin "Adaptive Images for WordPress" by nevma.
 *
 */
function nicebackgrounds_adapt_image( $source_file, $cache_file, $new_width, $jpg_quality, $sharpen, $file_type ) {
	// Check and ensure that cache directory is setup OK.
	$cache_path = dirname( $cache_file );
	if ( ! is_dir( $cache_path ) || ! is_writable( $cache_path ) ) {
		error_log( 'Cache directory for image not writeable: ' . $cache_path );

		return false;
	}

	// Setup.
	list( $width, $height ) = @GetImageSize( $source_file );
	if ( ! $width || ! $height ) {
		error_log( 'Dimension is zero sized in image: ' . $source_file );

		return false;
	}
	$ratio       = $height / $width;
	$new_height  = ceil( $new_width * $ratio );
	$destination = @ImageCreateTrueColor( $new_width, $new_height );
	$source      = null;

	// Don't upscale or recompress to same size.
	// This means we rely on the user to not upload an image with poor compression though.
	if ( $new_width >= $width ) {
		return false;
	}
	switch ( $file_type ) {
		case 'png':
			// PNG images generation.
			$source = @ImageCreateFromPng( $source_file );

			// Create a transparent color and fill the blank canvas with it.
			$rbga_color = @ImageColorAllocateAlpha( $destination, 0, 0, 0, 127 );
			@ImageColorTransparent( $destination, $rbga_color );
			@ImageFill( $destination, 0, 0, $rbga_color );

			// Copy source image to destination image with interpolation.
			@ImageCopyResampled( $destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

			// Convert true colour image to pallette image to achieve PNG-8 compression.
			$dither = true;
			@ImageTrueColorToPalette( $destination, $dither, 255 );

			// Save alpha (transparency) of destination image.
			$save_alpha = true;
			@ImageSaveAlpha( $destination, $save_alpha );

			// Disable blending of destination image to allow for alpha (transparency) above.
			$enable_alpha_blending = true;
			@ImageAlphaBlending( $destination, $enable_alpha_blending );
			break;

		case 'gif':
			$source = @ImageCreateFromGif( $source_file );
			// Create a transparent color and fill the blank canvas with it.
			$rbga_color = @ImageColorAllocateAlpha( $destination, 0, 0, 0, 127 );
			@ImageColorTransparent( $destination, $rbga_color );
			@ImageFill( $destination, 0, 0, $rbga_color );

			// Copy source image to destination image with interpolation.
			@ImageCopyResampled( $destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

			// Convert true colour image to pallette image to achieve PNG8 compression.
			$dither = true;
			@ImageTrueColorToPalette( $destination, $dither, 255 );

			// Enable alpha blending of destination image.
			$enable_alpha_blending = true;
			@ImageAlphaBlending( $destination, $enable_alpha_blending );
			break;

		default:
			// JPEG images generation.

			$source = ImageCreateFromJpeg( $source_file );

			// Enable JPEG interlacing.
			ImageInterlace( $destination, true );

			// Interpolates source image to destination image to make it more clear for JPGs.
			@ImageCopyResampled( $destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
			break;
	}

	// Cleanup source image from memory.
	@ImageDestroy( $source );

	// Do sharpening if requested.
	if ( $sharpen && function_exists( 'imageconvolution' ) ) {
		// Normalize width.
		$new_width = $new_width * ( 750.0 / $width );
		// Sharpness factors.
		$a = 52;
		$b = - 0.27810650887573124;
		$c = 0.00047337278106508946;
		// Calculate sharpness factor.
		$result                          = $a + $b * $new_width + $c * $new_width * $new_width;
		$sharpness_factor                = max( round( $result ), 0 );
		$sharpness_transformation_matrix = array(
			array( - 1, - 2, - 1 ),
			array( - 2, $sharpness_factor + 12, - 2 ),
			array( - 1, - 2, - 1 )
		);
		@ImageConvolution( $destination, $sharpness_transformation_matrix, $sharpness_factor, 0 );
	}

	// Save resized image in cache.
	switch ( $file_type ) {
		case 'png':
			$png_compression_level = 6;
			$image_saved           = @ImagePng( $destination, $cache_file, $png_compression_level, PNG_FILTER_NONE );
			break;
		case 'gif':
			$image_saved = @ImageGif( $destination, $cache_file );
			break;
		default:
			$image_saved = @ImageJpeg( $destination, $cache_file, $jpg_quality );
			break;
	}

	// Cleanup destination image from memory.
	@ImageDestroy( $destination );

	// Check if all OK.
	if ( ! $image_saved || ! file_exists( $cache_file ) ) {
		return false;
	}

	return true;
}

/**
 * Returns an arbitrary success message from the preconfigured list of success messages.
 *
 * @return string A success message.
 */
function nicebackgrounds_success() {
	$msgs = nicebackgrounds_success_messages();

	return $msgs[ array_rand( $msgs ) ];
}

/**
 * Estimates whether the system can handle resizing an image.
 *
 * No idea if this works.
 *
 * @param string $file_type The file extension of the image based on a MIME check.
 * @param int $width The width, in pixels, of the image.
 * @param int $height The height, in pixels, of the image.
 *
 * @return bool True or False indicating whether this function guesses a resize will work.
 */
function nicebackgrounds_estimate_image_memory( $file_type, $width, $height ) {
	$channels = ( 'jpg' === $file_type || 'jpeg' === $file_type ) ? 3 : 4;
	$baseline = 50000000;

	return ( $baseline + $width * $height * $channels ) > memory_get_usage();
}

/**
 * Creates an empty thumbnail for use with Unsplash preloads.
 *
 * @param string $unsplash_url The Unsplash CDN URL.
 *
 * @return array An ajax response containing the URL and the thumbnail code.
 */
function nicebackgrounds_unsplash_cdn_result( $unsplash_url ) {
	$thumb = nicebackgrounds_thumb( null, null, array( 'file' => null, 'w' => null, 'h' => null ), '', '', array(
		'thumb',
		'thumb-cdn'
	) );

	return array( 'success' => true, 'cdn' => true, 'result' => $unsplash_url, 'thumb' => $thumb );
}

/**
 * Returns a token representing the current block of time according to a granularity value.
 *
 * @param string $fixed The granularity as a string: 'weekly', 'daily', 'halfdaily', 'hourly'. Other values are assumed
 * to be of a granularity of one minute.
 *
 * @return string The token.
 */
function nicebackgrounds_current_image_time( $fixed ) {
	$date = new DateTime();
	switch ( $fixed ) {
		case 'weekly':
			return $date->format( "W-Y" );
		case 'daily':
			return $date->format( "d-m-Y" );
		case 'halfdaily':
			return $date->format( "a-d-m-Y" );
		case 'hourly':
			return $date->format( "H d-m-Y" );
		default:
			// Use 1 minute so as to not hammer Unsplash and ourselves with adaptive image generation.
			return $date->format( "H:i d-m-Y" );
	}
}

/**
 * Creates a set and stores it in WP options.
 *
 * @param string $new_name The desired name of the set.
 * @param array $set_template (Optional) An existing set array to use as defaults for the set settings and state.
 * @param array $images_template (Optional) An existing set images array to use as defaults for the set images.
 * @param array $reserves_template (Optional) An existing reserves array to use as defaults for the set reserves.
 *
 * @return string The set identifier of the created set.
 */
function nicebackgrounds_create_set( $new_name, $set_template = null, $images_template = null, $reserves_template = null ) {
	$sets         = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );
	$all_images   = get_option( 'nicebackgrounds_images', nicebackgrounds_images_defaults() );
	$all_reserves = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );

	list( $new_id, $new_name ) = nicebackgrounds_resolve_set_name( $new_name );

	$new_set          = ! empty( $set_template ) ? $set_template : nicebackgrounds_empty_set();
	$new_set['title'] = $new_name;
	$sets[ $new_id ]  = $new_set;
	update_option( 'nicebackgrounds_sets', $sets );

	$new_images            = ! empty( $images_template ) ? $images_template : array();
	$all_images[ $new_id ] = $new_images;
	update_option( 'nicebackgrounds_images', $all_images );

	$new_reserves            = ! empty( $reserves_template ) ? $reserves_template : array();
	$all_reserves[ $new_id ] = $new_reserves;
	update_option( 'nicebackgrounds_reserves', $all_reserves );

	return $new_id;
}

/**
 * Determines whether the set id exists.
 *
 * @param $set_id string The set identifier.
 *
 * @return bool Whether the set id exists in the set settings and state data.
 */
function nicebackgrounds_validate_set_id( $set_id ) {
	$sets = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );

	return isset( $sets[ $set_id ] );
}

/**
 * Prepare a file path for writing a file.
 *
 * Returns the filename of a nonexistent file in order to allow writing to the file system without conflicts.  Used as
 * an adapter to nicebackgrounds_resolve_file_save_path() supporting different parameters.
 *
 * @param string $dir The directory where a file is intended to be saved.
 * @param string $path The existing path, filename, or URL to the file from which to get the filename.
 *
 * @return string The resolved filename.
 */
function nicebackgrounds_prepare_save_path( $dir, $path ) {
	$path_parts = pathinfo( $path );
	$ext        = ! empty( $path_parts['extension'] ) ? $path_parts['extension'] : '';
	$filename   = ! empty( $path_parts['filename'] ) ? $path_parts['filename'] : uniqid();

	return nicebackgrounds_resolve_file_save_path( $dir, $filename, $ext, 'nicebackgrounds_tmp_ext' );
}



