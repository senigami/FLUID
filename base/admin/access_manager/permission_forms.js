var formObj = {                                                               //form object for modal
  init: function(type,arrContent,typeID=0){

    formObj.createForm(type,arrContent,typeID);
    
    $("#myModal").draggable({
        handle: ".modal-header"
    });
    $("#myModal").modal("show");

    $(document).on("click",".cancel_changes",function(){
      $("#myModal").modal("hide");
    });
    $(document).on("click",".delete_prompt",function(){                       //dialog
      $( "#dialog-confirm" ).dialog({
        resizable: false,
        height: "auto",
        width: 400,
        modal: false,
        overlay: {
          backgroundColor: "#000000",
          opacity: 0.9,
          zIndex: 9999999
        },
        buttons: {}
        ,open: function() {
          $(this).dialog({dialogClass:'menu_dialog'}); //override default dialog class
          var tmp   = $(".delete_prompt").attr("id").split("_");
          var type  = tmp[0];
          var idVal = tmp[1];
          $(".delete").attr("id","delete_"+type);

          $(".cancel").on("click",function(){
            $( "#dialog-confirm" ).dialog("close");
          });
       }
     });
     $('.ui-widget-overlay').css('background', 'black');
     $('.ui-widget-overlay').css('z-index', '9999');
    });
  }
  ,createForm: function(type,arrContent,typeID=0){
    $("#modal_title").html("Manage " + utilities.ucFirst(type)+"s");
    $(".modal-body").addClass("modal-form");
    var $form = $("<form />",{"onsubmit":"return false","id":"modal_form"});
    $form.attr("class","form-horizontal");
    $bodyDiv = $("<div />",{"class":"modal-form-body"});
    $.each(arrContent,function(label,content){
      if(content[2] == "body") $bodyDiv.append(formObj.createFormGroup(label,content[0],content[1],content[2]));
      else $form.append(formObj.createFormGroup(label,content[0],content[1],content[2]));
    });
    $form.append($bodyDiv);
    $(".modal-body").html($form);
    $("#div_btn_grp").html(formObj.createButtonGroup(type,typeID));
  }
  ,createFormGroup: function(labelText,content,required=false,type="body"){   //create form group for each label, input, required, subtitle
    var $div  = $("<div />",{"class":"form-group"}).css("padding-right","15px");
    var label = $('<label class="col-sm-3 control-label">'+labelText+ formObj.required(required) + '</label>');
    var value = $('<div class="col-sm-9"></div>');
    value.append(content);
    $div.append(label)
        .append(value);
    return $div;
  }
  ,required: function(isRequired){                                            //returns required marker (*)
    return isRequired == true ? "<span class='required'>*</span>" : "";
  }
  ,createButtonGroup: function(type,typeID){                                  //create modal button groups
    if(typeID == 0)
      return $('<div align="center"><button class="btn btn-primary save_'+type+'"><span class="glyphicon glyphicon-plus"></span> Add '+utilities.ucFirst(type)+'</button> '+
               '<button class="btn btn-default cancel" data-dismiss="modal"><span class="glyphicon glyphicon-remove cancel"></span> Cancel</button></div>');
    else
      return $('<div align="center"><button class="btn btn-success save_'+type+'"><span class="glyphicon glyphicon-pencil"></span> Save '+utilities.ucFirst(type)+'</button> '+
               '<button id="'+type+'_'+typeID+'"  class="btn btn-danger delete_prompt"><span class="glyphicon glyphicon-trash"></span> Delete '+utilities.ucFirst(type)+'</button>'+
               '<button class="btn btn-default cancel" data-dismiss="modal"><span class="glyphicon glyphicon-remove cancel"></span> Cancel</button></div>');
  }
  ,getParams: function(){                                                     //get form input params for updating permissionsJSON and creating paramObj
    var params = {};
    $(".form-control").each(function(){
      params[$(this).attr("name")] = $(this).val();
    });
    return params;
  }
}

var help = {
  init: function(){
    $(".help").on("click",function(){
      if($("#div_help").css("display") == "none"){
        $("#div_help").slideDown();
        $("#icon_help_div").attr("class","opened");
      }
      else{
        $("#div_help").slideUp();
        $("#icon_help_div").attr("class","");
      }
    });
    $(".close_help").on("click",function(){
      $("#div_help").slideUp();
      $("#icon_help_div").attr("class","");
    });
  }
}
