<?php
/**
 * This file picks an image based on $_GET['nicebackgrounds'] (Which will contain the set identifier) and $_GET['size']
 * and redirects to that image.  It also performs some tasks in lieu of a cron setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_dir_path( __FILE__ ) . 'data.php' );
require_once( plugin_dir_path( __FILE__ ) . 'utils.php' );
require_once( plugin_dir_path( __FILE__ ) . 'set.php' );

$set_id = ! empty( $_GET['nicebackgrounds'] ) ? $_GET['nicebackgrounds'] : null;
$sets   = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );
if ( null === $set_id || ! isset( $sets[ $set_id ] ) ) {
	// They didn't specify a set, or it's an invalid value, so just pick one.
	$set_id = key( $sets );
}
$set    = &$sets[ $set_id ];
$images = nicebackgrounds_get_set_images( $set_id );

$size = ! empty( $_GET['size'] ) ? intval( $_GET['size'] ) : nicebackgrounds_largest_size( $set_id, $set );
$size = nicebackgrounds_nearest_size( $set_id, $set, $size, ( $set['adapt'] == 'larger' ) );

$image_url = null;

$collection_with_images = ( $set['source'] == 'collection' && ! empty( $images ) );

$fixed = $set['fixed'];
if ( $set['source'] == 'unsplash' ) {
	// Because the server executing this script and Unsplash's server may not line up in terms of time, we poll Unsplash
	// more frequently than the fixed image time.
	if ( $set['fixed'] == 'weekly' ) {
		// In Unsplash CDN weekly mode request a new one per day.
		$fixed = 'daily';
	} elseif ( $set['fixed'] == 'daily' ) {
		// In Unsplash CDN daily mode request a new one hourly.
		$fixed = 'hourly';
	}
}
$current_image_time = nicebackgrounds_current_image_time( $fixed );

if ( $collection_with_images && $set['fixed'] != 'none' && $current_image_time != $set['current_image_time'] ) {
	// Collection source: Pick new image because the current one expired and we're in weekly/daily mode.
	// Images existing in set is enforced for safety.
	$image                     = $images[ array_rand( $images ) ];
	$set['current_image']      = $image;
	$set['current_image_time'] = $current_image_time;
	update_option( 'nicebackgrounds_sets', $sets );
	$image_url = nicebackgrounds_get_image_src( $set_id, $set, $image, $size );

} elseif ( $collection_with_images && $set['fixed'] != 'none' && ! empty( $set['current_image'] ) ) {
	// Collection source: Use current image because it hasn't expired and we're in weekly/daily mode.
	// Images existing in set is enforced for safety.
	$image_url = nicebackgrounds_get_image_src( $set_id, $set, $set['current_image'], $size );
} elseif ( $collection_with_images ) {
	// Collection source: Pick a random image because it is unfixed.
	// Images existing in set is enforced for safety.
	$image_url = nicebackgrounds_get_image_src( $set_id, $set, $images[ array_rand( $images ) ], $size );
} elseif ( $set['source'] == 'unsplash' && empty( $set['cdn'] ) && ! empty( $set['current_image'] ) ) {
	// Unsplash source: Use current image because there is one and we're not using the CDN.
	// We don't check the expiry here because it takes too long to fetch images from Unsplash.
	$image_url = nicebackgrounds_get_image_src( $set_id, $set, $set['current_image'], $size );
} else {
	// Default situation - use Unsplash CDN.  There could be a number of reasons we are here:
	// Collection source with no images in the set.
	// Unsplash source configured to use CDN.
	// Unsplash source without CDN but there just haven't been any Unsplash image fetched yet.
	if ( ! empty( $set['current_image']['unsplash'] ) ) {
		// Cached version.
		// We don't check the expiry here because it takes too long to fetch images from Unsplash.
		$image_url = $set['current_image']['unsplash'];
	} else {
		// No cached version exists, suck it up and fetch one at the right size for this user.  We'll fetch another one
		// later at the largest size once this has been sent to the user.
		$image_url = nicebackgrounds_resolve_unsplash_url(
			array_merge( $set['unsplash'], array( 'size' => '100x100', 'fixed' => $set['fixed'] ) ),
			$size
		);
	}
}

// Send the output - which is just the redirect to the url of the image with output buffering.
ob_start();
header( 'Location: ' . $image_url );
header( "Connection: close" );
ob_end_flush();
ob_flush();
flush();

// After output is sent, determine if set needs a new non-CDN image from Unsplash for next time.
// Note: Even for 'unfixed' mode we fix it for one minute, so as not to hammer Unsplash (and ourselves by generating
// heaps of adaptive images).
if ( $set['source'] == 'unsplash' && $current_image_time != $set['current_image_time'] ) {
	// Only send the configured fixed value to Unsplash if we're in CDN mode, otherwise we handle it ourselves.
	$fixed = ! empty( $set['cdn'] ) ? $set['fixed'] : 'none';

	// The reason we request a 100x100 image to our server first is that Unsplash doesn't allow fetching an image
	// with no height specified via the Source API URL, however once we resolve that URL to wherever it redirects
	// using one request we can then construct a URL for that specific image with only a width and no height.
	// We'll mitigate this by caching the final url server side in accordance with the fixed setting.
	$unsplash_url = nicebackgrounds_resolve_unsplash_url(
		array_merge( $set['unsplash'], array( 'size' => '100x100', 'fixed' => $fixed ) ),
		nicebackgrounds_largest_size( $set_id, $set )
	);
	if ( ! empty( $set['cdn'] ) ) {
		// Set the new image.
		// I'm not sure this minimal approach to storing the Unsplash CDN is good enough. If someone were to switch
		// the CDN setting off, things might go awry.  Perhaps it should all be done as in the 'else' clause of this
		// if-statement.
		$set['current_image']      = array( 'unsplash' => $unsplash_url );
		$set['current_image_time'] = $current_image_time;
		update_option( 'nicebackgrounds_sets', $sets );
	} else {
		// Store
		$url_parts = explode( '?', $unsplash_url );
		$response  = nicebackgrounds_leech( $set_id, $unsplash_url, false, $url_parts[0], true );
		if ( $response['success'] && is_array( $response['result'] ) ) {
			// Store the previous image filename for deletion.
			$previous_image = $set['current_image']['file'];

			// Set the new image.
			$set['current_image']      = $response['result'];
			$set['current_image_time'] = $current_image_time;
			update_option( 'nicebackgrounds_sets', $sets );

			// Delete the previous image.
			wp_delete_file( nicebackgrounds_image_path( $set_id, 'full', $previous_image ) );
			nicebackgrounds_clear_adaptive_sizes_specific( $set_id, $set, $previous_image );
		}
	}

}

// After output is sent, do some processing on missing adaptive sizes.
nicebackgrounds_find_and_generate_missing_adaptives( $set_id, $set, 10 );

exit;

