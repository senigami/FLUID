var upload = {
  prefix: ""
  ,accept: ""
  ,itemLength: 0
  ,fileID: 0
  ,fileList: $("<ol />",{"class":"sortable"})
  ,init: function(prefix,accept){
    upload.prefix = prefix;
    upload.accept = accept;
    upload.fileList.attr("id",upload.prefix+"_sortable");

    $("#"+upload.prefix+"_output").html("No files selected.");

    $(document).on('change', '.'+upload.prefix+'_file', function(e) {
        $(e.target).parent().removeClass('hover');
        $.each(jQuery($(this))[0].files, function(i, file) {
          if(upload.extensionCheck(file.name) == true){
            var $listObj = $("<li />",{"id":upload.prefix+"_item_"+upload.itemLength,"class":"file_item"});
            upload.fileList.append($listObj.html('<div ><div class="row"><div class="col-xs-6"><div class="input-group">'+
            '<span class="input-group-btn">       <span class="btn btn-secondary"><span class="glyphicon glyphicon-option-vertical"></span></span>'+
            '</span> <input type="text" class="form-control" name="uploader_'+upload.prefix+'_title[]" value="'+file.name+'" /></div></div><div class="col-xs-6" style="padding-top: 5px;">'+
              file.name+'<input type="hidden" name="uploader_'+upload.prefix+'_exists[]" value="new_'+upload.fileID+"_"+i+'"></div></div></div>'));

              upload.itemLength++;

          }
          //moved out of loop
          $("#"+upload.prefix+"_output").html(upload.fileList);
          $("#"+upload.prefix+"_output").append($("<div align='center'><button id='"+upload.prefix+"_clear' class='btn btn-default' type='button'><span class='glyphicon glyphicon-remove'></span> Clear Files</button></div>"));
          $( "#"+upload.prefix+"_sortable" ).sortable();

        });


        if(upload.itemLength == 0) $("#"+upload.prefix+"_output").html("No files selected.");
        else upload.addFileInput();
    });


    $(document).on('dragover', '.'+upload.prefix+'_file',function(e) {
      $(e.target).parent().addClass('hover');

    });
    $(document).on('dragleave', '.'+upload.prefix+'_file',function(e) {
        //console.log('dragleave');
        $(e.target).parent().removeClass('hover');
    });

		$(document).on("click","#"+upload.prefix+"_clear",function(){
			upload.itemLength = 0;
			upload.fileID = 0;

			$.each($("."+upload.prefix+"_file"),function(id,val){
				$(this).val("");

				if($(this).attr("id") != upload.prefix+"_file_0")  $(this).remove();
			});

			$("#"+upload.prefix+"_sortable").html("");
			$("#"+upload.prefix+"_output").html("No files selected.");
      $("#"+upload.prefix+"_deleted").val("1");

      //TODO: remove, set button as type=button
			return false;
		});
  }
  ,addFileInput: function(){
    upload.fileID++;
    var zIndex =upload.fileID + 20;

    if(/Edge\/\d./i.test(navigator.userAgent)) {  //ie edge being shady

      var $input = $("<input />",{"type":"file",
                                  "id": upload.prefix+"_file_"+upload.fileID,
                                  "height":"30px",
                                  "class":upload.prefix+"_file",
                                  "name":"uploader_"+upload.prefix+"_file_"+upload.fileID+"[]",
                                  "multiple":"",
                                  "accept":upload.accept})
                    .css({"position":"absolute","z-index": zIndex, "left": "25px", "top": "0px", "height":"35px", "width": "94%","bottom":"1px"});
    }
    else{

      var $input = $("<input />",{"type":"file",
                                  "id": upload.prefix+"_file_"+upload.fileID,
                                  "height":"110px",
                                  "class":upload.prefix+"_file",
                                  "name":"uploader_"+upload.prefix+"_file_"+upload.fileID+"[]",
                                  "multiple":"",
                                  "accept":upload.accept})
                    .css({"position":"absolute","background-color":"purple","z-index": zIndex,"top":"0px","width":"98%","right":"0px", "left": "14px","bottom":"1px"});
    }

    $("#"+upload.prefix+"_file_div").append($input);
  }
  ,extensionCheck: function(value){
     var ext = value.match(/\.([^\.]+)$/)[1];
     var tmp = upload.accept.split(",");
     for(var i = 0; i < tmp.length; i++){
        var allowed = tmp[i].split("/")[1];
        if(ext == allowed) return true;
     }
     return false;
  }
}
