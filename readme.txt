=== Sortable Posts ===
Contributors: carlosrios
Author URI: http://www.texaswebsitemanagement.com
Plugin URI: https://github.com/CarlosRios/sortable-posts-wp
Tags: custom post order, js post order, page order, post order, posts order, sort custom post types, sort posts, sort taxonomies, sortable taxonomies, sortable post types
Requires at least: 4.4
Tested up to: 4.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sortable Posts is a small plugin for WordPress that adds sortability to post types and taxonomies from the admin panel.

== Description ==
Sortable Posts uses and easy to use drag and drop ui to allow users to update the order of posts and taxonomy terms. Sortable Posts automatically arranges your posts and taxonomy terms on the frontend to match the order on the backend as well.

## Using Sortable Posts In Your Theme / Plugin
Sortable Posts currently allows users to add post types to the list of sortable post types and taxonomies by either adding them in the options panel or by adding them via a custom filter. Should you want to add them via a filter you can use the `sortable_post_types` for posts and the `sortable_taxonomies` filter for taxonomies.

Documentation can be found here.  
[https://github.com/CarlosRios/sortable-posts-wp](https://github.com/CarlosRios/sortable-posts-wp)  

== Installation ==
Sortable Posts requires the REST API that is currently being merged into 4.4.

1. Navigate to 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `sortable-posts-wp.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Extract the `sortable-posts-wp` directory to your computer
2. Upload the `sortable-posts-wp` directory to the `/wp-content/plugins/` directory
3. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==

= How do I work it? =
Navigate to the Sortable Post Types screen under the WordPress Settings tab and choose the post types you want to make sortable.

= Does it work with built in WordPress post types? =
No, currently it does not support the built in post types.

= Does Sortable Posts work with taxonomies as well? =
Yes! Sortable Posts requires at least WordPress 4.4 to make use of the new term meta fields that were added to it.

== Screenshots ==

1. before Sortable Posts
2. after Sortable Posts
3. Dashboard options

== Changelog ==

= 0.1.2 =
 - add sortable taxonomies with the help of the WordPress 4.4 term metadata improvements.

= 0.1.1 =
 - updated plugin to use REST API to power its ajax.
 - visual modifications to the post edit screen.
 - added an alert message when the sort update succeeds or fails.

= 0.1.0 =
 - initial commit for plugin version