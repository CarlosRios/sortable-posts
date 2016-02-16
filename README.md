## Sortable Posts
Sortable Posts is a small plugin for WordPress that adds sortability to post types and taxonomies from the admin panel.

**Version:**			1.1.1 
**Requires at least:**	4.4  
**Tested up to:**		4.4.2  
**License:**			GPLv2 or later  

## Installation
Sortable Posts requires the REST API that is currently being merged into 4.4. To install Sortable Posts, unzip the "sortable-posts" folder to your plugins directory, and activate.

## What Sortable Posts Does
Sortable Posts uses and easy to use drag and drop ui to allow users to update the order of posts and taxonomy terms. Sortable Posts automatically arranges your posts and taxonomy terms on the frontend to match the order on the backend as well.

## Using Sortable Posts In Your Theme / Plugin
Sortable Posts currently allows users to add post types to the list of sortable post types and taxonomies by either adding them in the options panel or by adding them via a custom filter. Should you want to add them via a filter you can use the `sortable_post_types` for posts and the `sortable_taxonomies` filter for taxonomies.

``` php
	add_filter( 'sortable_post_types', 'add_sortable_post_types' );
	function add_sortable_post_types( $types ) {
		$types = array_merge( $types, array( 'your_custom_post_type' ) );
		return $types;
	}
```

```php
	add_filter( 'sortable_taxonomies', 'add_sortable_taxes' );
	function add_sortable_taxes( $taxes ) {
		$taxes = array_merge( $taxes, array( 'your_taxonomy' ) );
		return $taxes;
	}
```

## Overriding the default sort functionality for posts
Sortable Posts changes the default 'order_by' parameter of all queries using a post type that has been made sortable just to make things easier. However, should you wish to override that functionality simply override it in your WP_Query.

```php
	$query = new WP_Query( array(
		'post_type'		=> $sortable_post_type,
		'order_by'		=> 'title'
	));
```

## Overriding the default sort functionality for taxonomies
Sortable Posts automatically orders your taxonomy's terms on the frontend as well as on the backend. If you wish to disable this feature you can change the orderby field in your query to order your terms by any of the [default taxonomy orderby parameters](https://codex.wordpress.org/Function_Reference/get_terms#Possible_Arguments).

```php
	$args = array(
		'orderby'	=> 'name',
	);
	$terms = get_terms( 'your_taxonomy', $args );
```

## Recent Changes

### 1.1.1
 - fix issue with sortable posts looking for assets in the wrong folder.
 - update docblocks

### 1.1
 - remove using WP_REST_Controller class because it was not included in 4.4

### 1.0
 - add sortable taxonomies with the help of the WordPress 4.4 term metadata improvements.
 - updated plugin to use REST API to power its ajax.
 - visual modifications to the post edit screen.
 - added an alert message when the sort update succeeds or fails.
 - initial commit for plugin version