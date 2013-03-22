/*global window,jQuery,wp */
var MediaModal = function (options) {
    'use strict';
    this.settings = {
        calling_selector: false,
        cb: function (attachment) {}
    };
    var that = this,
        frame = wp.media.frames.file_frame;

    this.attachEvents = function attachEvents() {
        jQuery(this.settings.calling_selector).live('click', this.openFrame);
    };

    this.openFrame = function openFrame(e) {
		e.preventDefault();
        
        if (!frame) {
			// Create the media frame.
			frame = wp.media.frames.file_frame = wp.media({
				title: jQuery(this).data('uploader_title'),
				button: {
					text: jQuery(this).data('uploader_button_text')
				},
				multiple: false
			});
			
			// When an image is selected, run a callback.
			frame.on('select', function () {
				// We set multiple to false so only get one image from the uploader
				var attachment = frame.state().get('selection').first().toJSON();
				that.settings.cb(attachment);
			});
			
			frame.on('open activate', function() {
				// Get the link/button/etc that called us
				var $caller = jQuery(that.settings.calling_selector);
				
				// Select the thumbnail if we have one
				if ($caller.data('thumbnail_id')) {
					var Attachment = wp.media.model.Attachment;
					var selection = frame.state().get('selection');
					selection.add(Attachment.get($caller.data('thumbnail_id')));
				}
			});
        }
		
        frame.open();
    };

    this.init = function init() {
        this.settings = jQuery.extend(this.settings, options);
        this.attachEvents();
    };
    this.init();

    return this;

};