<?php
/**
 * Creates the endpoints for the REST API
 *
 * @package Sortable Posts For WordPress
 * @category class
 * @author Carlos Rios
 * @since  1.0
 */

/**
 * SortablePosts_API class
 * 
 * @since  1.0
 */
class SortablePosts_API {

	/**
	 * Stores the order
	 *
	 * @since  1.0
	 * @var string
	 */
	public $order = '';

	/**
	 * Stores the starting point for updating the order
	 * 
	 * @since  1.0
	 * @var integer
	 */
	public $start = '';

	/**
	 * Stores the type of object we're saving
	 * 
	 * @since  1.0
	 * @var string
	 */
	public $obj_type = '';
	
	/**
	 * Stores the post type
	 * 
	 * @since  1.0
	 * @var string
	 */
	public $post_type = '';
	
	/**
	 * Stores the taxonomy
	 * 
	 * @since  1.0
	 * @var string
	 */
	public $taxonomy = '';
	
	/**
	 * Stores the taxonomy term
	 * 
	 * @since  1.0
	 * @var string
	 */
	public $taxonomy_term = '';

	/**
	 * Registers the REST API endpoints
	 * @since  1.0
	 */
	public function register_routes()
	{
		$namespace = 'sortable-posts';

		register_rest_route( $namespace, '/update', array(
			'methods'				=> WP_REST_Server::EDITABLE,
			'callback'				=> array( $this, 'update_item' ),
			'permission_callback'	=> array( $this, 'update_item_permissions_check' ),
			'args'					=> array(),
		));
	}

	/**
	 * Update the order of the posts
	 *
	 * @since  1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request )
	{
		// Sanitize data for database entry
		$this->prepare_item_for_database( $request );

		// Update the sort order
		$data = $this->update_sort_order();

		// Send error if update fails		
		if ( $data === false ) {
			$response = array(
				'status'		=> 400,
				'after_message'	=> '',
			);
			return new WP_Error( 'nothing-happened', __( 'Nothing happened. Try again.', 'sortable-posts' ), $response );
		}

		// Create the success response message
		$response = array(
			'code'		=> 'sortable-posts-updated',
			'message'	=> __( 'Saved successfully.', 'sortable-posts' ),
			'data'		=> array(
				'status' 		=> 200,
				'after_message'	=> '',
			),
		);

		// Return the response
		// Fix this after 4.5 return new WP_REST_Response( $response, 200 );
		return json_encode( $response );
	}

	/**
	 * Check if a given request has access to create items
	 * 
	 * @since  1.0
	 * @param WP_REST_Request $request
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request )
	{
		return current_user_can( 'publish_posts' );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @since  1.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request )
	{
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Prepare the request for database entry
	 *
	 * @since  1.0
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	public function prepare_item_for_database( $request )
	{
		$order_array = array();
		$order = $request->get_param( 'order' );

		if( ! is_array( $order ) || empty( $order ) ) {
			return new WP_Error( 'order-not-array', __( 'Order needs to be an array', 'sortable-posts' ), 500 );
		}

		// Store posts in object's order variable
		foreach( (array) $order as $post_id ){
			$post_id = sanitize_key( $post_id );
			$order_array[] = str_replace( array( 'post-', 'tag-' ), '', $post_id );
		}

		// Store order in object as an array
		$this->order = $order_array;

		// Sanitize start key and store in object
		$this->start = sanitize_key( $request->get_param( 'start' ) );

		// Set the object types
		$this->obj_type = sanitize_key( $request->get_param( 'object_type' ) );
		
		// Set the post types
		$this->post_type = sanitize_key( $request->get_param( 'post_type' ) );
		
		// Set the taxonomy
		if( !empty(sanitize_key( $request->get_param( 'taxonomy' ) )) ) {
		    $this->taxonomy = sanitize_key( $request->get_param( 'taxonomy' ) );
		}
		
		// Set the object types
		if( !empty(sanitize_key( $request->get_param( 'taxonomy' ) )) ) {
		    $this->taxonomy_term = sanitize_key( $request->get_param( 'taxonomy_term' ) );
		}
	}

	/**
	 * Returns the correct method for the type of object being sorted 
	 *
	 * @since  1.0
	 * @return WP_Error|sort update method
	 */
	protected function update_sort_order()
	{
		if( $this->obj_type == 'edit' ) {

			return $this->update_post_sort_order();

		} elseif( $this->obj_type == 'edit-tags' ) {

			return $this->update_taxonomy_sort_order();

		} else {

			$response = array(
				'status'		=> 400,
				'after_message'	=> '',
			);
			return new WP_Error( 'not-sortable', __( 'Sorry this object type is not sortable at the moment.', 'sortable-posts' ), $response );
		
		}
	}

	/**
	 * Updates the sort order for the post
	 *
	 * @since  1.0
	 * @return WP_Error|array
	 */
	protected function update_post_sort_order()
	{
		// Check if the combination of post type and taxonomy	
		$inside_tax = false;
		if( !empty($this->taxonomy) && !empty($this->taxonomy_term) ) {
		  $filter_inside_posts = apply_filters('sortable_post_inside_tax', array());
		  if( is_array( $filter_inside_posts ) ){
		    foreach ( $filter_inside_posts as $combo ) {
			if($combo['post_type'] === $this->post_type && $combo['taxonomy'] === $this->taxonomy){
			  $inside_tax = true;
			  break;
			}
		    }
		  }
		}
		if ( ! $inside_tax ) {
		  global $wpdb;

		  // Select items based on the starting point
		  $wpdb->query( "SELECT @i:= $this->start-1" );

		  // Order needs to be a comma separated string
		  $this->order = esc_sql( implode( ', ', $this->order ) );

		  // Insert the new order
		  $new_order = $wpdb->query(
			  "UPDATE {$wpdb->posts} SET menu_order = ( @i:= @i+1 )
			  WHERE ID IN ( $this->order ) ORDER BY FIELD( ID, $this->order );"
		  , ARRAY_A );
		  return $new_order;
		} else {
		  foreach( (array) $this->order as $term_id ) {
			// Get the position in the array
			$position = array_search( $term_id, $this->order );
			$position = abs( $position + 1 );
			
			// Update the term_order
			update_post_meta( $term_id, '_sortable_posts_order_' . $this->taxonomy . '_' . $this->taxonomy_term, $position );
		  }
		  return;
		}
	}

	/**
	 * Updates the sort order for taxonomies
	 *
	 * @since  1.0
	 * @return WP_Error|array
	 */
	protected function update_taxonomy_sort_order()
	{
		foreach( (array) $this->order as $term_id ) {
			// Get the position in the array
			$position = array_search( $term_id, $this->order );
			$position = abs( $position + 1 );

			// Update the term_order
			update_term_meta( $term_id, 'term_order', $position );
		}
		return;
	}

}

/**
 * Initiates the API
 *
 * @since  1.0
 */
function init_sortable_posts_api()
{
	$controller = new SortablePosts_API;
	$controller->register_routes();
}

// Hook into REST API
add_action( 'rest_api_init', 'init_sortable_posts_api' );
