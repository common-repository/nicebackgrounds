<?php
/**
 * The functions in this file return hardcoded data for the plugin, such as form configurations and option defaults.
 */

/**
 * Provides the data for the Source select list.
 */
function nicebackgrounds_options_source() {
	return array(
		'collection' => array(
			'label' => __( 'Collection (a directory of uploads and images chosen from URLs and Unsplash)', 'nicebackgrounds' ),
			'attrs' => array(
				'data-show' => '.nicebackgrounds-collection',
			),
		),
		'unsplash'   => array(
			'label' => __( 'Unsplash (keeps it fresh but there may be some surprises)', 'nicebackgrounds' ),
			'attrs' => array(
				'data-show' => '.nicebackgrounds-unsplash-search:first',
			),
		),

	);
}

/**
 * Provides the data for Unsplash filter forms.
 */
function nicebackgrounds_options_unsplash_filters() {
	return array(
		'unfiltered' => array(
			'label' => __( 'Unfiltered', 'nicebackgrounds' ),
		),
		'category'   => array(
			'label' => __( 'From a category', 'nicebackgrounds' ),
			'attrs' => array(
				'data-show' => '.nicebackgrounds-picklist-search-category, .nicebackgrounds-picklist-category',
			),
		),
		'user'       => array(
			'label' => __( 'From a user', 'nicebackgrounds' ),
			'attrs' => array(
				'data-show' => '.nicebackgrounds-picklist-search-user, .nicebackgrounds-picklist-user',
			),
		),
		'likes'      => array(
			'label' => __( "From a user's likes", 'nicebackgrounds' ),
			'attrs' => array(
				'data-show' => '.nicebackgrounds-picklist-search-user, .nicebackgrounds-picklist-user',
			),
		),
	);
}

/**
 * Provides the data for the Fixed form options.
 */
function nicebackgrounds_options_unsplash_fixed() {
	return array(
		'weekly' => array(
			'label' => __( 'Fixed weekly photo', 'nicebackgrounds' ),
		),
		'daily'  => array(
			'label' => __( 'Fixed daily photo', 'nicebackgrounds' ),
		),
		'none'   => array(
			'label' => __( 'New every minute', 'nicebackgrounds' ),
		),
	);
}

/**
 * Provides the data for the Adapt form options.
 */
function nicebackgrounds_options_adapt() {
	return array(
		'nearest' => array(
			'label' => __( 'To nearest size', 'nicebackgrounds' ),
		),
		'larger'  => array(
			'label' => __( 'To next size up', 'nicebackgrounds' ),
		),
	);
}

/**
 * Provides the data for the Measure form options.
 */
function nicebackgrounds_options_measure() {
	return array(
		'screen'  => array(
			'label' => __( 'Screen', 'nicebackgrounds' ),
		),
		'element' => array(
			'label' => __( 'Element', 'nicebackgrounds' ),
		),
	);
}

/**
 * Provides the data for the Measure form options.
 */
function nicebackgrounds_options_dimension() {
	return array(
		'width'   => array(
			'label' => __( 'Width', 'nicebackgrounds' ),
		),
		'height'  => array(
			'label' => __( 'Height', 'nicebackgrounds' ),
		),
		'longest' => array(
			'label' => __( 'Longest side', 'nicebackgrounds' ),
		),
	);
}

/**
 * Provides the data for the collection add tabs.
 */
function nicebackgrounds_tabs_collection_add( $set_id, $set ) {
	return array(
		'unsplash' => array(
			'title'   => '<span class="dashicons dashicons-camera"></span>' . __( 'Add from Unsplash', 'nicebackgrounds' ),
			'content' => nicebackgrounds_collection_unsplash( $set_id, $set ),
		),
		'upload'   => array(
			'title'   => '<span class="dashicons dashicons-upload"></span>' . __( 'Add from upload', 'nicebackgrounds' ),
			'content' => nicebackgrounds_collection_upload( $set_id ),
		),
		'url'      => array(
			'title'   => '<span class="dashicons dashicons-admin-site"></span>' . __( 'Add from URL', 'nicebackgrounds' ),
			'content' => nicebackgrounds_collection_url( $set_id ),
		),
		'reserves' => array(
			'title'   => '<span class="dashicons dashicons-admin-post"></span>' . __( 'Add from reserves', 'nicebackgrounds' ),
			'content' => nicebackgrounds_collection_reserves( $set_id, $set ),
		),
	);
}

/**
 * Provides the default Picklist choices for Unsplash categories.
 */
function nicebackgrounds_unsplash_categories_defaults() {
	// These correspond to a list found in Unsplash's documentation.
	return array(
		'buildings',
		'food',
		'nature',
		'people',
		'technology',
		'objects',
	);
}

/**
 * Provides the default Picklist choices for Unsplash keywords.
 */
function nicebackgrounds_unsplash_keywords_defaults() {
	return nicebackgrounds_unsplash_categories_defaults();
}


/**
 * Provides the default Picklist choices for Adaptive sizes.
 */
function nicebackgrounds_sizes_defaults() {
	return array( 2048, 1920, 1366, 1280, 1024, 800, 640, 480, 240 );
}

/**
 * For supported image formats, map extensions to mime types.
 */
function nicebackgrounds_image_types() {
	// These must not change as other formats are unhandled by the code, particularly regarding adaptive sizing.
	return array(
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
	);
}

/**
 * Provides the default values for the Unsplash subsection of a set.
 */
function nicebackgrounds_unsplash_search_defaults() {
	return array(
		'filters'  => 'unfiltered',
		'user'     => '',
		'category' => '',
		'featured' => 0,
		'keywords' => '',
	);
}

/**
 * Provides the default values for a set.
 */
function nicebackgrounds_empty_set() {
	return array(
		'title'              => __( 'Default', 'nicebackgrounds' ),
		'source'             => 'collection',
		'fixed'              => 'weekly',
		'cdn'                => 1,
		'sizes'              => nicebackgrounds_sizes_defaults(),
		'adapt'              => 'nearest',
		'unsplash'           => nicebackgrounds_unsplash_search_defaults(),
		'auto'               => 0,
		'sel'                => 'body',
		'measure'            => 'screen',
		'dimension'          => 'width',
		'current_image'      => null,
		'current_image_time' => null,
	);
}

/**
 * Provides the default values for the nicebackgrounds_sets WP option.
 */
function nicebackgrounds_sets_defaults() {
	return array(
		'default' => nicebackgrounds_empty_set(),
	);
}

/**
 * Provides the default values for the nicebackgrounds_images WP option.
 */
function nicebackgrounds_images_defaults() {
	return array(
		'default' => array(),
	);
}

/**
 * Provides the default values for the nicebackgrounds_reserves WP option.
 */
function nicebackgrounds_reserves_defaults() {
	return array(
		'default' => array(),
	);
}

/**
 * Provides a list of success messages to be used by the plugin.
 */
function nicebackgrounds_success_messages() {
	return array(
		// Enjoy translating this :D
		__( "Nice background!", 'nicebackgrounds' ),
		__( 'Good one!', 'nicebackgrounds' ),
		__( 'Impressive image!', 'nicebackgrounds' ),
		__( 'Excellent!', 'nicebackgrounds' ),
		__( 'Great choice!', 'nicebackgrounds' ),
		__( 'Awesome picture!', 'nicebackgrounds' ),
		__( 'That one is okay.', 'nicebackgrounds' ),
		__( 'Not bad...', 'nicebackgrounds' ),
		__( 'Super!', 'nicebackgrounds' ),
		__( 'Dope.', 'nicebackgrounds' ),
		__( 'Sweet as...', 'nicebackgrounds' ),
		__( 'f-f-f-fresh', 'nicebackgrounds' ),
		__( 'Quite alright', 'nicebackgrounds' ),
		__( 'Clearly good', 'nicebackgrounds' ),
		__( 'Alrighty', 'nicebackgrounds' ),
		__( 'Outstanding!', 'nicebackgrounds' ),
		__( 'Superb.', 'nicebackgrounds' ),
		__( 'Magnificent!', 'nicebackgrounds' ),
		__( 'Wonderful!', 'nicebackgrounds' ),
		__( 'Sublime!', 'nicebackgrounds' ),
		__( 'Exceptional pic.', 'nicebackgrounds' ),
		__( 'Ah, perfect...', 'nicebackgrounds' ),
		__( 'First-rate!', 'nicebackgrounds' ),
		__( 'SUPREME', 'nicebackgrounds' ),
		__( 'Admirable choice', 'nicebackgrounds' ),
		__( 'A fine image :)', 'nicebackgrounds' ),
		__( 'Splendid', 'nicebackgrounds' ),
		__( 'OK whatever', 'nicebackgrounds' ),
		__( 'Bravo!', 'nicebackgrounds' ),
	);
}

/**
 * Get a list of the option keys used by this plugin.
 *
 * @return array A list of the option keys.
 */
function nicebackgrounds_option_keys() {
	// These correspond to the settings in nicebackgrounds_admin_init(), some other functions need the list.
	return array(
		'sets',
		'images',
		'reserves',
		'unsplash_users',
		'unsplash_categories',
		'unsplash_keywords',
		'sizes'
	);
}