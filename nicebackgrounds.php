<?php
/*
Plugin Name: Nice Backgrounds
Description: Manage random adaptive background images and pull beautiful photos from Unsplash.
Version: 1.0
Author: brx8r
Author URI: https://profiles.wordpress.org/brx8r
*/

/**
 * Init action callback.
 */
function nicebackgrounds_init() {
	$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$lang_dir = apply_filters( 'nicebackgrounds_languages_directory', $lang_dir );
	load_plugin_textdomain( 'nicebackgrounds', false, $lang_dir );
}

/**
 * WP enqueue scripts action callback.
 */
function nicebackgrounds_wp_enqueue_scripts() {
	$auto_sels = nicebackgrounds_auto_sels();
	if ( ! empty( $auto_sels ) ) {
		switch ( $_SERVER['HTTP_HOST'] ) {
			case 'localhost':
				wp_enqueue_style( 'nicebackgrounds-css', plugin_dir_url( __FILE__ ) . 'css/style.css' );
				wp_enqueue_script( 'nicebackgrounds-js', plugin_dir_url( __FILE__ ) . 'js/nicebackgrounds.js', array(
					'jquery',
					'underscore'
				) );
				break;

			default:
				wp_enqueue_style( 'nicebackgrounds-css', plugin_dir_url( __FILE__ ) . 'css/style.min.css' );
				wp_enqueue_script( 'nicebackgrounds-js', plugin_dir_url( __FILE__ ) . 'js/nicebackgrounds.min.js', array(
					'jquery',
					'underscore'
				) );
		}
		wp_localize_script( 'nicebackgrounds-js', 'nicebackgrounds_data', array(
			'auto_sels' => $auto_sels,
			'display'   => site_url(),
		) );
	}
}

/**
 * Admin enqueue scripts action callback.
 */
function nicebackgrounds_admin_enqueue_scripts( $hook ) {
	if ( $hook == 'appearance_page_nicebackgrounds' ) {

		switch ( $_SERVER['HTTP_HOST'] ) {
			case 'localhost':
				wp_enqueue_style( 'nicebackgrounds-admin-css', plugin_dir_url( __FILE__ ) . 'css/admin.css' );
				wp_enqueue_script( 'nicebackgrounds-admin-upload-js', plugin_dir_url( __FILE__ ) . 'js/admin.upload.js', array( 'jquery' ) );
				wp_enqueue_script( 'nicebackgrounds-admin-picklist-js', plugin_dir_url( __FILE__ ) . 'js/admin.picklist.js', array( 'jquery' ) );
				wp_enqueue_script( 'nicebackgrounds-admin-tabs-js', plugin_dir_url( __FILE__ ) . 'js/admin.tabs.js', array( 'jquery' ) );
				wp_enqueue_script( 'nicebackgrounds-admin-unsplash-js', plugin_dir_url( __FILE__ ) . 'js/admin.unsplash.js', array( 'jquery' ) );
				wp_enqueue_script( 'nicebackgrounds-admin-url-js', plugin_dir_url( __FILE__ ) . 'js/admin.url.js', array( 'jquery' ) );
				wp_enqueue_script( 'nicebackgrounds-admin-reserves-js', plugin_dir_url( __FILE__ ) . 'js/admin.reserves.js', array( 'jquery' ) );
				wp_enqueue_script( 'nicebackgrounds-admin-js', plugin_dir_url( __FILE__ ) . 'js/admin.main.js', array( 'jquery' ) );
				break;

			default:
				wp_enqueue_style( 'nicebackgrounds-admin-css', plugin_dir_url( __FILE__ ) . 'css/admin.min.css' );
				wp_enqueue_script( 'nicebackgrounds-admin-js', plugin_dir_url( __FILE__ ) . 'js/admin.min.js', array( 'jquery' ) );
		}
		wp_localize_script( 'nicebackgrounds-admin-js', 'nicebackgrounds_data', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}
}

/**
 * Admin menu action callback.
 */
function nicebackgrounds_admin_menu() {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/admin.php' );
	add_submenu_page(
		'themes.php',
		__( 'Nice Backgrounds', 'nicebackgrounds' ),
		__( 'Nice Backgrounds', 'nicebackgrounds' ),
		'manage_options',
		'nicebackgrounds',
		'nicebackgrounds_admin_page'
	);
}

/**
 * Admin init action callback.
 */
function nicebackgrounds_admin_init() {
	register_setting( 'nicebackgrounds', 'nicebackgrounds_sets' );
	register_setting( 'nicebackgrounds', 'nicebackgrounds_images' );
	register_setting( 'nicebackgrounds', 'nicebackgrounds_reserves' );
	register_setting( 'nicebackgrounds', 'nicebackgrounds_unsplash_users' );
	register_setting( 'nicebackgrounds', 'nicebackgrounds_unsplash_categories' );
	register_setting( 'nicebackgrounds', 'nicebackgrounds_unsplash_keywords' );
	register_setting( 'nicebackgrounds', 'nicebackgrounds_sizes' );
}

/**
 * WP AJAX action callback.
 *
 * For convenience all Ajax requests in this plugin go through this function, which passes off to a named function in
 * ajax.php based on the parameters of the request. This function handles common code for the requests, such as
 * requiring files, security, and JSON output.
 *
 * @param bool $nopriv Set this to true if it's a front-end ajax call, intended for use by nicebackgrounds_ajax_nopriv().
 *
 * @todo Switch over to using wp_send_json(), wp_send_json_success(), wp_send_json_error()?
 */
function nicebackgrounds_ajax( $nopriv = false ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/data.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'includes/markup.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'includes/utils.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'includes/set.php' );
	if ( ! empty( $_POST['func'] ) ) {
		$nonce_key = ( ! empty( $_POST['nonce_key'] ) ?
			$_POST['nonce_key'] : ( ! empty( $_POST['set_id'] ) ? $_POST['func'] . '_' . $_POST['set_id'] : null ) );
		if ( $nopriv || ( wp_verify_nonce( $_REQUEST['nonce'], $nonce_key ) ) ) {
			$func = 'nicebackgrounds_ajax_' . ( $nopriv ? 'nopriv_' : '' ) . $_POST['func'];
			include_once( plugin_dir_path( __FILE__ ) . 'includes/ajax.php' );
			if ( function_exists( $func ) ) {
				// Validate the set identifier if it was provided in this request.  It may have been removed or renamed
				// by a concurrent user.  Not every ajax response handler displays errors to the user though.
				if ( isset( $_POST['set_id'] ) && ! nicebackgrounds_validate_set_id( $_POST['set_id'] ) ) {
					wp_die( json_encode( array(
						'success' => false,
						'message' => __( 'The set does not exist.', 'nicebackgrounds' )
					) ) );
				}

				// Get the ajax result from the appropriate handler function.
				wp_die( json_encode( call_user_func( $func ) ) );
			}
		}
	}

	$error_code = 'nbg_' . ( ! empty( $_POST['func'] ) ? $_POST['func'] : '0' ) . ( $nopriv ? '_1' : '_0' );
	wp_die( new WP_Error( $error_code, "Nonce nonce nonce nonce, don't func with my heart." ) );
}

/**
 * WP AJAX public action callback.
 *
 * This isn't currently used for anything.
 */
function nicebackgrounds_ajax_nopriv() {
	nicebackgrounds_ajax( true );
}

/**
 * Query vars action callback.
 *
 * @link http://ottopress.com/2010/dont-include-wp-load-please/  Refer to "Right Way The Second".
 */
function nicebackgrounds_query_vars( $vars ) {
	$vars[] = 'nicebackgrounds';

	return $vars;
}

/**
 * Template redirect action callback.
 *
 * @link http://ottopress.com/2010/dont-include-wp-load-please/  Refer to "Right Way The Second".
 */
function nicebackgrounds_template_redirect() {
	if ( ! isset( $_GET['nicebackgrounds'] ) ) {
		return;
	}
	require_once( plugin_dir_path( __FILE__ ) . 'includes/display.php' );
}

/**
 * Gets the auto apply settings for each set in sets.
 *
 * This utility function exists in this file so as to not need including any other files.
 *
 * @return array Associative array mapping set identifier strings to their auto apply settings array.
 */
function nicebackgrounds_auto_sels() {
	$auto_sels = array();
	// Notice that unlike everywhere else in this plugin the 'nicebackgrounds_sets' option is defaulted to an empty array.
	$sets = get_option( 'nicebackgrounds_sets', array() );
	foreach ( $sets as $set_id => $set ) {
		if ( ! empty( $set['auto'] ) && ! empty( $set['sel'] ) ) {
			$auto_sels[ $set_id ] = array(
				'sel'       => $set['sel'],
				'measure'   => $set['measure'],
				'dimension' => $set['dimension'],
			);
		}
	}

	return $auto_sels;
}


// Add actions.
add_action( 'init', 'nicebackgrounds_init' );
add_action( 'wp_enqueue_scripts', 'nicebackgrounds_wp_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'nicebackgrounds_admin_enqueue_scripts' );
add_action( 'admin_menu', 'nicebackgrounds_admin_menu' );
add_action( 'admin_init', 'nicebackgrounds_admin_init' );
add_action( 'wp_ajax_nicebackgrounds', 'nicebackgrounds_ajax' );
//add_action( 'wp_ajax_nopriv_nicebackgrounds', 'nicebackgrounds_ajax_nopriv' );
add_filter( 'query_vars', 'nicebackgrounds_query_vars' );
add_action( 'template_redirect', 'nicebackgrounds_template_redirect' );

