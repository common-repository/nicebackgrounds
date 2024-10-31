<?php
/**
 * The functions in this file return HTML as needed by admin.php, specifically related to the Collection section of the
 * admin page.
 */


/**
 *
 */
function nicebackgrounds_collection_upload( $set_id ) {
	$ret = nicebackgrounds_wrap( __( 'Drop files here', 'nicebackgrounds' ), array(
		'upload-instructions',
		'drop-instructions'
	), null, 'h2', null );
	$ret .= nicebackgrounds_p( _x( 'or', 'Uploader: Drop files here - or - Select Files', 'nicebackgrounds' ), array(
		'upload-instructions',
		'drop-instructions'
	), null );
	$ret .= nicebackgrounds_wrap( __( 'Select Files', 'nicebackgrounds' ), array(
		'browser',
		'button',
		'button-hero'
	), array( 'type' => 'button' ), 'button', null );
	$ret .= nicebackgrounds_make_tag( 'input', 'upload-file-input', array(
		'type'     => 'file',
		'name'     => 'files[]',
		'id'       => 'file',
		'multiple' => 'multiple'
	), null );

	$ret = nicebackgrounds_wrap( $ret, 'upload-here', null, 'div', null );

	$ret .= nicebackgrounds_wrap( nicebackgrounds_icon( 'update' ) . __( 'Uploading', 'nicebackgrounds' ) . '&hellip;',
		array( 'add-status', 'add-status-wait' ), null, 'div', null );
	$ret .= nicebackgrounds_wrap( nicebackgrounds_icon( 'thumbs-up' ) . __( 'Done', 'nicebackgrounds' ),
		array( 'add-status', 'add-status-success' ), null, 'div', null );
	$ret .= nicebackgrounds_wrap( nicebackgrounds_icon( 'thumbs-down' ) . __( 'Error', 'nicebackgrounds' ),
		array( 'add-status', 'add-status-error' ), null, 'div', null );
	$ret .= nicebackgrounds_wrap( nicebackgrounds_icon( 'no' ) . __( 'Upload failed', 'nicebackgrounds' ),
		array( 'add-status', 'add-status-failure' ), null, 'div', null );

	$nonce = wp_create_nonce( 'save_upload_' . $set_id );

	return nicebackgrounds_wrap( $ret, 'upload', array( 'data-save-upload' => $nonce ) );
}

/**
 *
 */
function nicebackgrounds_collection_unsplash( $set_id, $set ) {
	$ret = '';

	$preload_dir = nicebackgrounds_image_path( $set_id, 'preloads' );
	$thumb_dir   = nicebackgrounds_image_path( $set_id, nicebackgrounds_thumb_size( $set_id, $set ) );
	$iterator    = new DirectoryIterator( $preload_dir );
	foreach ( $iterator as $fileinfo ) {
		if ( $fileinfo->isFile() && $fileinfo->getFilename() != 'index.php' ) {
			if ( file_exists( $thumb_dir . $fileinfo->getFilename() ) ) {
				list( $width, $height ) = getimagesize( $preload_dir . $fileinfo->getFilename() );
				$image       = array( 'w' => $width, 'h' => $height );
				$preload_url = nicebackgrounds_image_url( $set_id, 'preloads', $fileinfo->getFilename() );
				$thumb_url   = nicebackgrounds_image_url( $set_id, 'preloads', $fileinfo->getFilename() );

				$ret .= nicebackgrounds_thumb( $set_id, $set, $image, $thumb_url, $preload_url );
			} else {
				// Cleanup.
				nicebackgrounds_remove_preload( $set_id, $fileinfo->getFilename() );
			}
		}
	}

	$ret = nicebackgrounds_wrap( $ret, 'unsplash-choices' );

	$search = nicebackgrounds_link( nicebackgrounds_icon( 'update' ), 'unsplash-refresh', '', __( 'Refresh choices', 'nicebackgrounds' ) );
	$search .= nicebackgrounds_admin_unsplash_search_form( array(), 'search-' );
	$ret .= nicebackgrounds_wrap( $search, 'unsplash-container-search' );

	$load_unsplash   = wp_create_nonce( 'load_unsplash_' . $set_id );
	$save_unsplash   = wp_create_nonce( 'save_unsplash_' . $set_id );
	$remove_unsplash = wp_create_nonce( 'remove_unsplash_' . $set_id );

	return nicebackgrounds_wrap( $ret, 'unsplash-container', array(
		'data-load-unsplash'   => $load_unsplash,
		'data-save-unsplash'   => $save_unsplash,
		'data-remove-unsplash' => $remove_unsplash,
	) );
}

/**
 *
 */
function nicebackgrounds_collection_url( $set_id ) {
	$ret = nicebackgrounds_input( 'url', 'url', '', __( 'URL', 'nicebackgrounds' ) );
	$ret .= nicebackgrounds_wrap( __( 'Fetch image', 'nicebackgrounds' ),
		array( 'browser', 'button', 'button-hero' ), array( 'id' => 'button' ), 'button', null );

	$msg = nicebackgrounds_wrap( nicebackgrounds_icon( 'update' ) . __( 'Fetching', 'nicebackgrounds' ) . '&hellip;',
		array( 'add-status', 'add-status-wait' ), null, 'div', null );
	$msg .= nicebackgrounds_wrap( nicebackgrounds_icon( 'thumbs-up' ) . __( 'Done', 'nicebackgrounds' ),
		array( 'add-status', 'add-status-success' ), null, 'div', null );
	$msg .= nicebackgrounds_wrap( nicebackgrounds_icon( 'thumbs-down' ) . __( 'Error', 'nicebackgrounds' ),
		array( 'add-status', 'add-status-error' ), null, 'div', null );
	$msg .= nicebackgrounds_wrap( nicebackgrounds_icon( 'no' ) . __( 'Fetch failed', 'nicebackgrounds' ),
		array( 'add-status', 'add-status-failure' ), null, 'div', null );
	$ret .= nicebackgrounds_wrap( $msg, 'url-statuses' );

	$nonce = wp_create_nonce( 'save_url_' . $set_id );

	return nicebackgrounds_wrap( $ret, 'url', array( 'data-save-url' => $nonce ) );
}


/**
 *
 */
function nicebackgrounds_collection_reserves( $set_id, $set ) {
	$ret      = nicebackgrounds_p(
		__( "Images removed from the set appear here.  It's just like <em>trash</em> but less demeaning to your nice backgrounds.", 'nicebackgrounds' )
	);
	$statuses = nicebackgrounds_wrap( nicebackgrounds_icon( 'update' ) . __( 'Loading', 'nicebackgrounds' ) . '&hellip;', 'status-wait' );
	$statuses .= nicebackgrounds_wrap( nicebackgrounds_icon( 'no' ) . __( 'Could not load reserves', 'nicebackgrounds' ), 'status-failure' );
	$ret .= nicebackgrounds_wrap( $statuses, 'reserves-choices' );

	$load_reserves   = wp_create_nonce( 'load_reserves_' . $set_id );
	$save_reserves   = wp_create_nonce( 'save_reserves_' . $set_id );
	$remove_reserves = wp_create_nonce( 'remove_reserves_' . $set_id );

	return nicebackgrounds_wrap( $ret, 'reserves-container', array(
		'data-load-reserves'   => $load_reserves,
		'data-save-reserves'   => $save_reserves,
		'data-remove-reserves' => $remove_reserves,
	) );
}

/**
 *
 */
function nicebackgrounds_collection_thumbs( $set_id, $set ) {
	$ret = '';

	$images = nicebackgrounds_get_set_images( $set_id );
	if ( ! empty( $images ) ) {
		foreach ( $images as $image ) {
			$ret .= nicebackgrounds_thumb( $set_id, $set, $image );
		}
	}

	return $ret;
}

/**
 *
 */
function nicebackgrounds_collection_screen( $set_id, $set ) {
	$nonce = wp_create_nonce( 'remove_image_' . $set_id );
	$ret   = nicebackgrounds_wrap( nicebackgrounds_collection_thumbs( $set_id, $set ), 'collection-display', array( 'data-remove-image' => $nonce ) );
	$ret .= nicebackgrounds_tabs( 'collection-add', nicebackgrounds_tabs_collection_add( $set_id, $set ) );

	return nicebackgrounds_wrap( $ret, 'collection' );
}

