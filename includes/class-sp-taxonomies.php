<?php
/**
 * Makes taxonomies sortable!
 *
 * @package Sortable Posts For WordPress
 * @category class
 * @author Carlos Rios
 * @version 0.1
 */

class SortablePosts_Taxonomies {

	/**
	 * Stores an array of sortable taxonomies
	 * @var array
	 * @since 0.1
	 */
	public $sortable_taxes = array();

	/**
	 * Sets the sortable taxonomies in the class
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
	 * @since 0.1
	 */
	public function register_hooks()
	{
		// Only loads if this is a Sortable Posts registered taxonomy
		add_action( 'admin_init', array( $this, 'register_custom_taxonomy_hooks' ) );
		add_filter( 'terms_clauses', array( $this, 'orderby_sortable_taxonomies' ), 10, 3 );
	}

	/**
	 * Register sortable taxonomy columns
	 * @since 0.1
	 */
	public function register_custom_taxonomy_hooks()
	{
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
	 * @param  array  $clauses SQL clauses.
	 * @param  mixed  $taxonomies Taxonomy name.
	 * @param  array  $args Query arguments.
	 * @return array Return SQL clauses.
	 */
	public function orderby_sortable_taxonomies( $clauses, $taxonomies, $args )
	{
		global $wpdb;

		// Need to rework this
		if ( isset( $args['orderby'] ) && $args['orderby'] !== 'name' ){
			return $clauses;
		}

		// Accept only single taxonomy queries & only if taxonomy is sortable
		if ( ! in_array( $taxonomies[0], $this->sortable_taxes ) ) {
			return $clauses;
		}

		// Join termmeta to terms tables
		$clauses['join'] .= " INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id";

		// Set order to default to ascending
		$order = strtoupper( $args['order'] );
		if ( ! in_array( $order, array('ASC', 'DESC') ) ) {
			$order = 'ASC';
		}
		$orderby = "ORDER BY tm.meta_value {$order}";

		if ( ! empty( $clauses['orderby'] ) ) {
			// insert custom column in front of current column
			$clauses['orderby'] = str_replace( 'ORDER BY', "{$orderby},", $clauses['orderby'] );
		} else {
			// sort by custom sort column and name
			$clauses['orderby'] = "{$orderby}, name";
		}

		// Add to WHERE clause
		$clauses['where'] .= " AND tm.meta_key = 'term_order'";

		return $clauses;
	}

	/**
	 * Add html to our custom taxonomy column
	 * @param $output column html output
	 * @param $column specific taxonomy column
	 * @param $term_id id of the current term
	 * @since 0.1
	 */
	public function manage_custom_taxonomy_column( $output, $column, $term_id )
	{
		$term_position = get_term_meta( $term_id, 'term_order', true );

		if( $column == 'sortable-posts-order' ) {
			$output .= '<strong class="sortable-posts-order-position">' . $term_position . '</strong>';
		}

		return $output;
	}

	/**
	 * Adds new term information
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

			add_term_meta( $term_id, 'term_order', $term_count + 1, true );
		}
		return;
	}

}

/**
 * Initiates SortablePosts_Taxonomies
 */
function init_sp_taxonomies()
{
	$taxes = new SortablePosts_Taxonomies();
	$taxes->set_sortable_taxes();
	$taxes->register_hooks();
}

// Get it started
init_sp_taxonomies();