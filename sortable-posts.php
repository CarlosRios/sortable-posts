<?php
/**
 * Plugin Name: Sortable Posts
 * Description: Sortable Posts is a small plugin for WordPress that adds sortability to the post edit screen of any post type you choose.
 * Author: Carlos Rios
 * Author URI: http://www.texaswebsitemanagement.com
 * Version: 0.1.0
 * Plugin URI: https://github.com/CarlosRios/sortable-posts-wp
 * License: GPL2+
 *
 * @package  Sortable Posts For WordPress
 * @category WordPress/Plugin
 * @author   Carlos Rios
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	die;
}

if( ! class_exists( 'SortablePosts' ) ) {

	class SortablePosts {

		/**
		 * Textdomain
		 * @var string
		 */
		public static $textdomain = 'sortable-posts';

		/**
		 * Sortable Posts version
		 * @var string
		 */
		public $version = '0.1.0';

		/**
		 * List of sortable post types
		 * @var array
		 */
		public $sortable_types = array();

		/**
		 * Initiates Sortable Posts
		 */
		public function __construct()
		{
			$this->includes();
			$this->set_sortable_types();
			$this->register_hooks();
		}

		/**
		 * Includes all necessary files
		 */
		public function includes()
		{
			require_once( 'includes/class-sp-settings.php' );
		}

		/**
		 * Registers all of our hooks
		 */
		public function register_hooks()
		{
			add_action( 'admin_init', array( $this, 'register_custom_columns' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
			add_action( 'admin_head', array( $this, 'add_styles_to_header' ) );
			add_action( 'admin_body_class', array( $this, 'add_classes_to_body' ) );
			add_action( 'wp_ajax_sortable_posts_update_order', array( $this, 'update_order' ) );
			add_action( 'wp_ajax_nopriv_sortable_posts_update_order', array( $this, 'update_order' ) );
			add_action( 'pre_get_posts', array( $this, 'order_by_sortable_posts' ) );
			add_filter( 'wp_insert_post_data', array( $this, 'update_order_on_new_post' ), 99, 2 );
		}

		/**
		 * Sets the sortable post types in the class
		 */
		function set_sortable_types()
		{
			$settings = get_option( 'sortable_posts' );
			$types = apply_filters( 'sortable_post_types', $settings );

			if( is_array( $types ) && !empty( $types ) ) {
				$this->sortable_types = $types;
			}
			
			return;
		}

		/**
		 * Registers the Sortable Posts custom columns for each post type
		 */
		function register_custom_columns()
		{
			// Add column to each registered post type.
			foreach( (array) $this->sortable_types as $type )
			{
				if( post_type_exists( $type ) )
				{
					// Add column.
					add_filter( "manage_{$type}_posts_columns", array( $this, 'create_custom_column' ) );

					// Add html to the row.
					add_action( "manage_{$type}_posts_custom_column", array( $this, 'manage_custom_column' ) );

					// Remove column sortability for all other columns.
					add_filter( "manage_edit-{$type}_sortable_columns", array( $this, 'remove_sortable_columns' ) );
				}
			}
		}

		/**
		 * Registers the scripts for Sortable Posts
		 */
		function register_scripts()
		{
			global $post, $wp_query;

			// Set the starting point for the menu_order.
			if( isset( $wp_query->query_vars['paged'] ) ) {
				$start = ($wp_query->query_vars['paged'] == 1 || $wp_query->query_vars['paged'] == 0 ) ? 1 : ($wp_query->query_vars['posts_per_page'] * $wp_query->query_vars['paged']) - $wp_query->query_vars['posts_per_page'] + 1;
			}else{
				$start = 1;
			}
			
			// Javascript
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'sortable-posts-js', plugins_url( '/sortable-posts-wp/assets/js/sortable-posts.js' ), 'jquery' );
			wp_localize_script( 'sortable-posts-js', 'sortablePosts', array(
				'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
				'start'		=> $start
			));

			// CSS
			wp_enqueue_style( 'sortable-posts-css', plugins_url( '/sortable-posts-wp/assets/css/sortable-posts.css' ) );
		}

		/**
		 * Adds class to the body when on a Sortable Posts post type
		 * @param array $classes
		 */
		function add_classes_to_body( $classes )
		{
			$screen = get_current_screen();
			if( $screen->base == 'edit' && in_array( $screen->post_type, $this->sortable_types ) ){
				$classes .= 'sortable-posts';
			}
			return $classes;
		}

		/**
		 * Adds admin theme styles to admin header
		 */
		function add_styles_to_header()
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
		 * Creates the custom column
		 * @param  array $columns - registered post type columns
		 * @return array $columns
		 */
		function create_custom_column( $columns )
		{
			$order = array( 'sortable-posts-order' => '<span class="dashicons dashicons-sort"></span>' );
			$columns = array_merge( $order, $columns );
			return $columns;
		}

		/**
		 * Add html to our custom column
		 * @param  $column - specific post type column
		 */
		function manage_custom_column( $column )
		{
			if( $column == 'sortable-posts-order' ){
				$post = get_post( $post_ID );
				echo '<strong class="sortable-posts-order-position">' . $post->menu_order . '</strong>';
			}
		}

		/**
		 * Removes all other sortability
		 *
		 * Will be removed in later versions once we get that working.
		 * 
		 * @param  array $columns - registered post type columns
		 * @return array $columns
		 */
		function remove_sortable_columns( $columns )
		{
			$columns = array();
			return $columns;
		}

		/**
		 * Updates the menu order for each post
		 */
		function update_order()
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
		 * Sets order and orderby properties in WP_Query for sortable types only.
		 * @var object $query - Instance of WP_Query
		 */
		function order_by_sortable_posts( $query )
		{
			if( isset( $query->query_vars['post_type'] ) ) {
				if( in_array( $query->query_vars['post_type'], $this->sortable_types ) )
				{
					// User submitted orderby takes priority to allow for override
					if( !isset( $query->query_vars['orderby'] ) ) {
						$query->set( 'orderby', 'menu_order' );
					}
					
					// User submitted order takes priority to allow for override
					if( !isset( $query->query_vars['order'] ) ) {
						$query->set( 'order', 'ASC');
					}
				}
			}
		}

		/**
		 * Updates the order when a new post is inserted
		 * @param  array $data
		 * @param  array $postarr
		 * @return array
		 */
		function update_order_on_new_post( $data, $postarr )
		{
			$type = $data['post_type'];

			if( in_array( $type, $this->sortable_types ) && $data['menu_order'] == 0 )
			{
				global $wpdb;
				$data['menu_order'] = $wpdb->get_var(
					"SELECT MAX(menu_order)+1 AS menu_order FROM {$wpdb->posts} WHERE post_type='{$type}'"
				);
			}

			return $data;
		}	

	}

	new SortablePosts();
}
