/*global window,jQuery,wp */
var MediaModal = function (options) {
    'use strict';
    this.settings = {
        $formField: false,
        $button: jQuery('.upload_image_click'),
        cb: function (attachment) {}
    };
    var that = this,
        file_frame = wp.media.frames.file_frame;

    this.attachEvents = function attachEvents() {
        this.settings.$button.live('click', this.openFrame);
    };

    this.openFrame = function openFrame(e) {
        e.preventDefault();
        
        // If the media frame already exists, reopen it.
        if (file_frame) {
            file_frame.open();
            return;
        }
		
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: jQuery(this).data('uploader_title'),
            button: {
                text: jQuery(this).data('uploader_button_text')
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
            // We set multiple to false so only get one image from the uploader
            var attachment = file_frame.state().get('selection').first().toJSON();
			if (that.settings.$formField) {
				that.settings.$formField.val(attachment.id);
			}
            that.settings.cb(attachment);
        });

        file_frame.open();

    };

    this.init = function init() {
        this.settings = jQuery.extend(this.settings, options);
        this.attachEvents();
    };
    this.init();

    return this;

};