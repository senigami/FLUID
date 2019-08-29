$(document).ready(function(){
  sectionsTable.init();
});

var sectionsTable = {
  init: function(){
    sectionsTable.loadTable();
    $("#sections_table").DataTable();
    help.init();
    sections.init();

    $(document).on("click",".edit_section",function(){
      var idVal = $(this).attr("id").split("_")[1];
      sections.createContent(idVal);
    });

    $(document).on("click",".add_section",function(){
      sections.createContent();
    });

    $(document).on("blur",".nullCheck",function(){
      if($(this).hasClass("chosen-select")){
        if($(this).val() == null) $("#"+this.id+"_chosen").css("border","1px solid red");
        else $("#"+this.id+"_chosen").css("border","");
      }
      else{
        if($(this).val() != "") $(this).css("border","");
        else $(this).css("border","1px solid red");
      }
    });
  }
  ,loadTable: function(){
    $output = $("<tbody />");
    $.each(sectionsJSON,function(id,info){
     $output.append($("<tr><td>"+info.name+"</td><td>"+sectionsTable.getPermissionTypes(info.permission_types)+'</td><td><button id="btn_'+info.id+'" class="btn btn-success edit_section small_btn"><span class="glyphicon glyphicon-pencil"></span></button></td></tr>'));
    });
    $("#tbody_sections").html($output.html());
  }
  ,getPermissionTypes: function(pTypes){
    var pTypeStr = "<ul>";
    $.each(pTypes,function(id,pType){
      var description = pType.description != "" ? " (<i>" + pType.description + "</i>)" : pType.description;
      pTypeStr += "<li>"+pType.name + description+"</li>";
    });
    pTypeStr += "</ul>"
    return pTypeStr;
  }
}

var sections = {
  newID: 0
  ,id: 0
  ,$output: $("<tbody />")
  ,init: function(sectionID=0){
    sections.id = sectionID;
    $(document).on("blur","#section",function(){
      sections.checkIfExists("section",$(this).val(),sectionID,sectionID);
    });

    $(".delete").on("click",function(){
      $( "#dialog-confirm" ).dialog("close");
      delete sectionsJSON["section_"+sections.id];
      sectionsTable.loadTable();

      $.post('/admin/access_manager/sections/update.php',
        {
          'action': 'delete_section',
          'typeID': sections.id
        }
        ,function(j){
          return false;
        }
      );
      $('#myModal').modal('hide');
    });

    $(document).on("click",".save_section",function(){                                       //modal save function
      if(sections.validate() == true){
        var params   = formObj.getParams();
        var paramObj = sections.createParamObj(sections.id,params);
        $.post('/admin/access_manager/sections/update.php',
          paramObj
          ,function(j){
            sections.updateSectionJSON(j);
            sectionsTable.loadTable();
            return false;
          }
        );
        $('#myModal').modal('hide');
      }
    });
    //check if role name already exists
   $("#section").on("blur",function(){
     sections.checkIfExists("section",$(this).val(),sectionID,sectionID);
   });
  }
  ,createParamObj: function(sectionID,params){                                    //create param object to pass in $.post
    var paramObj = {
        'action' : 'save_section',
        'params' : JSON.stringify(params),
        'typeID' : sectionID
    }

    paramObj = sections.updateSectionParams(paramObj);
    return paramObj;
  }
  ,updateSectionParams: function(paramObj){                                   //add additional parameters for section (existing permission types, new permission types)
    var pTypes = {};
    $("input[name='pType[]']").map(function(){pTypes[$(this).attr("id")] = $(this).val();}).get();
    paramObj['permissionTypes'] = JSON.stringify(pTypes);

    var newTypes = {};
    $("input[name='new[]']").map(function(){newTypes[$(this).attr("id")] = $(this).val();}).get();
    paramObj['newTypes'] = JSON.stringify(newTypes);

    var descriptions = {};
    $("input[name='description[]']").map(function(){descriptions[$(this).attr("id")] = $(this).val();}).get();
    paramObj['descriptions'] = JSON.stringify(descriptions);

    return paramObj;
  }
  ,checkIfExists(type,value,section=0,id=0){
    $.get('/access_manager/main/validate.php',
      {"param":type, "value":value, "id":id, "sectionID":section}
      ,function(j){
        $("#"+type+"_check").text(j);
      }
    );
  }
  ,createContent: function(sectionID=0){
    sections.id = sectionID;
    var checkDiv   = $("<div />",{"id":"section_check","class":"required","text":""});
    var arrContent = {  "Site":            [$('<input />',{"name":"site","type":"text","value":currSite,"readonly":"true"}).addClass("form-control"),false,"header"]
                       ,"Section Name":    [$("<div />").append(checkDiv)
                                                        .append($('<input />',{"id":"section","name":"section","class":"nullCheck form-control"}))
                                                        .append($('<input />',{"id":"sectionID","name":"section_id","type":"hidden","value":sectionID})),true,"header"]
                       ,"Permission Types":[$("<div />",{"id":"div_pTypes"}),false,"body"]
                      };

    formObj.init('section',arrContent,sectionID);
    permTypes.init(sectionID);
    //sections.updatePermissionTypes(sectionID);
    if(sectionID > 0){
      $("#section").val(sectionsJSON['section_'+sectionID].name);
    }

    //marked to show it has been edited from original value
    $(document).on("blur",".pType",function(){
      var idVal = $(this).attr("id").split("_")[1];
      if($(this).data("origVal") != "" && $(this).val() != $(this).data("origVal")) $("#updated_"+idVal).html("Permission Type will be changed.");
      else  $("#updated_"+idVal).html("");
    });
  }
  ,validate: function(){                                                      //validate modal form, returns true if valid
    var alertStr = sections.nullCheck();
    if(typeof $("#role_check").val() != "undefined" && $("#role_check").val() != "")
      alertStr += "-\nPlease enter a valid role";
    else if (typeof $("#section_check").val() != "undefined" && $("#section_check").html() != "")
      alertStr += "\n-Please enter a valid section";
    if (alertStr == "") return true;
    else alert("Please address the following:" + alertStr);

    return false;
  }
  ,nullCheck: function(){
    var nullChecks = $(".nullCheck");
    var alertStr = "";
    for (var i = 0; i < nullChecks.length; i++){
      if($(nullChecks[i]).attr("disabled") == false || typeof $(nullChecks[i]).attr("disabled") == "undefined"){
        if ($(nullChecks[i]).val() == ""){
          if($(nullChecks[i]).attr("name") == "new[]" || $(nullChecks[i]).attr("name") =="pType[]")
            alertStr += "\n-Please enter a value for permission type";
          else
            alertStr += "\n-Please enter a value for " + utilities.ucFirst($(nullChecks[i]).attr("name"));
          $(nullChecks[i]).css("border","1px solid red");
        }
      }
    }
    return alertStr;
  }
  ,updateSectionJSON: function(sectionJSON){                                  //update permissionsJSON section properties, called by save button click
    sectionJSON = JSON.parse(sectionJSON);

    //remove duplicate values
    delete(sectionJSON['permission_types']['permission_0']);

    //add roles
    if(typeof sectionsJSON["section_"+sectionJSON['id']] != "undefined"){
      $.each(sectionJSON['permission_types'],function(pTypeID,pTypes){
        if(typeof sectionsJSON["section_"+sectionJSON['id']]['permission_types'][pTypeID] != "undefined")
          sectionJSON['permission_types'][pTypeID]['roles'] = sectionsJSON["section_"+sectionJSON['id']]['permission_types'][pTypeID]['roles'];
        else sectionJSON['permission_types'][pTypeID]['roles'] = [];
      });
    }
    sectionsJSON["section_"+sectionJSON['id']] = sectionJSON;
  }
};

var permTypes = {
  $output: $("<tbody />")
  ,newID: 0
  ,init: function(sectionID=0){
    var btn = $('<button />').addClass("btn btn-default pType_add")
                             .append($("<span />").addClass("glyphicon glyphicon-plus"))
                             .append(" Add Permission Type");

    $("#div_pTypes").html(permTypes.loadPermTypes(sectionID))
                    .append(btn)
                    .append($("<input />",{"type":"hidden","name":"delete_pTypes","id":"delete_pTypes","class":"form-control"}));

    //permission type events
    $(".pType_delete").on("click",function(){
      var idVal =  $(this).attr("id").replace("delete_pType_","");
      $("#row_"+idVal).remove();
      var newDeleteVals = $("#delete_pTypes").val() == "" ? idVal : $("#delete_pTypes").val() + "|"+idVal;
      $("#delete_pTypes").val(newDeleteVals);
    });

    //$(document).on("click",".pType_add",function(){
    $(".pType_add").on("click",function(){
      permTypes.createRow();
    })

    $("#table_pTypes").on("click",".new_delete",function(){
      var idVal = $(this).attr("id").replace("delete","row");
      $("#"+idVal).remove();
    });
  }
  ,loadPermTypes: function(sectionID=0){
    permTypes.$output.html("");
    $table = $("<table />",{"id":"table_pTypes","class":"table"});
    permTypes.$output.append($("<tr />").append($("<th />").html("Permission Type"))
                     .append($("<th />").html("Description"))
                     .append($("<th />").html("Action")));
    if(sectionID > 0){
      $.each(sectionsJSON["section_"+sectionID].permission_types,function(pType,info){
        permTypes.createRow(info.id,"pType",info.name,info.description);
      });
    }
    else permTypes.createRow();
    $table.append(permTypes.$output);
    return $table;
  }
  ,createRow: function(idVal=permTypes.newID,type="new",pType="",description=""){
    $tdLabel        = $("<td />").html($("<input />",{"type":"text","name":type+"[]","id":type+"_"+idVal,"value":pType,"class":"form-control nullCheck pType","data":{"section":sections.id,"origVal":pType}}));
    $tdDescription  = $("<td />").html($("<input />",{"type":"text","name":"description[]","id":type+"_"+idVal,"class":"form-control","value":description}));
    $tdAction       = $("<td />").html($("<button />",{"class":"btn btn-danger "+type+"_delete","id":"delete_"+type+"_"+idVal}).html($("<span />",{"class":"glyphicon glyphicon-trash"})));

    if(type == "new"){
      $tr = $("<tr />",{"id":"row_new_"+permTypes.newID});
      permTypes.newID++;
    }
    else $tr = $("<tr />",{"id":"row_"+idVal});

    $tr.append($tdLabel).append($tdDescription).append($tdAction);
    permTypes.$output.append($tr);
  }
}
