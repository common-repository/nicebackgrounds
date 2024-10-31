<?php
/**
 * This functions in this file are responsible for returning the HTML that creates the admin page.  The exception being
 * nicebackgrounds_admin_page() which is the main callback for the admin page.
 */


/**
 * Creates the HTML code for the group of tabs and panels for sets on the admin page.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_admin_page_sets() {
	nicebackgrounds_init_options();
	$sets = get_option( 'nicebackgrounds_sets', nicebackgrounds_sets_defaults() );

	$set_tabs = array();
	foreach ( $sets as $set_id => $set ) {
		$set_tabs[ $set_id ] = array(
			'title'   => nicebackgrounds_icon( 'portfolio' ) . $set["title"],
			'content' => nicebackgrounds_admin_page_set( $set_id, $set ),
		);
	}

	return nicebackgrounds_tabs( 'set', $set_tabs );
}

/**
 * Creates the HTML code for an Unsplash search form on the admin page.
 *
 * @param array $vals (Optional) The associative array of values of the form.
 * @param string $prefix (Optional) A prefix to use for input tag name attributes, used for avoiding conflicting input names.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_admin_unsplash_search_form( $vals = array(), $prefix = null ) {
	$options = nicebackgrounds_unsplash_search_defaults();
	foreach ( $options as $opt => $default ) {
		if ( ! isset( $vals[ $opt ] ) ) {
			$vals[ $opt ] = $default;
		}
	}

	$ret = nicebackgrounds_input( 'options', $prefix . 'filters', $vals['filters'], __( 'Unsplash filter', 'nicebackgrounds' ),
		array( 'options' => nicebackgrounds_options_unsplash_filters() ) );

	$unsplash_users = get_option( 'nicebackgrounds_unsplash_users', array() );
	$ret .= nicebackgrounds_picklist(
		$prefix . 'user',
		__( 'Unsplash user', 'nicebackgrounds' ),
		$vals['user'],
		$unsplash_users,
		'user',
		false,
		array(
			'data-picklist-option' => 'unsplash_users',
		),
		'nicebackgrounds_callable.nicebackgrounds_save_picklist_option'
	);

	$unsplash_categories = get_option( 'nicebackgrounds_unsplash_categories', nicebackgrounds_unsplash_categories_defaults() );
	$ret .= nicebackgrounds_picklist(
		$prefix . 'category',
		__( 'Unsplash category', 'nicebackgrounds' ),
		$vals['category'],
		$unsplash_categories,
		'category',
		false,
		array(
			'data-picklist-option' => 'unsplash_categories',
		),
		'nicebackgrounds_callable.nicebackgrounds_save_picklist_option'
	);

	$ret .= nicebackgrounds_input( 'option', $prefix . 'featured', 1, __( 'Curated collections only', 'nicebackgrounds' ),
		array( 'multiple' => 1, 'checked_val' => $vals['featured'] ) );

	$ret .= nicebackgrounds_input( 'option', $prefix . 'has-keywords', 1, __( 'Limit by keywords', 'nicebackgrounds' ),
		array(
			'multiple'    => 1,
			'checked_val' => ! ( empty( $vals['keywords'] ) ? 1 : 0 ),
			'iattrs'      => array( 'data-show' => '.nicebackgrounds-picklist-' . $prefix . 'keywords' )
		) );

	$unsplash_categories = get_option( 'nicebackgrounds_unsplash_keywords', nicebackgrounds_unsplash_keywords_defaults() );
	$ret .= nicebackgrounds_picklist(
		$prefix . 'keywords',
		__( 'Keywords', 'nicebackgrounds' ),
		$vals['keywords'],
		$unsplash_categories,
		'category',
		true,
		array(
			'data-picklist-option' => 'unsplash_keywords',
		),
		'nicebackgrounds_callable.nicebackgrounds_save_picklist_option'
	);

	return nicebackgrounds_wrap( $ret, 'unsplash-search' );
}

/**
 * Creates the HTML code for a set on the admin page.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_admin_page_set( $set_id, $set ) {
	$ret = nicebackgrounds_admin_set_actions( $set_id, $set );

	$prep = nicebackgrounds_prepare_set( $set_id, $set );
	if ( true !== $prep ) {
		$ret .= nicebackgrounds_p( __( 'There was a problem preparing the Nice Backgrounds set.', 'nicebackgrounds' ) );
		$ret .= nicebackgrounds_p( $prep );

		return $ret;
	} else {

		$settings = nicebackgrounds_input( 'select', 'source', $set['source'], __( 'Source', 'nicebackgrounds' ),
			array( 'options' => nicebackgrounds_options_source() ) );

		$settings .= nicebackgrounds_input( 'option', 'cdn', 1,
			__( 'For Unsplash images use their CDN (they prefer this so they can collect statistics)', 'nicebackgrounds' ),
			array(
				'multiple'    => 1,
				'checked_val' => ! ( empty( $set['cdn'] ) ? 1 : 0 ),
			) );

		$settings .= nicebackgrounds_input( 'options', 'fixed', $set['fixed'], __( 'Fixed', 'nicebackgrounds' ),
			array( 'options' => nicebackgrounds_options_unsplash_fixed() ) );

		$unsplash_sizes = get_option( 'nicebackgrounds_sizes', nicebackgrounds_sizes_defaults() );
		$settings .= nicebackgrounds_picklist(
			'sizes',
			__( 'Adaptive sizes', 'nicebackgrounds' ),
			$set['sizes'],
			$unsplash_sizes,
			implode( ', ', nicebackgrounds_sizes_defaults() ),
			true,
			array(
				'data-picklist-option' => 'sizes',
			),
			'nicebackgrounds_callable.nicebackgrounds_save_picklist_option',
			__( 'Note: The smallest size is also used for thumbnail resolutions on this page, and the largest for Unsplash requests.', 'nicebackgrounds' )
		);

		$settings .= nicebackgrounds_input( 'options', 'adapt', $set['adapt'], __( 'Adapt', 'nicebackgrounds' ),
			array( 'options' => nicebackgrounds_options_adapt() ) );

		$settings .= nicebackgrounds_admin_unsplash_search_form( $set['unsplash'] );

		$ret .= nicebackgrounds_wrap( $settings, 'set-settings' );

		$ret .= nicebackgrounds_collection_screen( $set_id, $set );


		$css = nicebackgrounds_input( 'option', 'auto', 1,
			__( 'Auto apply background', 'nicebackgrounds' ),
			array(
				'multiple'    => 1,
				'checked_val' => ! ( empty( $set['auto'] ) ? 1 : 0 ),
				'iattrs'      => array( 'data-show' => '.nicebackgrounds-auto-apply' )
			) );

		$sel_help   = nicebackgrounds_link( nicebackgrounds_icon( 'editor-help' ), 'sel-help', 'https://api.jquery.com/category/selectors/basic-css-selectors/',
			__( 'Selector help', 'nicebackgrounds' ), array( 'target' => '_blank' ) );
		$auto_apply = nicebackgrounds_input( 'text', 'sel', $set['sel'], __( 'Selector', 'nicebackgrounds' ) . ' ' . $sel_help );

		$auto_apply .= nicebackgrounds_input( 'options', 'measure', $set['measure'], __( 'Measure', 'nicebackgrounds' ),
			array( 'options' => nicebackgrounds_options_measure() ) );

		$auto_apply .= nicebackgrounds_input( 'options', 'dimension', $set['dimension'], __( 'Dimension', 'nicebackgrounds' ),
			array( 'options' => nicebackgrounds_options_dimension() ) );

		$css .= nicebackgrounds_wrap( $auto_apply, 'auto-apply' );

		$instructions = __( 'Alternatively you can use the following image URL in CSS/JS/HTML in your theme:', 'nicebackgrounds' );
		$instructions .= nicebackgrounds_wrap( site_url() . '?nicebackgrounds=' . $set_id . '&size=<em>[width]</em>', 'instructions-code' );
		$instructions .= __( 'For adaptive sizing it must be done through JavaScript with something like the following:', 'nicebackgrounds' );
		$instructions .= nicebackgrounds_wrap( 'var size = screen.width * (window.devicePixelRatio ? window.devicePixelRatio : 1);', 'instructions-code' );
		$css .= nicebackgrounds_wrap( $instructions, 'instructions' );

		$ret .= nicebackgrounds_wrap( $css, 'css-settings' );


		$attr = array(
			'data-save-set'     => wp_create_nonce( 'save_set_' . $set_id ),
			'data-generate-set' => wp_create_nonce( 'generate_set_' . $set_id ),
		);

		return nicebackgrounds_wrap( $ret, 'set-form', $attr, 'form' );
	}

}

/**
 * Creates the HTML code for the action links in the top right corner of a set on the admin page.
 *
 * @param string $set_id The set identifier.
 * @param array $set The associative array representing the set settings and state.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_admin_set_actions( $set_id, $set ) {

	$clear = __( 'Clear set cache', 'nicebackgrounds' );
	$link  = nicebackgrounds_link( nicebackgrounds_icon( 'marker' ) . $clear, 'set-clear', '', $clear );
	$ret   = nicebackgrounds_wrap( $link, array( 'action-link', 'action-link-clear' ) );

	$rename = __( 'Rename set', 'nicebackgrounds' );
	$link   = nicebackgrounds_link( nicebackgrounds_icon( 'edit' ) . $rename, 'set-rename', '', $rename );
	$link .= nicebackgrounds_input( 'text', 'set-name-form', $set['title'], '',
		array(
			'iattrs' => array( 'placeholder' => __( 'Set title', 'nicebackgrounds' ) ),
			'suffix' => nicebackgrounds_wrap( nicebackgrounds_icon( 'arrow-right' ), null, null, 'button' )
		) );
	$ret .= nicebackgrounds_wrap( $link, array( 'action-link', 'action-link-rename' ) );

	$clone       = __( 'Clone set', 'nicebackgrounds' );
	$link        = nicebackgrounds_link( nicebackgrounds_icon( 'admin-page' ) . $clone, 'set-clone', '', $clone );
	$clone_title = $set['title'] . ' (' . __( 'clone', 'nicebackgrounds' ) . ')';
	$link .= nicebackgrounds_input( 'text', 'set-clone-form', $clone_title, '',
		array(
			'iattrs' => array( 'placeholder' => __( 'Set title', 'nicebackgrounds' ) ),
			'suffix' => nicebackgrounds_wrap( nicebackgrounds_icon( 'arrow-right' ), null, null, 'button' )
		) );
	$ret .= nicebackgrounds_wrap( $link, array( 'action-link', 'action-link-clone' ) );

	$delete = __( 'Delete set', 'nicebackgrounds' );
	$link   = nicebackgrounds_link( nicebackgrounds_icon( 'trash' ) . $delete, 'set-delete', '', $delete );
	$link .= nicebackgrounds_wrap( __( 'Delete', 'nicebackgrounds' ) . ' ' . nicebackgrounds_icon( 'arrow-right' ), 'form-row', null, 'button' );
	$ret .= nicebackgrounds_wrap( $link, array( 'action-link', 'action-link-delete' ) );

	return nicebackgrounds_wrap( $ret, 'set-actions', array( 'data-set-action' => wp_create_nonce( 'set_action_' . $set_id ) ) );
}

/**
 * Creates the HTML code for the 'Create set' link in the top right corner of the admin page.
 *
 * @return string The HTML code.
 */
function nicebackgrounds_admin_page_new_set() {
	$create_set = __( 'Create set', 'nicebackgrounds' );
	$ret        = nicebackgrounds_link( nicebackgrounds_icon( 'plus' ) . $create_set, 'new-set', '', $create_set,
		array( 'id' => 'nicebackgrounds-new-set' ) );
	$ret .= nicebackgrounds_input( 'text', 'new-set-form', '', '',
		array(
			'iattrs' => array( 'placeholder' => __( 'Set title', 'nicebackgrounds' ) ),
			'suffix' => nicebackgrounds_wrap( nicebackgrounds_icon( 'arrow-right' ), null, null, 'button' )
		) );
	$ret = nicebackgrounds_wrap( $ret, null, null, 'form' );

	return nicebackgrounds_wrap( $ret, null, array(
		'id'           => 'nicebackgrounds-new-set-container',
		'data-new-set' => wp_create_nonce( 'new_set' )
	) );
}

/**
 * The main callback for the admin page.  Outputs the HTML.
 */
function nicebackgrounds_admin_page() {
	require_once( plugin_dir_path( __FILE__ ) . 'collection.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'data.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'markup.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'utils.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'set.php' );
	$ret = nicebackgrounds_wrap( __( 'Nice Backgrounds', 'nicebackgrounds' ), null, null, 'h2' );
	$ret .= nicebackgrounds_admin_page_new_set();
	$ret .= nicebackgrounds_admin_page_sets();

	$wrap_attrs = array(
		'id'                        => 'nicebackgrounds-wrap',
		'data-save-picklist-option' => wp_create_nonce( 'save_picklist_option' ),
	);

	$ret = nicebackgrounds_wrap( $ret, 'wrap', $wrap_attrs, 'div', null );

	if ( true === WP_DEBUG ) {
		$debug = nicebackgrounds_wrap( 'DEBUG', null, null, 'label' );
		$debug .= nicebackgrounds_wrap( nicebackgrounds_debug_options(), null, null, 'textarea' );
		$ret .= nicebackgrounds_wrap( $debug, 'debug' );
	}

	$status = nicebackgrounds_wrap( nicebackgrounds_icon( 'update' ) . __( 'Saving', 'nicebackgrounds' ) . '&hellip;', 'wait' );
	$status .= nicebackgrounds_wrap( nicebackgrounds_icon( 'yes' ) . __( 'Saved', 'nicebackgrounds' ), 'success' );
	$status .= nicebackgrounds_wrap( nicebackgrounds_icon( 'no' ) . __( "Won't save!", 'nicebackgrounds' ), 'failure' );
	$ret .= nicebackgrounds_wrap( $status, 'status' );

	echo $ret;
}


