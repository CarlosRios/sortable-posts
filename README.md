## Sortable Posts For WordPress
Sortable Posts is a small framework for WordPress that adds sortability to the post edit screen of any post type you choose. You can view the docs for it [here](http://sortable.texaswebsitemanagement.com).

**Version:** 0.2.1
**Requires at least:** 4.0
**Tested up to:** 4.2.4

## Installation
To install Sortable Posts, unzip the "sortable-posts" folder anywhere in your theme or plugin. After its in your project's directory, you can include sortable-posts.php

## Using Sortable Posts With Your Post Types
After you've installed Sortable Posts into your project all you need to do is tell it which post types you'd like to make sortable. Add the sortable_post_types filter to your project and return an array with the the post types you'd like to make sortable.

		add_filter('sortable_post_types', 'initSortablePosts');
		function initSortablePosts( $types ){
			return array( 'team', 'page' );
		}

## Recent Changes

### 0.2.1
 - fix manage column error
 - fix error with columns unsetting
 - fix post array issue when adding new post