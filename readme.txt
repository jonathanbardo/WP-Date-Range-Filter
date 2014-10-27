=== Date Range Filter ===
Contributors:      jonathanbardo, stream
Tags:              date, filter, admin, dashboard
Requires at least: 3.7
Tested up to:      4.0
Stable tag:        trunk
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Easily filter the admin list of post and custom post type with a date range.

== Description ==

**Note: This plugin requires PHP 5.3 or higher to be activated.**

A big shout-out to the [Stream](https://profiles.wordpress.org/stream/) team for developing much of the functionnality of this plugin and letting me reuse it for another purposes. You guys rock!

This plugin was develop to supercharge the current date filter of WordPress admin. It will let you filter posts by a custom date range or by an already defined range.

By default the plugin only filters post creation date. If you would like to filter the post modified date, please use this filter:
`
function my_date_range_filter_query_column( $column ){
	return 'post_modified';
}
add_filter( 'date_range_filter_query_column', 'my_date_range_filter_query_column', 10, 1 );
`

**Languages Supported:**

 * English

**Improvement? Bugs?**

Please fill out an issue [here](https://github.com/jonathanbardo/WP-Date-Range-Filter/issues).

== Changelog ==

= 0.0.1 =
Initial release. Props [Stream](https://profiles.wordpress.org/stream/)
