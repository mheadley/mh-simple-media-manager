/*
 *  Media Locations
 *  Plugin URI: http://mheadley.com
 *
 * handles putting dropdown for filtering locations.
 * version : 0.5
 */
'use strict';

(function(){
  var  mh_slug_parse = function(item){
    var itemArray  = item.split("_");
    return itemArray[itemArray.length - 1];
  }
  var mediaLocationInst = {value: ""};
  var MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
		id: 'media-attachment-taxonomy-filter',

		createFilters: function() {
            
			var filters = {};
			// Formats the 'terms' we've included via wp_localize_script()
			_.each( MediaLibraryTaxonomyFilterData.terms || {}, function( value, index ) {
				filters[ index ] = {
					text: value.parent > 0 ? getParentName(value.parent) + mh_slug_parse(value.slug)  : mh_slug_parse(value.slug),
					props: {
						// Change this: key needs to be the WP_Query var for the taxonomy
						attachment_location: value.slug,
					}
				};
            });
            

			filters.all = {
				// Change this: use whatever default label you'd like
				text:  'Any Media Location',
				props: {
					// Change this: key needs to be the WP_Query var for the taxonomy
					attachment_location: ''
				},
				priority: 10
			};
			this.filters = filters;
    }
    
  });

  var SubviewConstructor = wp.Backbone.View.extend({
    template: function(){ 
      var html_string = "<div class='inline-status-and-location'><div class='upload-inline-status-container'><p>Select Media Location</p><select class='attachment-location-select-inline-upload'><option value=''>Default Location</option>";
      _.each(MediaLibraryTaxonomyFilterData.terms,function(val){   
        if(val.parent > 0){
          html_string += "<option value='" + val.term_id + "'>" + getParentName(val.parent) + mh_slug_parse(val.slug) +"</option>" ;
        }else{
          html_string += "<option value='" + val.term_id + "'>" + mh_slug_parse(val.slug) +"</option>" ;
        }
        
      }) 
      return html_string + "</select></div><div class='upload-inline-status'></div></div>";
    },
    ready: function(){
      var i;
      if ( this.parentOptions ) {
        this.views.set( '.upload-inline-status', new wp.media.view.UploaderStatus({
            controller: this.parentController
        }));
       }
      
      if(mediaLocationInst.value && mediaLocationInst.value != ""){
        var selectDropdowns = document.querySelectorAll('.attachment-location-select-inline-upload');
        var val = mediaLocationInst.value;
      
        for (i = 0; i < selectDropdowns.length; ++i) {
          var opts = selectDropdowns[i].options;
          for (var opt, j = 0; opt = opts[j]; j++) {
            if (opt.value == val) {
              selectDropdowns[i].selectedIndex = j;
              break;
            }
          }
        }
      } 
    },
    events : {
      'change .attachment-location-select-inline-upload' : 'UpdateLocationUploader'
    }, 
    UpdateLocationUploader: function(e){
      //console.log("changing of the media location",e);
      var select_id = e.target;
      var newVal = select_id.options[select_id.selectedIndex].value;
      mediaLocationInst.value = newVal;
      //this.mediaLocation.value = newVal;
      //console.log("media location", mediaLocationInst, newVal);
    },
   
  });

  

  var getParentName  = function(id){
    var item;

    _.each(MediaLibraryTaxonomyFilterData.terms,function(val){   
      if(val.term_id === id){
        item = val;
      }
    })

    if(item){
      if(item.parent){
        return getParentName(item.parent) + mh_slug_parse(item.slug) + "/";
      }
      return mh_slug_parse(item.slug) + "/";
    } 
    return false;
  }


  var uploaderUIOrig = wp.media.view.UploaderInline;
  wp.media.view.UploaderInline = wp.media.view.UploaderInline.extend({
    ready: function() {
			// Make sure to load the original init
      uploaderUIOrig.prototype.ready.call( this );
      

      var Subview = new SubviewConstructor();
      Subview.parentController = this.controller;
      Subview.parentOptions = this.options.status;
   

      this.views.set( '.upload-inline-status', Subview );
    
      var oldSelector = document.getElementById("attachment_location");
      if(oldSelector){oldSelector.style.display = "none"}
      //console.log("this is the uploader object should be unique", this);
		}
  })


	var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call( this );
			this.toolbar.set( 'MediaLibraryTaxonomyFilter', new MediaLibraryTaxonomyFilter({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );
		}
	});

  

  wp.Uploader.prototype.init =  function(att){
    
    this.uploader.bind('BeforeUpload', function(uploader,file){
      uploader.settings.multipart_params.mediaLocation =  mediaLocationInst.value;
    })
    
  }


})()


//}