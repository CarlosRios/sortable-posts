<?php
/**
 * Creates the settings for Sortable Posts
 *
 * @package Sortable Posts For WordPress
 * @category class
 * @author Carlos Rios
 */

class SortablePostsSettings {

	public function __construct()
	{
		add_action( 'admin_init', array( $this, 'create_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Create the menu to be displayed in admin side
	 */
	public function register_menu()
	{
		add_options_page( __( 'Sortable Posts For WordPress', SortablePosts::$textdomain ), __( 'Sortable Post Types', SortablePosts::$textdomain ), 'administrator', 'sortable_posts_settings', array( $this, 'settings_html' ) );
	}

	/**
	 * Creates the settings and renders them with the correct html
	 * @echo html - rendered html for settings page
	 */
	public function settings_html()
	{
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET['tab'] : 'settings';
		?>
		<div class="wrap">

			<h2><?php _e( 'Sortable Posts For WordPress', SortablePosts::$textdomain ); ?></h2>

			<h2 class="nav-tab-wrapper">
				<a href="?page=sortable_posts_settings&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'sortable_posts' );
				do_settings_sections( 'sortable_posts' );
				submit_button();
				?>
			</form>

		</div>
	<?php
	}

	/**
	 * Creates the sections & fields for WordPress Settings API and registers the settings.
	 */
	public function create_settings()
	{
		// Sortable settings section
		add_settings_section( 'settings', 'Sortable Types', array( $this, 'settings_description' ), 'sortable_posts' );

		// Post type settings
		add_settings_field( 'sortable-post-types', 'Sortable Post Types', array( $this, 'posts_field_handler' ), 'sortable_posts', 'settings', array( 'id' => 'sortable-post-types', 'type' => 'text', 'desc' => 'These post types will magically become sortable!' ) );
		register_setting( 'sortable_posts', 'sortable_posts', array( $this, 'sanitize_settings' ) );

		// Taxonomy settings
		add_settings_field( 'sortable-taxonomy-types', 'Sortable Taxonomies', array( $this, 'taxonomies_field_handler' ), 'sortable_posts', 'settings', array( 'id' => 'sortable-taxonomy-types', 'type' => 'text', 'desc' => 'These taxonomies will magically become sortable!' ) );
		register_setting( 'sortable_posts', 'sortable_taxonomies', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Echo's the information below the title of the page.
	 * @echo string
	 */
	public function settings_description() {
		echo '<p>Choose which types will be sortable.</p>';
	}

	/**
	 * Renders the field's html and description
	 * @param array $data - data thats passed in when the field is created
	 * @echo  $field - echo's the html for this field
	 */
	public function posts_field_handler( $data )
	{
		$args = array(
			'_builtin' => false,
		);

		// Get the registered post types and post type option
		$registered_post_types = get_post_types( $args, 'objects' );
		$post_type_option = get_option( 'sortable_posts', array() ); ?>

		<fieldset id="sortable-posts-fieldset">

			<?php
			if( !empty( $registered_post_types ) ) :
			
				foreach( (array) $registered_post_types as $type ) :

					// Create checked variable
					$checked = '';

					// Check if post type is in post type option
					if( in_array( $type->name, $post_type_option ) ) {
						$checked = 'checked="checked"';
					} ?>

					<label for="sortable_post_type_<?php echo $type->name; ?>">
						
						<input id="sortable_post_type_<?php echo $type->name; ?>" type="checkbox" name="sortable_posts[]" value="<?php echo $type->name; ?>" <?php echo $checked; ?>></input> <?php echo $type->labels->name; ?>

					</label>

					<br>

				<?php endforeach; ?>

			<?php endif; ?>

		</fieldset>
		
		<?php
	}

	/**
	 * Renders the field's html and description
	 * @param array $data - data thats passed in when the field is created
	 * @echo  $field - echo's the html for this field
	 */
	public function taxonomies_field_handler( $data )
	{
		// Get the registered taxonomies and taxonomy option
		$registered_taxonomies = get_taxonomies( '', 'objects' );
		$taxonomy_option = get_option( 'sortable_taxonomies', array() );
		?>

		<fieldset id="sortable-taxonomies-fieldset">

			<?php
			if( !empty( $registered_taxonomies ) ) :

				foreach( (array) $registered_taxonomies as $tax ) :

					// Create checked variable
					$checked = '';

					// Check if taxonomy is in taxonomy option
					if( !empty( $taxonomy_option ) && in_array( $tax->name, $taxonomy_option ) ){
						$checked = 'checked="checked"';
					} ?>

					<label for="sortable_taxonomy_<?php echo $tax->name; ?>">

						<input id="sortable_taxonomy_<?php echo $tax->name; ?>" type="checkbox" name="sortable_taxonomies[]" value="<?php echo $tax->name; ?>" <?php echo $checked; ?>></input> <?php echo $tax->labels->name; ?>

					</label>

					<br>

				<?php endforeach; ?>

			<?php endif; ?>

		</fieldset>

	<?php
	}

	/**
	 * Cleans up the settings before they're stored in WordPress
	 * @param  array $input - the data being stored
	 * @return array - sanitized values
	 */
	public function sanitize_settings( $input )
	{
		$output = array();
		foreach( $input as $key => $val ) {
			if( isset ( $input[$key] ) ) {
				$output[$key] = strip_tags( stripslashes( $input[$key] ) );
			}
		}
		return apply_filters( 'sanitize_settings', $output, $input );
	}

}

return new SortablePostsSettings();