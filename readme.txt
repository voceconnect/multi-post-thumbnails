=== Plugin Name ===
Contributors: chrisscott, voceplatforms
Tags: thumbnails, image, featured image
Requires at least: 2.9.2
Tested up to: 4.1.1
Stable tag: 1.6.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds multiple post thumbnails to a post type. If you've ever wanted more than one Featured Image on a post, this plugin is for you.

== Installation ==

Please refer to full documentation at https://github.com/voceconnect/multi-post-thumbnails/wiki

== Frequently Asked Questions ==

If you have any issues with this plugin, please log them at the Github repo for this plugin.
This is done to centralize our issues and make sure nothing goes unnoticed.

The URL to log an issue is https://github.com/voceconnect/multi-post-thumbnails/issues

See Frequently Asked Questions at https://github.com/voceconnect/multi-post-thumbnails/wiki/Frequently-Asked-Questions

== Screenshots ==

1. Admin meta box showing a new thumbnail named 'Secondary Image'.
2. Media modal showing images attached to the post and a 'Secondary Image' selected.
3. Admin meta box with the 'Secondary Image' selected.

== Changelog ==

After version 1.3, releases were tracked in github: https://github.com/voceconnect/multi-post-thumbnails/releases

Historical releases are below:

= 1.6.6 = 

* Fixed escaping of iframe url

= 1.3 =

* Don't show set as links in media screens when not in context (props prettyboymp). Add voceplatforms as an author. Updated FAQ.

= 1.2 =

* Only enqueue admin scripts on needed pages (props johnjamesjacoby) and make sure thickbox is loaded (props prettyboymp). Add media-upload script to dependencies for post types that don't already require it (props kevinlangleyjr).

= 1.1 =

* Update FAQ. Clean up `readme`. Don't yell `null`. Don't output link to original if there is no image. 

= 1.0 =

* Use `get_the_ID()` in `get_the_post_thumbnail`. Props helgatheviking.

= 0.9 =
* Increment version only to attempt to get plugin versions back in sync.

= 0.8 =
* Revert init action changes from 0.7. Fixes admin metaboxes not showing when the MultiPostThumbnails class is instantiated in an action instead of `functions.php`

= 0.7 =
* Add actions/filters on init action. Should fix admin metaboxes not showing or showing out of order. props arizzitano.

= 0.6 =
* Update `get_the_post_thumbnail` return filter to use format `{$post_type}_{$thumb_id}_thumbnail_html` which allows filtering by post type and thumbnail id which was the intent. Props gordonbrander.
* Update plugin URL to point to Plugin Directory

= 0.5 =
* Update readme to check for `MultiPostThumbnails` class before calling.

= 0.4 =
* Added: optional argument `$link_to_original` to *_the_post_thumbnails template tags. Thanks to gfors for the suggestion.
* Fixed: PHP warning in media manager due to non-existent object

= 0.3 =
* Fixed: when displaying the insert link in the media library, check the post_type so it only shows for the registered type.

= 0.2 =
* Update docs and screenshots. Update tested through to 3.0 release.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.6 =
`get_the_post_thumbnail` return filter changed to use the format `{$post_type}_{$thumb_id}_thumbnail_html` which allows filtering by post type and thumbnail id which was the intent.
