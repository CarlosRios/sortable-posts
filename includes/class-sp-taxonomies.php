<?php
/**
 * Makes taxonomies sortable!
 *
 * @package Sortable Posts For WordPress
 * @category class
 * @author Carlos Rios
 * @version 1.0
 */

/**
 * SortablePosts_Taxonomies class
 * 
 * @since  1.0
 */
class SortablePosts_Taxonomies {

	/**
	 * Stores an array of sortable taxonomies
	 *
	 * @since  1.0
	 * @var array
	 */
	public $sortable_taxes = array();

	/**
	 * Sets the sortable taxonomies in the class
	 *
	 * @since  1.0
	 * @uses sortable_taxonomies hook provided in themes or plugins.
	 */
	public function set_sortable_taxes()
	{
		$taxes = get_option( 'sortable_taxonomies', array() );

		// Add backwards compatibility for plugins and themes that use Sortable Posts
		$taxes = array_unique( array_merge( $taxes, apply_filters( 'sortable_taxonomies', array() ) ) );

		if( is_array( $taxes ) && !empty( $taxes ) ) {
			$this->sortable_taxes = $taxes;
		}
		
		return;
	}

	/**
	 * Registers the required hooks
	 * 
	 * @since 1.0
	 */
	public function register_hooks()
	{
		add_action( 'admin_init', array( $this, 'register_custom_taxonomy_hooks' ) );
		add_filter( 'terms_clauses', array( $this, 'orderby_sortable_taxonomies' ), 10, 3 );
	}

	/**
	 * Register sortable taxonomy columns
	 * 
	 * @since 1.0
	 */
	public function register_custom_taxonomy_hooks()
	{
		add_filter( 'pre_update_option_sortable_taxonomies', array( $this, 'check_sortable_taxonomy_terms_for_term_meta' ), 10, 2 );

		// Add column to each registered taxonomy.
		foreach( (array) $this->sortable_taxes as $tax )
		{
			if( taxonomy_exists( $tax ) )
			{
				// Add column.
				add_filter( "manage_edit-{$tax}_columns", array( 'SortablePosts', 'create_custom_column' ), 10, 1 );

				// Add html to the row.
				add_filter( "manage_{$tax}_custom_column", array( $this, 'manage_custom_taxonomy_column' ), 10, 3 );

				// Remove column sortability for all other columns.
				add_filter( "manage_edit-{$tax}_sortable_columns", array( 'SortablePosts', 'remove_sortable_columns' ) );

				// Update the order when a new term is created
				add_action( 'create_term', array( $this, 'add_new_term_data' ), 10, 3 );
			}
		}
	}

	/**
	 * Used for sorting taxonomies.
	 *
	 * @since  1.0
	 * @param  array  $clauses SQL clauses.
	 * @param  mixed  $taxonomies Taxonomy name.
	 * @param  array  $args Query arguments.
	 * @return array Return SQL clauses.
	 */
	public function orderby_sortable_taxonomies( $clauses, $taxonomies, $args )
	{
		if( is_admin() ) {
                    global $wpdb;
                    if(function_exists('get_current_screen')) {
                        $screen = get_current_screen();
			if( !empty( $screen ) ) {
                        	if( $screen->base === 'edit-tags' || $screen->base === 'post' || $screen->base === 'edit' ) {
                          		return $clauses;
                        	}
			}
                    }
                    // Need to rework this. Allows users to override orderby param
                    if ( isset( $args['orderby'] ) && $args['orderby'] !== 'name' ){
                            return $clauses;
                    }

                    // taxonomies might come as associative array.
                    // make sure $taxonomy_values[0] won't trigger a php warning
                    $taxonomy_values = array_values( $taxonomies );

                    // Accept only single taxonomy queries & only if taxonomy is sortable
                    if ( ! in_array( $taxonomy_values[0], $this->sortable_taxes ) ) {
                            return $clauses;
                    }

                    // Join termmeta to terms tables
                    $clauses['join'] .= " INNER JOIN {$wpdb->termmeta} tm ON (t.term_id = tm.term_id AND tm.meta_key = 'term_order')";

                    // Set order to default to ascending
                    $order = strtoupper( $args['order'] );
                    if ( ! in_array( $order, array('ASC', 'DESC') ) ) {
                            $order = 'ASC';
                    }
                    $orderby = "ORDER BY ABS(tm.meta_value) {$order}";

                    if ( ! empty( $clauses['orderby'] ) ) {
                            // insert custom column in front of current column
                            $clauses['orderby'] = str_replace( 'ORDER BY', "{$orderby},", $clauses['orderby'] );
                    } else {
                            // sort by custom sort column and name
                            $clauses['orderby'] = "{$orderby}, name";
                    }
                }

		return $clauses;
	}

	/**
	 * Add html to our custom taxonomy column
	 *
	 * @since  1.0
	 * @param $output column html output
	 * @param $column specific taxonomy column
	 * @param $term_id id of the current term
	 */
	public function manage_custom_taxonomy_column( $output, $column, $term_id )
	{
		$term_position = get_term_meta( $term_id, 'term_order', true );

		if( $column == 'sortable-posts-order' ) {

			// Display nothing if term_position is equal to nothing
			if( $term_position == 0 ) {
				$term_position = '';
			}

			$output .= sprintf( '<strong class="sortable-posts-order-position">%1$s</strong>', $term_position );
		}

		return $output;
	}

	/**
	 * Adds new term information
	 *
	 * @since  1.0
	 * @param  integer $term_id term id
	 * @param  integer $tt_id term's taxonomy id
	 */
	public function add_new_term_data( $term_id, $tt_id, $taxonomy )
	{
		if( in_array( $taxonomy, $this->sortable_taxes ) ) {

			// Gather all terms that have a term_order meta_key
			$args = array(
				'meta_query' => array(
					'meta_key'		=> sanitize_key( 'term_order' ),
					'meta_compare'	=> 'EXISTS',
				)
			);

			// Gather previously added terms to count them
			$terms = get_terms( $taxonomy, $args );
			$term_count = count( $terms );
			$term_count = abs( $term_count + 1 );

			add_term_meta( $term_id, 'term_order', $term_count, true );
		}
		return;
	}

	/**
	 * Checks for registered taxonomy terms with missing term_order meta
	 * and adds the meta if it is not present
	 *
	 * @since  1.0
	 * @return array
	 */
	public function check_sortable_taxonomy_terms_for_term_meta( $new_value, $old_value )
	{
		// If values are not arrays then save an array
		if( ! is_array( $new_value ) || ! is_array( $old_value ) ) {
			return array();
		}

		// Compare arrays to see if they have changed
		$array_compare = array_diff( $new_value, $old_value );

		// Continue if arrays are different
		if( ! empty( $array_compare ) ) {

			// Search for missing term_meta if values have changed
			foreach( (array) $array_compare as $taxonomy ) {

				// Get the terms for this taxonomy
				$terms = get_terms( $taxonomy, array( 'hide_empty' => 0 ) );

				if( ! empty( $terms ) ) {
					// Search terms for term_order meta
					foreach( $terms as $term ) {
						$term_order = get_term_meta( $term->term_id, 'term_order', $single = true );

						// If the term_order is empty then add the metadata
						// Need to rework this to add default value as increment of the highest previous value
						if( empty( $term_order ) ) {
							add_term_meta( $term->term_id, 'term_order', '0', true );
						}
					}
				}

			}

		}

		// Save new value
		return $new_value;
	}

}

/**
 * Initiates SortablePosts_Taxonomies
 *
 * @since  1.0
 */
function init_sp_taxonomies()
{
	$taxes = new SortablePosts_Taxonomies();
	$taxes->set_sortable_taxes();
	$taxes->register_hooks();
}

// Get it started
init_sp_taxonomies();
