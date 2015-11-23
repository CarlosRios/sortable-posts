<?php
/**
 * Creates the endpoints for the REST API
 *
 * @package Sortable Posts For WordPress
 * @category class
 * @author Carlos Rios
 * @since  0.1.1
 */

class SortablePostsAPI extends WP_REST_Controller {

	/**
	 * Stores the order
	 * @var string
	 */
	public $order = '';

	/**
	 * Stores the starting point for updating the order
	 * @var integer
	 */
	public $start = '';

	/**
	 * Registers the REST API endpoints
	 */
	public function register_routes()
	{
		$namespace = 'sortable-posts';

		register_rest_route( $namespace, '/update', array(
			'methods'				=> WP_REST_Server::EDITABLE,
			'callback'				=> array( $this, 'update_item' ),
			'permission_callback'	=> array( $this, 'update_item_permissions_check' ),
			'args'					=> $this->get_endpoint_args_for_item_schema( false )
		));
	}

	/**
	 * Update the order of the posts
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
		if ( $data == false ) {
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
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Check if a given request has access to create items
	 *
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
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	public function prepare_item_for_database( $request )
	{
		$order_array = array();
		$order = $request->get_param( 'order' );

		if( ! is_array( $order ) ) {
			return new WP_Error( 'order-not-array', __( 'Order needs to be an array', 'sortable-posts' ), 500 );
		}

		// Store posts in object's order variable
		foreach( (array) $order as $post_id ){
			$post_id = sanitize_key( $post_id );
			$order_array[] = str_replace( 'post-', '', $post_id );
		}

		// Store order in object as a comma separated string
		$this->order = implode( ', ', $order_array );

		// Sanitize start key and store in object
		$this->start = sanitize_key( $request->get_param( 'start' ) );
	}

	/**
	 * Updates the sort order in the database
	 * @return WP_Error|array
	 */
	protected function update_sort_order()
	{
		global $wpdb;

		// Select items based on the starting point
		$wpdb->query( "SELECT @i:= $this->start-1" );
		
		// Insert the new order
		$new_order = $wpdb->query(
			"UPDATE wp_posts SET menu_order = ( @i:= @i+1 )
			WHERE ID IN ( $this->order ) ORDER BY FIELD( ID, $this->order );"
		, ARRAY_A );

		return $new_order;
	}

}

/**
 * Initiates the API
 */
function init_sortable_posts_api()
{
	$controller = new SortablePostsAPI;
	$controller->register_routes();
}

// Hook into REST API
add_action( 'rest_api_init', 'init_sortable_posts_api' );
