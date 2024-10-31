<?php
/**
 * The functions in this file relate to operations on Nice Backgrounds Sets.  Most of this code should probably be
 * rewritten as a class, perhaps when sets are moved into their own db tables.
 */

/**
 * Creates the directories required by a set, and removes unneeded ones.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return bool Indicating success (regarding directory creation).
 */
function nicebackgrounds_prepare_set( $set_id, $set ) {
	$dirs = nicebackground_get_set_dirs( $set_id, $set );

	// Add missing dirs.
	foreach ( $dirs as $dir ) {
		$img_dir = nicebackgrounds_image_path( $set_id, $dir );
		$res     = nicebackgrounds_mkdir( $img_dir );
		if ( true !== $res ) {
			return $res;
		}
	}

	// Remove unneeded dirs - failures here don't affect return value.
	$iterator = new FilesystemIterator( nicebackgrounds_set_dir( $set_id ) );
	foreach ( $iterator as $fileinfo ) {
		if ( $fileinfo->isDir() && ! in_array( $iterator->getBasename(), $dirs ) ) {
			nicebackgrounds_rmdir( nicebackgrounds_image_path( $set_id, $iterator->getBasename() ) );
		}
	}

	return true;
}

/**
 * Retrieves a list of set subdirectories.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return array A list of subdirectory names.
 */
function nicebackground_get_set_dirs( $set_id, $set ) {
	$dirs   = nicebackgrounds_adaptive_sizes( $set_id, $set );
	$dirs[] = 'full';
	$dirs[] = 'preloads';

	return $dirs;
}

/**
 * Deletes a set from the WP options.
 *
 * @param string $set_id The set identifier.
 */
function nicebackgrounds_delete_set_from_options( $set_id ) {
	$sets         = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );
	$all_images   = get_option( 'nicebackgrounds_images', nicebackgrounds_images_defaults() );
	$all_reserves = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );
	if ( isset( $sets[ $set_id ] ) ) {
		unset( $sets[ $set_id ] );
	}
	if ( isset( $all_images[ $set_id ] ) ) {
		unset( $all_images[ $set_id ] );
	}
	if ( isset( $all_reserves[ $set_id ] ) ) {
		unset( $all_reserves[ $set_id ] );
	}
	update_option( 'nicebackgrounds_sets', $sets );
	update_option( 'nicebackgrounds_images', $all_images );
	update_option( 'nicebackgrounds_reserves', $all_reserves );
}

/**
 * Deletes a set.
 *
 * @param string $set_id The set identifier.
 */
function nicebackgrounds_delete_set( $set_id ) {
	nicebackgrounds_delete_set_from_options( $set_id );
	nicebackgrounds_rmdir( nicebackgrounds_set_dir( $set_id ) );
}

/**
 * Renames a set.
 *
 * @param string $set_id The set identifier.
 * @param string $new_name The new name of the set.
 */
function nicebackgrounds_rename_set( $set_id, $new_name ) {
	$set      = nicebackgrounds_get_set( $set_id );
	$images   = nicebackgrounds_get_set_images( $set_id );
	$reserves = nicebackgrounds_get_set_reserves( $set_id );

	nicebackgrounds_delete_set_from_options( $set_id );

	$new_id = nicebackgrounds_create_set( $new_name, $set, $images, $reserves );
	rename( nicebackgrounds_set_dir( $set_id ), nicebackgrounds_set_dir( $new_id ) );
}

/**
 * Clones a set.
 *
 * @param string $set_id The set identifier.
 * @param string $new_name The new name of the set.
 */
function nicebackgrounds_clone_set( $set_id, $new_name ) {
	$set      = nicebackgrounds_get_set( $set_id );
	$images   = nicebackgrounds_get_set_images( $set_id );
	$reserves = nicebackgrounds_get_set_reserves( $set_id );

	$new_id = nicebackgrounds_create_set( $new_name, $set, $images, $reserves );
	nicebackgrounds_copydir( nicebackgrounds_set_dir( $set_id ), nicebackgrounds_set_dir( $new_id ) );
}


/**
 * Clears the set cache by removing all the generated adaptive images.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 */
function nicebackgrounds_clear_adaptive_sizes( $set_id, $set ) {
	$dirs = nicebackgrounds_adaptive_sizes( $set_id, $set );

	foreach ( $dirs as $dir ) {
		$img_dir  = nicebackgrounds_image_path( $set_id, $dir );
		$iterator = new DirectoryIterator( $img_dir );
		foreach ( $iterator as $fileinfo ) {
			if ( $fileinfo->isFile() && $fileinfo->getFilename() != 'index.php' ) {
				wp_delete_file( $img_dir . $iterator->current() );
			}
		}
	}
}

/**
 * Clears the cache of one particular image by removing the generated adaptive versions of that image.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param string $filename The filename of the image to be cleared.
 */
function nicebackgrounds_clear_adaptive_sizes_specific( $set_id, $set, $filename ) {
	$dirs = nicebackgrounds_adaptive_sizes( $set_id, $set );

	foreach ( $dirs as $dir ) {
		$img_dir = nicebackgrounds_image_path( $set_id, $dir );
		wp_delete_file( $img_dir . $filename );
	}
}

/**
 * Retreives a set's settings and state.
 *
 * @param string $set_id The set identifier.
 *
 * @return array $set The associative array representing the set settings and state.
 */
function nicebackgrounds_get_set( $set_id ) {
	$sets = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );

	return $sets[ $set_id ];
}

/**
 * Retrieves a set's images.
 *
 * @param string $set_id The set identifier.
 *
 * @return array $set The associative array representing the set images.
 */
function nicebackgrounds_get_set_images( $set_id ) {
	$all_images = get_option( 'nicebackgrounds_images', nicebackgrounds_images_defaults() );

	return $all_images[ $set_id ];
}

/**
 * Retrieves a set's reserves.
 *
 * @param string $set_id The set identifier.
 *
 * @return array $set The associative array representing the set reserves.
 */
function nicebackgrounds_get_set_reserves( $set_id ) {
	$all_reserves = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );

	return $all_reserves[ $set_id ];
}

/**
 * Finds orphaned files in the full images directory and adds them to reserves.
 *
 * This bypasses any file verification or mime checks, which may make it impossible to actually add these in the set.
 * At least it allows the stray items to be deleted via the admin screen though.
 *
 * @param string $set_id The set identifier.
 */
function nicebackgrounds_update_set_reserves( $set_id ) {
	$all_reserves = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );
	$reserves     = &$all_reserves[ $set_id ];
	$images       = nicebackgrounds_get_set_images( $set_id );

	$dir      = nicebackgrounds_image_path( $set_id, 'full' );
	$iterator = new DirectoryIterator( $dir );
	foreach ( $iterator as $fileinfo ) {
		if ( $fileinfo->isFile() && $fileinfo->getFilename() != 'index.php' ) {
			$found = false;
			foreach ( $images as $image ) {
				if ( $image['file'] === $fileinfo->getFilename() ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				foreach ( $reserves as $reserve ) {
					if ( $reserve['file'] === $fileinfo->getFilename() ) {
						$found = true;
						break;
					}
				}
			}
			if ( ! $found ) {
				list( $width, $height ) = @GetImageSize( $fileinfo->getPathname() );
				$reserves[] = array(
					'w'    => $width,
					'h'    => $height,
					'file' => $fileinfo->getFilename(),
				);
			}
		}
	}

	update_option( 'nicebackgrounds_reserves', $all_reserves );
}

/**
 * Removes an item from the set's reserves.
 *
 * @param string $set_id The set identifier.
 * @param string $file_path_or_unsplash A file path or Unsplash URL by which to identify the item.
 */
function nicebackgrounds_remove_reserves( $set_id, $file_path_or_unsplash ) {
	$all_reserves          = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );
	$reserves              = &$all_reserves[ $set_id ];
	$dir                   = nicebackgrounds_image_path( $set_id, 'full' );
	$url_parts             = explode( '?', $file_path_or_unsplash );
	$file_path_or_unsplash = $url_parts[0];
	foreach ( $reserves as $key => $image ) {
		if ( ( ! empty( $image['unsplash'] ) && $image['unsplash'] === $file_path_or_unsplash ) || basename( $file_path_or_unsplash ) === $image['file'] ) {
			unset( $reserves[ $key ] );
			wp_delete_file( $dir . $image['file'] );
			break;
		}
	}
	update_option( 'nicebackgrounds_reserves', $all_reserves );
}

/**
 * Removes an item from the set's images.
 *
 * @param string $set_id The set identifier.
 * @param string $file_path_or_unsplash A file path or Unsplash URL by which to identify the item.
 */
function nicebackgrounds_remove_image( $set_id, $file_path_or_unsplash ) {
	$all_images            = get_option( 'nicebackgrounds_images', nicebackgrounds_images_defaults() );
	$images                = &$all_images[ $set_id ];
	$set                   = nicebackgrounds_get_set( $set_id );
	$all_reserves          = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );
	$reserves              = &$all_reserves[ $set_id ];
	$url_parts             = explode( '?', $file_path_or_unsplash );
	$file_path_or_unsplash = $url_parts[0];
	foreach ( $images as $key => $image ) {
		if ( ( ! empty( $image['unsplash'] ) && $image['unsplash'] === $file_path_or_unsplash ) || basename( $file_path_or_unsplash ) === $image['file'] ) {
			$reserves[] = $image;
			unset( $images[ $key ] );
			// Also remove the adaptive versions.
			nicebackgrounds_clear_adaptive_sizes_specific( $set_id, $set, basename( $file_path_or_unsplash ) );
			break;
		}
	}
	update_option( 'nicebackgrounds_images', $all_images );
	update_option( 'nicebackgrounds_reserves', $all_reserves );
}

/**
 * Checks the file system for missing adaptive images and generates them as needed.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param int $timelimit (Optional) A time limit in seconds, if execution exceeds this limit it will not generate
 * another image.  Defaults to 10.
 *
 * @return bool Indicating whether the function was able to generate all the missing images before hitting the time limit.
 */
function nicebackgrounds_find_and_generate_missing_adaptives( $set_id, $set, $timelimit = 10 ) {
	$sizes      = nicebackgrounds_adaptive_sizes( $set_id, $set );
	$iterator   = new DirectoryIterator( nicebackgrounds_image_path( $set_id, 'full' ) );
	$start_time = microtime( true );
	foreach ( $iterator as $fileinfo ) {
		if ( $fileinfo->isFile() && $fileinfo->getFilename() != 'index.php' ) {
			$source_file = nicebackgrounds_image_path( $set_id, 'full', $iterator->current() );
			foreach ( $sizes as $size ) {
				$cache_file = nicebackgrounds_image_path( $set_id, $size, $fileinfo->getFilename() );

				if ( ! file_exists( $cache_file ) ) {
					$file_type = nicebackgrounds_check_mime( $source_file );
					$res       = nicebackgrounds_adapt_image( $source_file, $cache_file, $size, 80, true, $file_type );
					if ( $res && file_exists( $cache_file ) ) {
						// Use the resized image for efficiency.
						$source_file = $cache_file;
					}
				}
				if ( microtime( true ) - $start_time > 10 ) {
					if ( true === WP_DEBUG ) {
						error_log( 'Generating missing adaptives has exceeded time limit.  Start time:' . $start_time . ' End time:' . microtime( true ) );
					}

					// Leave it for now.
					return false;
				}
			}
		}
	}

	return true;
}

/**
 * Generates the adaptive versions of an image.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param array $image The associative array representing a set image.
 * @param int $timelimit (Optional) A time limit in seconds, if execution exceeds this limit it will not generate
 * another image.  Defaults to 10.
 * @param bool $preload (Optional) Whether only a thumbnail size is required. Defaults to false.
 *
 * @return bool Indicating whether the function was able to generate all the missing images before hitting the time limit.
 */
function nicebackgrounds_generate_image_adaptive_sizes( $set_id, $set, $image, $timelimit = 10, $preload = false ) {

	$start_time  = microtime( true );
	$source_file = nicebackgrounds_image_path( $set_id, ( $preload ? 'preloads' : 'full' ), $image['file'] );

	if ( $preload ) {
		$sizes = array( nicebackgrounds_thumb_size( $set_id, $set ) );
	} else {
		$sizes = nicebackgrounds_adaptive_sizes( $set_id, $set );
	}
	foreach ( $sizes as $size ) {
		$cache_file = nicebackgrounds_image_path( $set_id, $size, $image['file'] );
		$res        = nicebackgrounds_adapt_image( $source_file, $cache_file, $size, 80, true, $image['mime'] );
		if ( microtime( true ) - $start_time > $timelimit ) {
			if ( true === WP_DEBUG ) {
				error_log( 'Generating adaptives has exceeded time limit.  Start time:' . $start_time . ' End time:' . microtime( true ) );
			}
			// Leave it for now.
			return false;
		}
		if ( $res ) {
			// Use the resized image for efficiency.
			$source_file = $cache_file;
		}
	}

	return true;
}

/**
 * Gets the best URL available for an image given the supplied parameters.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param array $image The associative array representing a set image.
 * @param int $size The desired width of the image.
 *
 * @return string|false The image URL or false if nothing could be found.
 */
function nicebackgrounds_get_image_src( $set_id, $set, $image, $size ) {
	if ( ! empty( $image['unsplash'] ) && ! empty( $set['cdn'] ) ) {
		return $image['unsplash'] . '?w=' . $size;
	}
	$sizes      = array_reverse( nicebackgrounds_adaptive_sizes( $set_id, $set ) );
	$found_size = null;
	foreach ( $sizes as $s ) {
		if ( $s < $size ) {
			continue;
		}
		if ( file_exists( nicebackgrounds_image_path( $set_id, $s, $image['file'] ) ) ) {
			return nicebackgrounds_image_url( $set_id, $s, $image['file'] );
		}
	}

	return nicebackgrounds_get_full_image_src( $set_id, $set, $image );
}

/**
 * Gets the URL to the full sized version of an image.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 * @param array $image The associative array representing a set image.
 *
 * @return string|false The image URL or false if nothing could be found.
 */
function nicebackgrounds_get_full_image_src( $set_id, $set, $image ) {
	if ( ! empty( $image['unsplash'] ) && ! empty( $set['cdn'] ) ) {
		return $image['unsplash'] . '?w=' . nicebackgrounds_largest_size( $set_id, $set );
	}
	if ( file_exists( nicebackgrounds_image_path( $set_id, 'full', $image['file'] ) ) ) {
		return nicebackgrounds_image_url( $set_id, 'full', $image['file'] );
	}
	return false;
}

/**
 * Retrieves the path or URL to a set directory.
 *
 * @param string $set_id The set identifier.
 * @param bool $url (Optional) Whether to get a URL as opposed to a path. Defaults to false.
 *
 * @return string The URL or path.
 */
function nicebackgrounds_set_dir( $set_id, $url = false ) {
	$uploads = wp_upload_dir();

	return ( $url ? $uploads['baseurl'] : $uploads['basedir'] ) . '/nicebackgrounds/' . $set_id . '/';
}

/**
 * Retrieves a path to a set subdirectory or a file within such a subdirectory.
 *
 * @param string $set_id The set identifier.
 * @param string $subdir The name of the subdirectory.
 * @param string $filename (Optional) A filename of a file in the subdirectory.
 *
 * @return string The path.
 */
function nicebackgrounds_image_path( $set_id, $subdir, $filename = null ) {
	return nicebackgrounds_set_dir( $set_id ) . $subdir . '/' . $filename;
}

/**
 * Retrieves a URL to a set subdirectory or a file within such a subdirectory.
 *
 * @param string $set_id The set identifier.
 * @param string $subdir The name of the subdirectory.
 * @param string $filename (Optional) A filename of a file in the subdirectory.
 *
 * @return string The URL.
 */
function nicebackgrounds_image_url( $set_id, $subdir, $filename = null ) {
	return nicebackgrounds_set_dir( $set_id, true ) . $subdir . '/' . $filename;
}

/**
 * Persists the array representing a set image to the WP options storage.
 *
 * @param string $set_id The set identifier.
 * @param array $image The associative array representing a set image.
 */
function nicebackgrounds_store_image( $set_id, $image ) {
	$all_images              = get_option( 'nicebackgrounds_images', nicebackgrounds_images_defaults() );
	$all_images[ $set_id ][] = $image;
	update_option( 'nicebackgrounds_images', $all_images );
}

/**
 * Gets the smallest size of adaptive images.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return int The size.
 */
function nicebackgrounds_thumb_size( $set_id, $set ) {
	$sizes = nicebackgrounds_adaptive_sizes( $set_id, $set );

	return min( $sizes );
}

/**
 * Gets the largest size of adaptive images.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return int The size.
 */
function nicebackgrounds_largest_size( $set_id, $set ) {
	$sizes = nicebackgrounds_adaptive_sizes( $set_id, $set );

	return max( $sizes );
}

/**
 * Gets the adaptive sizes for a set, if none exist then use the default sizes.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return array A list of the adaptive sizes.
 */
function nicebackgrounds_adaptive_sizes( $set_id, $set ) {
	return ! empty( $set['sizes'] ) ? $set['sizes'] : nicebackgrounds_sizes_defaults();
}

/**
 * Processes a file and returns the $image array. Deletes the file if there's a problem.
 *
 * @param string $dir The file path to the file's directory.
 * @param string $filename The filename of the file.
 * @param string $unsplash_cdn_url (Optional) The URL to the Unsplash CDN, if applicable.
 *
 * @return array|string The associative array representing a set image if successful or otherwise an error message.
 *
 */
function nicebackgrounds_save_image( $dir, $filename, $unsplash_cdn_url = null ) {
	$file_type = nicebackgrounds_check_mime( $dir . $filename );
	if ( ! $file_type ) {
		wp_delete_file( $dir . $filename );

		return __( 'Invalid file type.', 'nicebackgrounds' );
	}
	$new_filename = str_replace( '.nicebackgrounds_tmp_ext', '.' . $file_type, $filename );
	if ( $new_filename != $filename ) {
		$rename = rename( $dir . $filename, $dir . $new_filename );
		if ( ! $rename ) {
			wp_delete_file( $dir . $filename );

			return __( "Sorry that file can't be saved, you'll have to upload it.", 'nicebackgrounds' );
		}
		$filename = $new_filename;
	}

	if ( ! nicebackgrounds_verify_file( $dir . $filename ) ) {
		wp_delete_file( $dir . $filename );

		return __( "Sorry that file can't be verified, you'll have to upload it.", 'nicebackgrounds' );
	}

	list( $width, $height ) = getimagesize( $dir . $filename );

	if ( ! nicebackgrounds_estimate_image_memory( $file_type, $width, $height ) ) {
		return __( 'File size and memory limit do not jive.', 'nicebackgrounds' );
	}

	// Make image array.
	$image = array(
		'file' => $filename,
		'w'    => $width,
		'h'    => $height,
		'mime' => $file_type,
	);
	if ( ! empty( $unsplash_cdn_url ) ) {
		$image['unsplash'] = $unsplash_cdn_url;
	}

	return $image;
}

/**
 * Creates the Ajax response for saving an image.
 *
 * @param string $set_id The set identifier.
 * @param array $image The associative array representing a set image.
 * @param bool $ret_img (Optional) If true the response contains the image array instead of thumbnail code. Defaults to false.
 *
 * @return array Ajax response for a saved image.
 */
function nicebackgrounds_saved_image_result( $set_id, $image, $ret_img = false ) {
	$set = nicebackgrounds_get_set( $set_id );

	nicebackgrounds_store_image( $set_id, $image );

	// Generate sizes.
	nicebackgrounds_generate_image_adaptive_sizes( $set_id, $set, $image, 10 );

	if ( $ret_img ) {
		$result = $image;
	} else {
		// Get a thumbnail for the result.
		$result = nicebackgrounds_thumb( $set_id, $set, $image );
	}

	return array( 'success' => true, 'result' => $result, 'message' => nicebackgrounds_success() );
}

/**
 * Converts a preloaded image to a set image.
 *
 * @param string $set_id The set identifier.
 * @param string $preload_path The path to the preloaded image.
 *
 * @return array An Ajax response containing the result of nicebackgrounds_saved_image_result() or an error message.
 */
function nicebackgrounds_preload_to_full( $set_id, $preload_path ) {
	$preload_dir = nicebackgrounds_image_path( $set_id, 'preloads' );
	$dir         = nicebackgrounds_image_path( $set_id, 'full' );
	$filename    = nicebackgrounds_prepare_save_path( $dir, $preload_path );
	rename( $preload_dir . basename( $preload_path ), $dir . $filename );

	$image = nicebackgrounds_save_image( $dir, $filename );

	if ( ! is_array( $image ) ) {
		// It's not an image, it's an error string.
		return array( 'success' => false, 'message' => $image );
	}

	return nicebackgrounds_saved_image_result( $set_id, $image );
}

/**
 * Converts a reserves image to a set image.
 *
 * @param string $set_id The set identifier.
 * @param string $file_path_or_unsplash The path to the reserves file or the Unsplash CDN URL by which to identify the image.
 *
 * @return array An Ajax response containing the result of nicebackgrounds_saved_image_result() or an error message.
 */
function nicebackgrounds_reserves_to_full( $set_id, $file_path_or_unsplash ) {
	$dir                   = nicebackgrounds_image_path( $set_id, 'full' );
	$all_images            = get_option( 'nicebackgrounds_images', nicebackgrounds_images_defaults() );
	$images                = &$all_images[ $set_id ];
	$set                   = nicebackgrounds_get_set( $set_id );
	$all_reserves          = get_option( 'nicebackgrounds_reserves', nicebackgrounds_reserves_defaults() );
	$reserves              = &$all_reserves[ $set_id ];
	$return                = array( 'success' => false );
	$url_parts             = explode( '?', $file_path_or_unsplash );
	$file_path_or_unsplash = $url_parts[0];
	foreach ( $reserves as $key => $image ) {
		if ( ( ! empty( $image['unsplash'] ) && $image['unsplash'] === $file_path_or_unsplash ) || basename( $file_path_or_unsplash ) === $image['file'] ) {

			// Resave.
			$unsplash = ! empty( $image['unsplash'] ) ? $image['unsplash'] : false;
			$image    = nicebackgrounds_save_image( $dir, $image['file'], $unsplash );

			if ( ! is_array( $image ) ) {
				// It's not an image, it's an error string.
				return array( 'success' => false, 'message' => $image );
			}

			unset( $reserves[ $key ] );
			$images[] = $image;

			$return = nicebackgrounds_saved_image_result( $set_id, $image );

			break;
		}
	}
	if ( ! empty( $return['success'] ) ) {
		update_option( 'nicebackgrounds_images', $all_images );
		update_option( 'nicebackgrounds_reserves', $all_reserves );
	}

	return $return;
}

/**
 * Fetch an image from a remote URI and provide Ajax response which typically contains the thumbnail code.
 *
 * @param string $set_id The set identifier.
 * @param string $url The URL of the file to fetch.
 * @param bool $preload (Optional) Whether this fetch is being done for a preload. Defaults to false.
 * @param string $unsplash_cdn_url (Optional) The URL to the Unsplash CDN, if applicable.
 * @param bool $ret_img (Optional) If true (and $preload is false) the response contains the image array instead of thumbnail code. Defaults to false.
 *
 * @return array An Ajax response containing the result of nicebackgrounds_saved_image_result() with a success message, or an error message.
 */
function nicebackgrounds_leech( $set_id, $url, $preload = false, $unsplash_cdn_url = null, $ret_img = false ) {
	$dir      = nicebackgrounds_image_path( $set_id, ( $preload ? 'preloads' : 'full' ) );
	$filename = nicebackgrounds_prepare_save_path( $dir, parse_url( $url, PHP_URL_PATH ) );

	if ( ! nicebackgrounds_curl_pull( $url, $dir, $filename ) ) {
		return array( 'success' => false, 'message' => __( 'Could not fetch file.', 'nicebackgrounds' ) );
	}

	$image = nicebackgrounds_save_image( $dir, $filename, $unsplash_cdn_url );

	if ( ! is_array( $image ) ) {
		// It's not an image, it's an error string.
		return array( 'success' => false, 'message' => $image );
	}

	if ( ! $preload ) {
		return nicebackgrounds_saved_image_result( $set_id, $image, $ret_img );
	} else {
		$set    = nicebackgrounds_get_set( $set_id );
		$result = nicebackgrounds_preload_thumb( $set_id, $set, $image );
	}

	return array( 'success' => true, 'result' => $result, 'message' => nicebackgrounds_success() );
}

/**
 * Delete the preloaded file corresponding to the filename of supplied path.
 *
 * @param string $set_id The set identifier.
 * @param string $file_path The path to the preloaded file.
 *
 * @return bool Currently always returns true.
 */
function nicebackgrounds_remove_preload( $set_id, $file_path ) {
	$set         = nicebackgrounds_get_set( $set_id );
	$preload_dir = nicebackgrounds_image_path( $set_id, 'preloads' );
	$thumb_dir   = nicebackgrounds_image_path( $set_id, nicebackgrounds_thumb_size( $set_id, $set ) );
	$filename    = basename( $file_path );
	wp_delete_file( $preload_dir . $filename );
	wp_delete_file( $thumb_dir . $filename );

	return true;
}


/**
 * Given a size and set, get the nearest size in sizes.
 *
 * @param string $set_id The set identifier.
 * @param array $set Associative array representing the set object.
 * @param int $size The requested size.
 * @param bool $must_be_larger Whether the returned value must be larger than $size.
 *
 * @return int The configured adaptive size in the set that is nearest to $size.
 */
function nicebackgrounds_nearest_size( $set_id, $set, $size, $must_be_larger = false ) {
	$sizes   = $set['sizes'];
	$nearest = null;
	foreach ( $sizes as $s ) {
		if ( ( ! $must_be_larger || $s >= $size ) && ( null === $nearest || abs( $size - $nearest ) > abs( $s - $size ) ) ) {
			$nearest = $s;
		}
	}

	if ( null === $nearest ) {
		$nearest = nicebackgrounds_largest_size( $set_id, $set );
	}

	return $nearest;
}

