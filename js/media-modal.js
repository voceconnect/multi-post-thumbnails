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
    jQuery(this.settings.calling_selector).on('click', this.openFrame);
  };

  this.openFrame = function openFrame(e) {
    e.preventDefault();

    // Create the media frame.
    frame = wp.media.frames.file_frame = wp.media({
      title: jQuery(this).data('uploader_title'),
      button: {
        text: jQuery(this).data('uploader_button_text')
      },
      library : {
        type : 'image'
      },
      multiple: false
    });

    // Set filterable state to uploaded to get select to show (setting this
    // when creating the frame doesn't work)
    frame.on('toolbar:create:select', function(){
      frame.state().set('filterable', 'uploaded');
    });

    frame.on( 'open', function() {
      // Get the link/button/etc that called us
      var $caller = jQuery( that.settings.calling_selector );

      // Select the thumbnail if we have one
      if ( $caller.data( 'thumbnail_id' ) ) {
        var Attachment  = wp.media.model.Attachment.get( $caller.data( 'thumbnail_id' ) );
        Attachment.fetch();
        var selection = frame.state().get( 'selection' );
        selection.add( Attachment );

        // Overload the library's comparator to push items that are not in
        // the mirrored query to the front of the aggregate collection.
        var library = frame.state().get( 'library' );
        var comparator = library.comparator;
        library.comparator = function( a, b ) {
          var aInQuery = !! this.mirroring.get( a.cid ),
          bInQuery = !! this.mirroring.get( b.cid );

          if ( ! aInQuery && bInQuery ) {
            return -1;
          }  else if ( aInQuery && ! bInQuery ) {
          return 1;
          }  else {
          return comparator.apply( this, arguments );
          }
        };
        library.observe( selection );
      }
    });

    frame.open();
  };

  this.init = function init() {
    this.settings = jQuery.extend(this.settings, options);
    this.attachEvents();
  };
  this.init();

  return this;
};