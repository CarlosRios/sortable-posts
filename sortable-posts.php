<?php

/**
 * @package Sortable Posts For WordPress
 * @author Carlos Rios
 * @version  0.2.1
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Function to get the url of this file.
 */
if( ! function_exists( 'getSortablePostsUrl' ) )
{
	function getSortablePostsUrl(){
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Windows
			$content_dir = str_replace( '/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR );
			$content_url = str_replace( $content_dir, WP_CONTENT_URL, dirname(__FILE__) );
			$url = str_replace( DIRECTORY_SEPARATOR, '/', $content_url );

		} else {
		  $url = str_replace(
				array(WP_CONTENT_DIR, WP_PLUGIN_DIR),
				array(WP_CONTENT_URL, WP_PLUGIN_URL),
				dirname( __FILE__ )
			);
		}
		return trailingslashit( apply_filters('sortable-posts-url', $url ) );
	}
}

/**
 * Create the columns for each post type on admin init.
 */
add_action( 'admin_init', 'sortablePostsAdminInit' );
function sortablePostsAdminInit()
{
	// Load scripts & styles.
	add_action( 'admin_enqueue_scripts', 'sortablePostsScripts' );
	add_action( 'admin_head', 'sortablePostsAdminHead' );

	// Get the post types specified by developer.
	$postTypes = apply_filters( 'sortable_post_types', array(), $types );

	// Add column to each registered post type.
	foreach( (array) $postTypes as $type )
	{
		if( post_type_exists( $type ) )
		{
			// Add column.
			add_filter( "manage_{$type}_posts_columns", 'sortablePostsCreateColumns' );

			// Add html to the row.
			add_action( "manage_{$type}_posts_custom_column", 'sortablePostsManageColumn' );

			// Remove column sortability for all other columns.
			add_filter( "manage_edit-{$type}_sortable_columns", 'sortablePostsRemoveSortableColumns' );
		}
	}
}

/**
 * Load the scripts and styles.
 */
function sortablePostsScripts()
{
	global $post, $wp_query;

	// Get the starting point for the menu_order.
	$start = ($wp_query->query_vars['paged'] == 1 || $wp_query->query_vars['paged'] == 0 ) ? 1 : ($wp_query->query_vars['posts_per_page'] * $wp_query->query_vars['paged']) - $wp_query->query_vars['posts_per_page'] + 1;

	// Javascript
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'sortable-posts-js', getSortablePostsUrl() . 'js/sortable-posts.js', 'jquery' );
	wp_localize_script('sortable-posts-js', 'sortablePosts', array(
		'ajaxurl'			=> admin_url('admin-ajax.php'),
		'start'				=> $start
	));

	// CSS
	wp_enqueue_style('sortable-posts-css', getSortablePostsUrl() . 'css/sortable-posts.css');
}

/**
 * Add user color scheme styles to our markup.
 */
function sortablePostsAdminHead()
{
	global $_wp_admin_css_colors;
	$user_admin_color = get_user_option( 'admin_color' );
	$colors = $_wp_admin_css_colors[$user_admin_color]->colors; ?>
	<style>
		body.sortable-posts .wp-list-table #the-list tr:hover .sortable-posts-order{
			border-left-color: <?php echo $colors[3]; ?>;
		}
		.sortable-posts-placeholder{
			background: <?php echo $colors[3]; ?>;
		}
	</style>
<?php
}

/**
 * Makes the column.
 */
function sortablePostsCreateColumns( $columns )
{
	$order = array( 'sortable-posts-order' => '<span class="dashicons dashicons-sort"></span>' );
	$columns = array_merge( $order, $columns );
	return $columns;
}

/**
 * Handles the row html.
 */
function sortablePostsManageColumn( $column )
{
	if( $column == 'sortable-posts-order' ){
		$post = get_post($post_ID);
		echo '<strong class="sortable-posts-order-position">' . $post->menu_order . '</strong>';
	}
}

/**
 * Removes sortability for all other columns.
 */
function sortablePostsRemoveSortableColumns( $columns )
{
	$columns = array();
	return $columns;
}

/**
 * Change the orderby parameter for every query with a sortable post type.
 */
add_action( 'pre_get_posts', 'sortablePostsPrePosts' );
function sortablePostsPrePosts( $query )
{
	$postTypes = apply_filters( 'sortable_post_types', array(), $types );

	if( in_array( $query->query_vars['post_type'], $postTypes ) )
	{
		if( !isset( $query->query_vars['orderby'] ) ) // Makes orderby overridable in WP_Query.
			$query->set( 'orderby', 'menu_order' );
		
		if( !isset( $query->query_vars['order'] ) ) // Makes the order overridable in WP_Query.
			$query->set( 'order', 'ASC');
	}
}

/**
 * Add sortable post type class to admin body.
 */
add_action( 'admin_body_class', 'sortablePostsBodyClass' );
function sortablePostsBodyClass( $classes )
{
	$screen = get_current_screen();
	$postTypes = apply_filters( 'sortable_post_types', array(), $types );

	if( $screen->base == 'edit' && in_array( $screen->post_type, $postTypes ) ){
		$classes .= 'sortable-posts';
	}
	return $classes;
}

/**
 * Ajax to update order.
 */
add_action( 'wp_ajax_sortablePostsUpdateOrder', 'sortablePostsUpdateOrder' );
add_action( 'wp_ajax_nopriv_sortablePostsUpdateOrder', 'sortablePostsUpdateOrder' );
function sortablePostsUpdateOrder()
{
	global $wpdb;
	$order = $_POST['order'];
	$start = $_POST['start'];

	foreach( $order as $id ){
		$ids[] = str_replace( 'post-', '', $id );
	}

	$list = join(', ', $ids);
	$wpdb->query( "SELECT @i:= $start-1" );

	$wpdb->query(
		"UPDATE wp_posts SET menu_order = ( @i:= @i+1 )
		WHERE ID IN ( $list ) ORDER BY FIELD( ID, $list );"
	);
}

/**
 * Add order number to newly created post.
 */
add_filter( 'wp_insert_post_data','sortablePostsInsertNewPost', '99', 2 );
function sortablePostsInsertNewPost( $data, $postarr )
{
	$postTypes = apply_filters( 'sortable_post_types', array(), $types );
	$type = $data['post_type'];

	if( in_array( $type, $postTypes ) && $data['menu_order'] == 0 )
	{
		global $wpdb;
		$data['menu_order'] = $wpdb->get_var(
			"SELECT MAX(menu_order)+1 AS menu_order FROM {$wpdb->posts} WHERE post_type='{$type}'"
		);
	}

	return $data;
}