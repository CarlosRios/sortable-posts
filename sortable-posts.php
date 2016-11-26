<?php
/**
 * Plugin Name: Sortable Posts
 * Description: Sortable Posts is a small plugin for WordPress that adds sortability to the post edit screen of any post type you choose.
 * Author: Carlos Rios
 * Author URI: http://www.texaswebsitemanagement.com
 * Version: 1.1.4
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

	/**
	 * SortablePosts class
	 * 
	 * @since  1.0
	 */
	class SortablePosts {

		/**
		 * Sortable Posts version
		 *
		 * @since  1.0
		 * @var string
		 */
		public $version = '1.1.4';

		/**
		 * List of sortable post types
		 *
		 * @since  1.0
		 * @var array
		 */
		public $sortable_types = array();

		/**
		 * Initiates Sortable Posts
		 *
		 * @since  1.0
		 */
		public function __construct()
		{
			$this->includes();
			$this->set_sortable_types();
			$this->sortable_posts_hooks();
		}

		/**
		 * Includes all necessary files
		 *
		 * @since  1.0
		 */
		public function includes()
		{
			if ( is_admin() ) {
				require_once( 'includes/class-sp-settings.php' );
			}
			require_once( 'includes/class-sp-api.php' );
			require_once( 'includes/class-sp-taxonomies.php' );
		}

		/**
		 * Registers all of our hooks
		 *
		 * @since  1.0
		 */
		public function sortable_posts_hooks()
		{
			add_action( 'admin_init', array( $this, 'register_custom_columns' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
			add_action( 'admin_head', array( $this, 'add_styles_to_header' ) );
			add_action( 'admin_body_class', array( $this, 'add_classes_to_body' ) );
			add_action( 'pre_get_posts', array( $this, 'order_by_sortable_posts' ),99999 );
			add_filter( 'wp_insert_post_data', array( $this, 'update_order_on_new_post' ), 99, 2 );
			add_action( 'admin_notices', array( $this, 'status_update_html' ) );
		}

		/**
		 * Sets the sortable post types in the class
		 *
		 * @since  1.0
		 * @uses sortable_posts_types hook provided in themes or plugins.
		 */
		function set_sortable_types()
		{
			$types = get_option( 'sortable_posts', array() );

			// Check that we're working with an array
			if( is_array( $types ) ){

				// Add backwards compatibility for plugins and themes that use Sortable Posts
				$types = array_unique( array_merge( $types, apply_filters( 'sortable_post_types', array() ) ) );

				if( is_array( $types ) && !empty( $types ) ) {
					$this->sortable_types = $types;
				}

			}
			
			return;
		}

		/**
		 * Registers the Sortable Posts custom columns for each post type
		 *
		 * @since  1.0
		 */
		function register_custom_columns()
		{
			// Add column to each registered post type.
			foreach( (array) $this->sortable_types as $type )
			{
				if( post_type_exists( $type ) )
				{
					// Add column.
					add_filter( "manage_{$type}_posts_columns", array( __CLASS__, 'create_custom_column' ) );

					// Add html to the row.
					add_action( "manage_{$type}_posts_custom_column", array( __CLASS__, 'manage_custom_column' ) );

					// Remove column sortability for all other columns.
					add_filter( "manage_edit-{$type}_sortable_columns", array( __CLASS__, 'remove_sortable_columns' ) );
				}
			}
		}

		/**
		 * Registers the scripts for Sortable Posts
		 *
		 * @since  1.0
		 */
		function register_scripts()
		{
			global $wp_query;

			// Set the starting point for the menu_order.
			if( isset( $wp_query->query_vars['paged'] ) ) {
				$start = ($wp_query->query_vars['paged'] == 1 || $wp_query->query_vars['paged'] == 0 ) ? 1 : ($wp_query->query_vars['posts_per_page'] * $wp_query->query_vars['paged']) - $wp_query->query_vars['posts_per_page'] + 1;
			}else{
				$start = 1;
			}

			// Get the object type to send to REST API
			$obj_type = get_current_screen();
			
			// Create settings for localization
			$settings = array(
				'root'		=> esc_url_raw( rest_url() ),
				'nonce'		=> wp_create_nonce( 'wp_rest' ),
				'start'		=> $start,
				'obj_type'	=> $obj_type->base,
				'taxonomy'	=> (isset($wp_query->query_vars['taxonomy']) ? $wp_query->query_vars['taxonomy'] : ''),
				'taxonomy_term'	=> (isset($wp_query->query_vars['term']) ? $wp_query->query_vars['term'] : ''),
			);

			// Load scripts
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'sortable-posts-js', plugins_url( '/sortable-posts/assets/js/sortable-posts.js' ), array( 'jquery' ) );
			wp_localize_script( 'sortable-posts-js', 'WP_API_Settings', $settings );

			// CSS
			wp_enqueue_style( 'sortable-posts-css', plugins_url( '/sortable-posts/assets/css/sortable-posts.css' ) );
		}

		/**
		 * Adds class to the body when on a Sortable Posts post type
		 *
		 * @since  1.0
		 * @param array $classes
		 */
		function add_classes_to_body( $classes )
		{
			$classes .= ' sortable-posts ';
			return $classes;
		}

		/**
		 * Adds admin theme styles to admin header
		 * 
		 * @since  1.0
		 */
		function add_styles_to_header()
		{
			global $_wp_admin_css_colors;
			$user_admin_color = get_user_option( 'admin_color' );
			$colors = $_wp_admin_css_colors[$user_admin_color]->colors; ?>
			<style>
				.wp-list-table #the-list tr:hover .sortable-posts-order{
					border-left-color: <?php echo $colors[3]; ?>;
				}
				.sortable-posts-placeholder{
					background: <?php echo $colors[3]; ?> !important;
				}
			</style>
			<?php
		}

		/**
		 * Creates the custom column
		 *
		 * @since  1.0
		 * @param  array $columns - registered post type columns
		 * @return array $columns
		 */
		public static function create_custom_column( $columns )
		{
			$order = array( 'sortable-posts-order' => '<span class="dashicons dashicons-sort"></span>' );
			$columns = array_merge( $order, $columns );
			return $columns;
		}

		/**
		 * Add html to our custom column
		 *
		 * @since  1.0
		 * @param  $column - specific post type column
		 */
		public static function manage_custom_column( $column )
		{
			global $post, $wp_query;

			if( $column == 'sortable-posts-order' ){
				$order = $post->menu_order;
				if(isset($wp_query->query_vars['taxonomy'])) {
				  $filter_inside_posts = apply_filters('sortable_post_inside_tax', array());
				  if( is_array( $filter_inside_posts ) ){
				    foreach ( $filter_inside_posts as $combo ) {
					if($combo['post_type'] === $post->post_type && $combo['taxonomy'] === $wp_query->query_vars['taxonomy']){
					  $order = get_post_meta( $post->ID, '_sortable_posts_order_' . $wp_query->query_vars['taxonomy'] . '_' . $wp_query->query_vars['term'], true );
					  break;
					}
				    }
				  }
				}
				echo '<strong class="sortable-posts-order-position">' . $order . '</strong>';
			}
		}

		/**
		 * Removes all other sortability
		 *
		 * Will be removed in later versions once we get that working.
		 *
		 * @since  1.0
		 * @param  array $columns - registered post type columns
		 * @return array $columns
		 */
		public static function remove_sortable_columns( $columns )
		{
			$columns = array();
			return $columns;
		}

		/**
		 * Sets order and orderby properties in WP_Query for sortable types only.
		 *
		 * @since  1.0
		 * @var object $query - Instance of WP_Query
		 */
		function order_by_sortable_posts( $query )
		{
			global $wp_query;
			//Get the post type
			$post_type = '';
			if(isset($query->query_vars['post_type'])) {
			  $post_type = $query->query_vars['post_type'];
			}
			// On the archive page of the taxonomy term the post type on the query is page
			if( is_archive() && $post_type === 'page' ) {
			  $post_type = get_post_type();
			}
			//No Post type? Stop!
			if(empty($post_type)) {
			  return;
			}
			// Check if post type is set and if its sortable
			if( in_array( $post_type, $this->sortable_types ) ) {
				// Detect taxonomy and term
				$taxonomy = $taxonomy_term = '';
				$queried = get_queried_object();
				if(isset($queried->taxonomy)) {
				  $taxonomy = $queried->taxonomy;
				  $taxonomy_term = $queried->slug;
				} elseif(isset($query->query['tax_query'])){
                                  if ( empty( $query->query['tax_query'] ) ) {
                                    return false;
                                  }
				  // This check if a custom WP_query
				  $taxonomy = $query->query['tax_query'][0]['taxonomy'];
				  $taxonomy_term = $query->query['tax_query'][0]['terms'];
				}
				$inside_tax = 0;
				// Check if array need to sorted based the term parent
				if($taxonomy !== '') {
				  $filter_inside_posts = apply_filters('sortable_post_inside_tax', array());
				  if( is_array( $filter_inside_posts ) ){
				    foreach ( $filter_inside_posts as $combo ) {
					if($combo['post_type'] === $post_type && $combo['taxonomy'] === $taxonomy){
					  $inside_tax = true;
					  break;
					}
				    }
				  }
				}
				if ( !$inside_tax ) {
				  // Override on admin
				  if( is_admin() ) {
					  $query->set( 'orderby', 'menu_order' );
					  $query->set( 'order', 'ASC' );
				  } else {
					  // User submitted orderby takes priority to allow for override in frontend
					  if( ! isset( $query->query_vars['orderby'] ) ) {
						  $query->set( 'orderby', 'menu_order' );
					  }

					  // User submitted order takes priority to allow for override in frontend
					  if( ! isset( $query->query_vars['order'] ) ) {
						  $query->set( 'order', 'ASC');
					  }
				  }
				} else {
				  // If admin or custom WP_query
				  if( is_admin() || !is_archive() && isset($query->query['tax_query'])) {
					  $query->set( 'orderby', 'meta_value_num' );
					  $query->set( 'order', 'ASC' );
					  $query->set( 'meta_query',
					    array( 
						  'relation' => 'OR',
						  array(  
							'key' => '_sortable_posts_order_' . $taxonomy . '_' . $taxonomy_term,
							'compare' => '!=',
							'value' => ''
						    ),
						  array(  
							'key' => '_sortable_posts_order_' . $taxonomy . '_' . $taxonomy_term,
							'compare' => 'NOT EXISTS',
							'value' => ''
						    )
						  )
					    );
				  } else {
					    //If is an archive
					    $args = $wp_query->query_vars;
					    $new_args = array();
					    $new_args['orderby'] = 'meta_value_num';
					    $new_args['order'] = 'ASC';
					    $new_args[ 'meta_query'] = array( 
						    'relation' => 'OR',
						    array(  
							  'key' => '_sortable_posts_order_' . $taxonomy . '_' . $taxonomy_term,
							  'compare' => '!=',
							  'value' => ''
							),
						    array(  
							  'key' => '_sortable_posts_order_' . $taxonomy . '_' . $taxonomy_term,
							  'compare' => 'NOT EXISTS',
							  'value' => ''
							)
						    );
					    $wp_query = new WP_Query(array_merge($args, $new_args));
					    return $wp_query;
					  } 
				  }
								
			}
			return $query;
		}

		/**
		 * Updates the order when a new post is inserted
		 *
		 * @since  1.0
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

		/**
		 * Renders a status update message
		 *
		 * @since  1.0
		 */
		public function status_update_html()
		{
			?>
			<div id="sortable-posts-status">
				<strong id="sortable-posts-status-head"></strong>
				<div id="sortable-posts-status-message"></div>
			</div>
			<?php
		}

		/**
		 * Things to fire during activation
		 *
		 * @since   1.1.2
		 * @return
		 */
		public static function activate()
		{
			flush_rewrite_rules();
		}

	}

	new SortablePosts();

	// Flush rewrite rules so that WP_REST_API is available after
	register_activation_hook( __FILE__, array( 'SortablePosts', 'activate' ) );
}
