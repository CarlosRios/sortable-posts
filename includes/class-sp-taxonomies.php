<?php
/**
 * Makes taxonomies sortable
 *
 * @package Sortable Posts For WordPress
 * @category class
 * @author Carlos Rios
 * @since  0.1.2
 */

class SortablePosts_Taxonomies {

	/**
	 * Stores an array of sortable taxonomies
	 * @var array
	 */
	public $sortable_taxes = array();

	public function register_hooks()
	{
		add_action( 'admin_init', array( $this, 'register_custom_taxonomy_columns' ) );
	}

	public function register_custom_taxonomy_columns()
	{
		// Add column to each registered taxonomy.
		foreach( (array) $this->sortable_taxes as $tax )
		{
			if( post_type_exists( $tax ) )
			{
				// Add column.
				add_filter( "manage_edit-{$tax}_columns", 'create_custom_taxonomy_column' );

				// Add html to the row.

				// Remove column sortability for all other columns.
				//add_filter( "manage_{$tax}_custom_column", 'manage_custom_taxonomy_column', 10, 3 );
			}
		}
	}

	/**
	 * Creates the custom taxonomy column
	 * @param  array $columns - registered taxonomy columns
	 * @return array $columns
	 */
	public function create_custom_taxonomy_column()
	{
		
	}

	/**
	 * Add html to our custom taxonomy column
	 * @param  $column - specific taxonomy column
	 */
	public function manage_custom_taxonomy_column()
	{
		
	}

}

/**
 * Initiates SortablePosts_Taxonomies
 */
function init_sp_taxonomies()
{
	$object = new SortablePosts_Taxonomies();
	$object->register_hooks();
}

// Get it started
init_sp_taxonomies();