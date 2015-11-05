## Sortable Posts For WordPress
Sortable Posts is a small framework for WordPress that adds sortability to the post edit screen of any post type you choose.

[display]: https://github.com/CarlosRios/sortable-posts-wp/raw/master/assets/images/display_image.jpg "Sortable Posts For WordPress"

**Version:** 0.1.0
**Requires at least:** 4.0
**Tested up to:** 4.3.1

## Installation
To install Sortable Posts, unzip the "sortable-posts" folder to your plugins directory, and activate.

## What Sortable Posts Does
Sortable Posts updates a list of posts via the menu_order built into WordPress.

## Using Sortable Posts In Your Theme / Plugin
Sortable Posts currently allows users to add post types to the list of sortable post types by either adding them in the options panel or by adding them via a custom filter. Should you want to add them via a filter you can use the `sortable_post_types` filter and return an array with the the post types you'd like to make sortable.

``` php
	add_filter( 'sortable_post_types', 'add_sortable_post_types' );
	function add_sortable_post_types( $types ) {
		return array( 'team', 'events' );
	}
```

## Overriding the default sort functionality
Sortable Posts changes the default 'order_by' parameter of all queries using a post type that has been made sortable just to make things easier. However, should you wish to override that functionality simply override it in your WP_Query.

```php
	$query = new WP_Query( array(
		'post_type'		=> $sortable_post_type,
		'order_by'		=> 'title'
	));
```

## Recent Changes

### 0.1.0
 - initial commit