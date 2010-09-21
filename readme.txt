=== Plugin Name ===
Contributors: chrisscott
Tags: thumbnails, image
Requires at least: 2.9.2
Tested up to: 3.0
Stable tag: 0.3

Adds the ability to add multiple post thumbnails to a post type. If you've ever wanted more than one Featured Image on a post, this plugin is for you.

== Installation ==

1. Upload the `multi-post-thumbnails` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Register a new thumbnail for the post type you want it active for. If `post_type` is not set it defaults to `post`.

		$thumb = new MultiPostThumbnails(array(
			'label' => 'Secondary Image',
			'id' => 'secondary-image',
			'post_type' => 'page'
			)
		);
4. Display the thumbnail in your theme:

		<?php if (class_exists('MultiPostThumbnails')
			&& MultiPostThumbnails::has_post_thumbnail('post', 'secondary-image')) :
				MultiPostThumbnails::the_post_thumbnail('post', 'secondary-image'); endif; ?>

== Frequently Asked Questions ==

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

= 0.3 =
* Fixed: when displaying the insert link in the media library, check the post_type so it only shows for the registered type.

= 0.2 =
* Update docs and screenshots. Update tested through to 3.0 release.

= 0.1 =
* Initial release.