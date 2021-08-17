/*
 *  Media Locations
 *  Plugin URI: http://mheadley.com
 *
 * handles putting buttons on forms for file uploading.
 * version : 0.5
 */
'use strict';
console.log("simple library js loaded");
(function(){
  var i;

  var uploadArea = document.getElementById("plupload-upload-ui");
  if(!(uploadArea && window.getComputedStyle(uploadArea)["display"] != "none")){
    uploadArea = document.getElementById("html-upload-ui");
  } 
  
  if(!uploadArea){
    //console.log("Bailing loading simple manager but can't find upload area to attach to");
    return false;
  } 
  wpUploaderInit.multipart_params.mediaLocation =  "";

  var UpdateLocationUploader =  function(e){
    //console.log(e.target.id);
    if (e.target.id != "attachment_location") {
      return false;
    }
    //console.log("changing of the media location",e);
    var select_id = e.target;
    var newVal = e.target.options[select_id.selectedIndex].value;
    wpUploaderInit.multipart_params.mediaLocation = newVal;
    document.getElementById("mediaLocation").value = newVal;

  }

  window.addEventListener('load', function(){

    var selectors = document.querySelectorAll("#attachment_location");

    for (i = 0; i < selectors.length; ++i) {
      selectors[i].addEventListener('change', UpdateLocationUploader);
    }

      var hiddenLocationInput = document.createElement('input');
      hiddenLocationInput.type = "hidden";
      hiddenLocationInput.name = "mediaLocation";
      hiddenLocationInput.id = "mediaLocation";
      document.getElementById("html-upload-ui").appendChild(hiddenLocationInput);

  });

})()

