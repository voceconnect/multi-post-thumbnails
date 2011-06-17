=== Plugin Name ===
Contributors: chrisscott
Tags: thumbnails, image
Requires at least: 2.9.2
Tested up to: 3.1.3
Stable tag: 0.6

Adds multiple post thumbnails to a post type. If you've ever wanted more than one Featured Image on a post, this plugin is for you.

== Installation ==

1. Upload the `multi-post-thumbnails` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Register a new thumbnail for the post type you want it active for. If `post_type` is not set it defaults to `post`.

		if (class_exists('MultiPostThumbnails')) {
			new MultiPostThumbnails(array(
			'label' => 'Secondary Image',
			'id' => 'secondary-image',
			'post_type' => 'post'
			)
		);
}
4. Display the thumbnail in your theme:

		<?php if (class_exists('MultiPostThumbnails')
			&& MultiPostThumbnails::has_post_thumbnail('post', 'secondary-image')) :
				MultiPostThumbnails::the_post_thumbnail('post', 'secondary-image'); endif; ?>

== Frequently Asked Questions ==

= I'm trying to upgrade to a new verions of WordPress and get an error about `MultiPostThumbnails` =

This is caused by using the example in previous readmes that didn't do a check for the `MultiPostThumbnails` class existing first. This has been corrected in the Installation section.

= How do I register the same thumbnail for multiple post types? =

You can loop through an array of the post types:

	if (class_exists('MultiPostThumbnails')) {
		$types = array('post', 'page', 'my_post_type');
		foreach($types as $type) {
			$thumb = new MultiPostThumbnails(array(
				'label' => 'Secondary Image',
				'id' => 'secondary-image',
				'post_type' => $type
				)
			);
		}
	}

= How do I use a custom thumbnail size in my theme? =

After you have registered a new post thumbnail, register a new image size for it. e.g if your post thumbnail `id` is `secondary-image` and it is for a `post`, it probably makes sense to use something like:

	`add_image_size('post-secondary-image-thumbnail', 250, 150);`

This will register a new image size of 250x150 px. Then, when you display the thumbnail in your theme, update the call to `MultiPostThumbnails::the_post_thumbnail()` to pass in the image size:

	`MultiPostThumbnails::the_post_thumbnail('post', 'secondary-image', NULL,  'post-secondary-image-thumbnail');`

You can register multiple image sizes for a given thumbnail if desired.

== Screenshots ==

1. Admin meta box showing a new thumbnail named 'Secondary Image'.
2. Media screen showing the link to use the image as the 'Secondary Image'.
3. Admin meta box with the 'Secondary Image' selected.

== Changelog ==

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