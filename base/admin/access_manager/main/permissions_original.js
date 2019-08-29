$(document).ready(function(){
  permissions.init();                                                         //create permissions table
});

//permissions table object
var permissions = {
  numRoles: 0
  ,arrDelete: new Array()
  ,paramObj: {}
  ,init: function(){
    permissions.loadTable();

    //button collapse/expand
    $(".btn-collapse").on("click",function(){
      if($(this).text() == " Collapse All") permissions.updateCollapse($(this),"glyphicon-chevron-up","Expand","none");
      else permissions.updateCollapse($(this),"glyphicon-chevron-down","Collapse","table-row");
    });

    //reset to original permissions (excepting role/sections)
    $(".reset").on("click",function(){
      $(".checkbox").attr("class","btn btn-default checkbox");
      $(".checkbox i").attr("class","glyphicon glyphicon-none");

      $.each(permissionsJSON['map_id'],function(buttonID,mapID){
        $("#span_"+buttonID).attr("class","btn btn-success checkbox");
        $("#icon_"+buttonID).attr("class","glyphicon glyphicon-ok");
      });
    });

    //checkbox button functionality
    $(document).on("click",".checkbox",function(){
      permissions.updateCheckbox($(this));
    });

    $(document).on("click",".save_content",function(){                          //for permissions
      //alert("Permissions have been saved.");
      permissions.updateCheckboxColors();
      permissions.arrDelete = [];
      permissions.paramObj = {};
      var params = permissions.getParams();

      var paramObj = {
          'action'	    : 'save_'+this.id.replace("btn_",""),
          'permissions' : params.get(),
          'delete'      : permissions.arrDelete,
          'obj'         : permissions.paramObj
      }

      $.post('/admin/permissions/update.php',
        paramObj
        ,function(j){
          //console.log(j);
          resultJSON = JSON.parse(j);
          $.each(resultJSON,function(id,vals){
            var sectionID    = vals['sectionID'];
            var pTypeID      = vals['pTypeID'];
            var permissionID = vals['permissionID'];
            var roleID       = vals['roleID'];
            permissionsJSON['map_id'][sectionID+"_"+permissionID+"_"+roleID] = vals['pTypeID'];
          });
          return false;
        }
      );
    });

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


    /*
    //manage role button
    $(".edit_role").on("click",function(){
      roles.prepareModal();
    });

    //manage section button- calls modal content
    $(document).on("click",".edit_section",function(){
      sections.prepareModal();
    });

    //null check for form inputs on blur
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


    $(document).on("click",".delete_pType",function(){
      alert("The permission type has been deleted");
      $("#myModal").modal("hide");

      var pTypeID = $(this).attr("id").split("_")[1];
      var linkID = "link_"+pTypeID;
      $("#"+linkID).popover("hide");

      var section =   $("#"+linkID).data('section');
      delete permissionsJSON['permissions'][section]['permission_types']['permission_'+pTypeID];
      permissions.loadTable();

      var paramObj = {"action":"delete_pType","pTypeID":pTypeID};
      $.post('/admin/permissions/update.php',
        paramObj
        ,function(j){
        }
      );
    });
*/
  }
  ,loadTable: function(){
    permissions.numRoles = Object.keys(permissionsJSON['roles']).length;
    permissions.createTableContent();
    $(".header").on("click",function(e){
      //alert(e.target.tagName);
      if(e.target.tagName.toLowerCase() == "td"){
        //$(this).closest("tr").toggleClass('expand').nextUntil('tr.section_header').slideToggle(100);
        $(this).toggleClass("expand").nextUntil("tr.section_header").slideToggle(100);
        var spanIcon = $("#"+$(this).attr("id").replace("row","span"));

        if(spanIcon.attr("class") == "glyphicon glyphicon-chevron-down expand-collapse")
           spanIcon.attr("class","glyphicon glyphicon-chevron-up expand-collapse");
        else{
          spanIcon.attr("class","glyphicon glyphicon-chevron-down expand-collapse");
        }
        //e.stopPropagation();
      }
    });

    $(".this_section").on("click",function(e){
        var sectionID = $(this).attr("id").replace("section_","");
        sections.prepareModal(sectionID);
        e.stopPropagation();
    });

    permissionTypes.init();
  }
  ,createTableHeader: function(){
    $tr = $("<tr class='table_header btn-default inactive hover' />");
    $tr.append("<th />");
    for (var i in permissionsJSON.roles){

      $a  = $("<span />",{"text":permissionsJSON.roles[i]['role'],"id":"role_"+i});
      if(permissionsJSON.roles[i].site == "base") $a.addClass("global");
      //var role = $("<span />",{"title":permissionsJSON.roles[i]['description'],"class":"role_header","id":"role_"+i,"text":permissionsJSON.roles[i]['role']}).css("cursor","pointer");
      $tr.append($('<th />').append($a));
    }
    return $tr;
  }
  ,createTableContent: function(){                                            //create table for permissions
    $output = $("<table />").addClass("table table-striped");
    $output.append(permissions.createTableHeader());

    $.each(permissionsJSON['permissions'],function(section,sectionDetails){
      $output.append(permissions.createSectionHeader(section,sectionDetails));
      $.each(sectionDetails['permission_types'],function(permission,values){
        $output.append(permissions.createCheckboxes(permission,values,section,sectionDetails));
      });
    });

    $("#div_permissions").html($output)
                         .append(  permissions.createButtonGroup('permissions'));
  }
  ,createSectionHeader: function(section,sectionDetails){                     //called by createTableContent, returns section headers
    var colSpan = permissions.numRoles+1;
    //row = $('<tr class="section_header btn-primary"><td colspan="'+colSpan+'"><span class="glyphicon glyphicon-chevron-down header"></span> '+utilities.ucFirst(section)+'<td style="text-align: right"><button class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></button></td></tr>');
    if(sectionDetails.site == "base")
    row = $('<tr class="section_header btn-success header" id="row_'+sectionDetails.id+'"><td colspan="'+colSpan+'"><span id="span_'+sectionDetails.id+'" class="glyphicon glyphicon-chevron-down expand-collapse"></span> '+
          '<span class="global_section header">'+utilities.ucFirst(sectionDetails.name)+'</span>'+
          //' <button class="btn btn-default this_section small_btn" id="'+section+'">'+'<span class="glyphicon glyphicon-pencil"></span></button> '+
          //'<a href="#" class="btn btn-default section_pType small_btn" title="Add Permission Type" id="pType_'+ sectionDetails.id+'">'+'<span class="glyphicon glyphicon-plus"></span></a>'+
          ' </tr>');
    else
    row = $('<tr class="section_header btn-primary header" id="row_'+sectionDetails.id+'"><td colspan="'+colSpan+'"><span id="span_'+sectionDetails.id+'" class="glyphicon glyphicon-chevron-down expand-collapse"></span> '+
          utilities.ucFirst(sectionDetails.name)+
          //' <button class="btn btn-default this_section small_btn" id="'+section+'">'+'<span class="glyphicon glyphicon-pencil"></span></button> '+
          //'<a href="#" class="btn btn-default section_pType small_btn" title="Add Permission Type" id="pType_'+ sectionDetails.id+'">'+'<span class="glyphicon glyphicon-plus"></span></a>'+
          ' </tr>');

    return row;
  }
  ,createCheckboxes: function(permission,pt,section,sectionDetails){          //called by createTableContent, returns checkbox objects wrapped in a span
    $tr = $('<tr />');
    //$a  = $("<a />",{"class":"link_label pType_label","data-placement":"bottom","data-toggle":"popover","data-container":"body","data-placement": "left","type":"button", "data-html":"true","href":"#","text":pt.name,"id":"link_"+pt.id});
    //$a.data("section",section);
    if(pt.description != "") $tr.append($('<th />',{"class":"permission"}).html(pt.name + "<div class='description'>" + pt.description+"</div>"));
    else $tr.append($('<th />',{"class":"permission"}).html(pt.name));
    for (var j in permissionsJSON.roles){
      var ptRoles = pt['roles'];
      var $td = $("<td />");
      if($.inArray(j,ptRoles) != -1)
        $td.append($("<span />",{"id":"span_"+sectionDetails['id']+"_"+pt['id']+"_"+j,"name":"span_"+section+"_"+permission+"_"+j}).addClass("btn btn-success checkbox")
                                .append($("<i />",{"id": "icon_"+sectionDetails['id']+"_"+pt['id']+"_"+j}).addClass("glyphicon glyphicon-ok"))
                                .append($("<input />",{"name": "permissions[]", "type": "checkbox", "value": sectionDetails['id']+"_"+pt['id']+"_"+j, "checked": true, "id": sectionDetails['id']+"_"+pt['id']+"_"+j })));

      else
        $td.append($("<span />",{"id":"span_"+sectionDetails['id']+"_"+pt['id']+"_"+j,"name":"span_"+section+"_"+permission+"_"+j}).addClass("btn btn-default checkbox")
                                .append($("<i />",{"id": "icon_"+sectionDetails['id']+"_"+pt['id']+"_"+j}).addClass("glyphicon glyphicon-none"))
                                .append($("<input />",{"name": "permissions[]","type": "checkbox", "value": sectionDetails['id']+"_"+pt['id']+"_"+j, "id": sectionDetails['id']+"_"+pt['id']+"_"+j})));

      $tr.append($td);
    }
    return $tr;
  }
  ,updateCheckboxColors: function(){
    $.each($(".btn-warning"),function(){
      var chkbox = $(this).attr("id").replace("span_","");
      if($("#"+chkbox).prop("checked") == true) $(this).switchClass("btn-warning","btn-success");
      else $(this).switchClass("btn-warning","btn-default");
    })
  }
  ,createButtonGroup: function(type){                                         //button group for permissions
    return $('<div align="center" style="padding: 10px;"><button id="btn_'+type+'" class="btn btn-success save_content"><span class="glyphicon glyphicon-ok"></span> Save</button> '+
             '<button type="reset" class="btn btn-default reset"><span class="glyphicon glyphicon-refresh reset"></span> Revert</button></div>');
  }
  ,updateCollapse: function(button,classVal,textVal,rowDisplay){              //called when collapse all/expand all button clicked (see permissions.init)
    button.text(" " + textVal + " All");
    button.prepend($("<span />").attr("class","glyphicon " + classVal));
    $.each($("tr"),function(){
      if($(this).hasClass("section_header") == false && $(this).hasClass("table_header") == false)
        $(this).css("display",rowDisplay);
    });
    $("span.expand-collapse").attr("class","glyphicon " + classVal + " expand-collapse");
  }
  ,updateCheckbox: function($btn){
    var $chkbox = $("#"+$btn.attr("id").replace("span_",""));
    var $icon   = $("#"+$btn.attr("id").replace("span","icon"));
    var idVal = $chkbox.attr("id").replace("span_","");



    if($chkbox.prop("checked") == true){
      $chkbox.prop("checked",false);
      if(typeof permissionsJSON['map_id'][idVal] != "undefined"){
        $btn.attr("class","btn btn-warning checkbox");
        $icon.attr("class","glyphicon glyphicon-none");
      }
      else{
        $btn.attr("class","btn btn-default checkbox");
        $icon.attr("class","glyphicon glyphicon-none");
      }

    }
    else{
      $chkbox.prop("checked",true);
        if(typeof permissionsJSON['map_id'][idVal] != "undefined"){
        $btn.attr("class","btn btn-success checkbox");
        $icon.attr("class","glyphicon glyphicon-ok");
      }
      else{
        $btn.attr("class","btn btn-warning checkbox");
        $icon.attr("class","glyphicon glyphicon-ok");

      }

    }
  }                                   //checkbox functionality, called when checkbox clicked                                           //update checkbox functionality
  ,getParams: function(){
    var params = $("form").find('input').map(function(i, el) {
      var tmp = el.id.split("_");
      var sectionID = tmp[0];
      var permissionID = tmp[1];
      var roleID = tmp[2];


      if(el.checked == false  && permissionsJSON['map_id'][el.value] != null){
        permissions.arrDelete.push(permissionsJSON['map_id'][el.value]);
        var arrRoles = permissionsJSON['permissions']["section_"+sectionID]['permission_types']["permission_"+permissionID]['roles'];
        arrRoles.splice( $.inArray(roleID, arrRoles), 1 );
        delete permissionsJSON['map_id'][el.value];
      }
      else if(el.checked == true && permissionsJSON['map_id'][el.value] == null) {
        permissions.paramObj[el.value] = {"sectionID":sectionID,"permissionID":permissionID};
        permissionsJSON['permissions']["section_"+sectionID]['permission_types']["permission_"+permissionID]['roles'].push(roleID);
        return el.value;
      }
    });
    //console.log(params);
    return params;
  }
}

/*
//form object for modal
var formObj = {
  init: function(type,arrContent,typeID=0){
    formObj.createForm(type,arrContent);                                    //update modal with form content
    $("#div_btn_grp").html(formObj.createButtonGroup(type,typeID));         //update button group for modal
    $('#myModal').modal('show');                                            //display modal

    $(".save").on("click",function(){                                       //modal save function
      if(formObj.validate() == true){
        var params   = formObj.getParams();
        var paramObj = formObj.createParamObj(this.id,params);

        //check in case updated
        typeID = $(this).attr("id").split("_")[1];

        $.post('/admin/permissions/update.php',
          paramObj
          ,function(j){
            if(type == "role"){
              if(typeID == 0) roles.updateRoleJSON(j,params);
              else roles.updateRoleJSON($("#sel_roles").val(),params);
            }
            else sections.updateSectionJSON(j);
            permissions.loadTable();
            return false;
          }
        );
        $('#myModal').modal('hide');
      }
    });

    $(".delete_prompt").on("click",function(){
      $( "#dialog-confirm" ).dialog({
        resizable: false,
        height: "auto",
        width: 400,
        modal: true,
        overlay: {
          backgroundColor: "#000000",
          opacity: 0.5,
          zIndex: 9998
        },
        buttons: {//added to main page
        }
        ,open: function() {
          $(this).dialog({dialogClass:'menu_dialog'}); //override default dialog class
          $(".delete").attr("id",type+"_"+$("#sel_"+type+"s").val());

          $(".cancel").on("click",function(){
            $( "#dialog-confirm" ).dialog("close");
          });
       }
      });
    });
  }
  ,createForm: function(type,arrContent){                                       //create form content for modal, using arrContent (input object,required)
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
  }
  ,getParams: function(){                                                     //get form input params for updating permissionsJSON and creating paramObj
    var params = {};
    $(".form-control").each(function(){
      params[$(this).attr("name")] = $(this).val();
    });
    return params;
  }
  ,createParamObj: function(idVal,params){                                    //create param object to pass in $.post
    var tmp = idVal.split("_");
    var type   = tmp[0];
    var typeID = tmp[1];

    var paramObj = {
        'action' : 'save_'+type,
        'params' : JSON.stringify(params),
        'typeID' : typeID
    }

    if(type == "section") paramObj = formObj.updateSectionParams(paramObj);
    return paramObj;
  }
  ,updateSectionParams: function(paramObj){                                   //add additional parameters for section (existing permission types, new permission types)
    if(typeof $("#sel_sections") != "undefined"){
      var pTypes = {};
      $("input[name='pType[]']").map(function(){pTypes[$(this).attr("id")] = $(this).val();}).get();
      paramObj['permissionTypes'] = JSON.stringify(pTypes);
    }
    var newTypes = {};
    $("input[name='new[]']").map(function(){newTypes[$(this).attr("id")] = $(this).val();}).get();
    paramObj['newTypes'] = JSON.stringify(newTypes);
    return paramObj;
  }
  ,createFormGroup: function(labelText,content,required=false,type="body"){   //create form group for each label, input, required, subtitle
    var $div   = $("<div />",{"class":"form-group"}).css("padding-right","15px");
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
      return $('<div align="center"><button id="'+type+'_'+typeID+'" class="btn btn-success save"><span class="glyphicon glyphicon-pencil"></span> Save</button> '+
               '<button class="btn btn-danger delete_prompt" style="display: none"><span class="glyphicon glyphicon-trash"></span> Delete</button>'+
               '<button class="btn btn-default cancel" data-dismiss="modal"><span class="glyphicon glyphicon-remove cancel"></span> Cancel</button></div>');
    else
      return $('<div align="center"><button id="'+type+'_'+typeID+'" class="btn btn-success save"><span class="glyphicon glyphicon-pencil"></span> Save</button> '+
               '<button class="btn btn-danger delete_prompt"><span class="glyphicon glyphicon-trash"></span> Delete</button>'+
               '<button class="btn btn-default cancel" data-dismiss="modal"><span class="glyphicon glyphicon-remove cancel"></span> Cancel</button></div>');
  }
  ,updateButtons: function(type,typeID){                                      //update modal button based on new vs. edit (display for delete)
    if(typeID == 0)
      $(".delete_prompt").css("display","none");
    else{
      $(".save").attr("id",type+"_"+typeID);
      $(".delete_prompt").css("display","inline-block");
      $(".delete").attr("id",type+"_"+typeID);
    }
  }
  ,validate: function(){                                                      //validate modal form, returns true if valid
      var valid = true;
      var nullChecks = $(".nullCheck");
      for (var i = 0; i < nullChecks.length; i++){
        if($(nullChecks[i]).attr("disabled") == false || typeof $(nullChecks[i]).attr("disabled") == "undefined"){
          if($(nullChecks[i]).hasClass("chosen-select")){
            if($(nullChecks[i]).val() == null || $(nullChecks[i]).val() == ""){
              alert("Please select all required fields");
              $("#"+$(nullChecks[i]).attr("id")+"_chosen").css("border","1px solid red");
              valid = false;
            }
          }
          else if ($(nullChecks[i]).attr("type") == "select"){
            alert("Please select all required fields");
            $(nullChecks[i]).css("border","1px solid red");
            valid = false;
          }
          else if ($(nullChecks[i]).val() == ""){
            alert("Please enter a value for " + utilities.ucFirst($(nullChecks[i]).attr("name")));
            $(nullChecks[i]).css("border","1px solid red");
            valid = false;
          }
        }
      }
      if(typeof $("#role_check").val() != "undefined" && $("#role_check").val() != ""){
        alert("Please enter a valid role");
        valid = false;
      }
      else if (typeof $("#section_check").val() != "undefined" && $("#section_check").val() != ""){
        alert("Please enter a valid section");
        valid = false;
      }
      if (valid == true) return true;
      else return false;
  }
  ,checkIfExists(type,value,section=0,id=0){
    $.get('/admin/permissions/validate.php',
      {"param":type, "value":value, "id":id, "sectionID":section}
      ,function(j){
        $("#"+type+"_check").text(j);
      }
    );
  }
}

//roles object- called by form object
var roles = {
  init: function(roleID=0){
    roles.createEditContent(roleID);
    $("#sel_roles").on("change",function(){
      roles.updateRoleFields($(this).val());
      formObj.updateButtons("role",$(this).val());
    });

    $(".delete").on("click",function(){
      $( "#dialog-confirm" ).dialog( "close" );
      var tmp    = this.id.split("_");
      var type   = tmp[0];
      var typeID = tmp[1];
      roles.removeRole(typeID);

      $.post('/admin/permissions/update.php',
        {
          'action': 'delete_'+type,
          'typeID': typeID
        }
        ,function(j){
          return false;
        }
      );

      $('#myModal').modal('hide');
    });

    //check if role name already exists
    $("#role").on("blur",function(){
      formObj.checkIfExists("role",$(this).val(),0,roleID);
    });
  }
  ,createEditContent: function(roleID=0){                                     //create role arrContent, and pass to formObj.init, add buttons
    var checkDiv = $("<div />",{"id":"role_check","class":"required","text":""});
    var arrContent = {"Site":         [$('<input />',{"name":"site","type":"text","value":permissionsJSON['site'],"readonly":"true"}).addClass("form-control"),false,"header"],
                      "Role":         [$("<select />",{"id": "sel_roles","name":"role_id"}).addClass("form-control")
                                                                                           .append(roles.getRoleSelectOptions("role",permissionsJSON['roles']))
                                       ,false,"header"
                                      ]
                      ,"Role Name":   [$("<div />").append(checkDiv)
                                                  .append($("<input />",{"id":"role", "name": "role","class":"nullCheck form-control"})),true,"body"]
                      ,"Description": [$("<input />",{"id":"description","name":"description","class":"form-control"}),false,"body"]
                      ,"Groups" :   [$(roles.getKeywordSelect('keywords',keywordsJSON)).addClass("nullCheck form-control"),true,"body"]
                     };

    formObj.init('role',arrContent);
    formObj.createButtonGroup("role","");

    //view specific role's information
    if(roleID > 0){
      $("#sel_roles").val(roleID);
      roles.updateRoleFields(roleID);
      formObj.updateButtons("role",roleID);
    }
  }
  ,removeRole: function(typeID){                                              //called when role is deleted.  removes role from permissionsJSON
    $.each(permissionsJSON['permissions'],function(section,sectionInfo){
      $.each(sectionInfo['permission_types'],function(pTypes,vals){
        if($.inArray(typeID, vals['roles']) > -1) {
          vals['roles'].splice( $.inArray(typeID, vals['roles']), 1 );
        }
      });
    });
    delete permissionsJSON['roles'][typeID];
    permissions.loadTable();
  }
  ,getRoleSelectOptions: function(type,arrRoles,arrSelected=[]){              //change variables to be more generic
    var optionStr = arrSelected == [] ? '<option value="" selected>Add a new '+type+'</option>' : '<option value="" selected>Add a new '+type+'</option>';
    for(var roleID in arrRoles){
      if($.inArray(roleID,arrSelected) > -1 && arrSelected != []) optionStr += '<option value="'+roleID+'" selected>'+arrRoles[roleID]['role']+'</option>';
      else optionStr += '<option value="'+roleID+'">'+arrRoles[roleID]['role']+'</option>';
    }
    return optionStr;
  }
  ,updateRoleFields: function(roleID){                                        //update role fields when role select changed
    if(roleID == ""){
      $("#role").val("");
      $("#description").val("");
      $("#sel_keywords").val("");
      $('select').trigger("chosen:updated");
    }
    else{
      $("#role").val(permissionsJSON['roles'][roleID]['role']);
      $("#description").val(permissionsJSON['roles'][roleID]['description']);
      var arrKeywords = permissionsJSON['roles'][roleID]['keywords'].split(",");
      $('#sel_keywords').val(arrKeywords);
      $('select').trigger("chosen:updated");
    }
  }
  ,updateRoleJSON: function(roleID,params){                                   //update roles when adding/editing
    permissionsJSON['roles'][roleID] = {"role":params['role'],"keywords":","+params['keywords']+",","description":params['description']};
  }
  ,getKeywordSelect: function(name,arrFilterVals){
    var sel = '<select name="'+name+'" class="chosen-select form-control '+name+'" multiple tabindex="4"  id="sel_'+name+'">'+roles.getKeywordOptions(arrFilterVals)+'</select>';
    return sel;
  }                           //create keyword multiselect
  ,getKeywordOptions: function(arrFilterVals,arrSelected=[]){
    var optionStr = "";
    for (i = 0; i < arrFilterVals.length; i++){
      if(arrSelected != [] && $.inArray(arrFilterVals[i],arrSelected) > -1)
        optionStr += '<option value="'+arrFilterVals[i]+'" selected>'+arrFilterVals[i]+'</option>';
      else
        optionStr += '<option value="'+arrFilterVals[i]+'">'+arrFilterVals[i]+'</option>';
    }
    return optionStr;
  }                //called by getKeywordSelect, get options for select
  ,prepareModal: function(roleID=0){
    roles.init(roleID);
    $("#myModal").draggable({
        handle: ".modal-header"
    });

    //multiselect
    $(".chosen-select").chosen();
    $("#sel_keywords_chosen").outerWidth('100%');
  }
}

//sections object- called by form object
var sections  = {
  newID: 0
  ,init: function(sectionID=0){                                               //calls createEditContent, events
    sections.createEditContent(sectionID);

    //update permission types and buttons based on section selection (new/existing)
    $("#sel_sections").on("change",function(){
      var selectedVal = $("#sel_sections").val() == "" ? "" : $("option:selected", this).text();
      $("#section").val(selectedVal);
      sections.updatePermissionTypes($("option:selected", this).val());
      formObj.updateButtons('section',$(this).val());
    });

    //delete event when click on "delete" button on modal
    $(".delete").on("click",function(){
      $( "#dialog-confirm" ).dialog("close");
      var tmp    = this.id.split("_");
      var type   = tmp[0];
      var typeID = tmp[1];
      sections.removePermissionTypes(typeID);

      $.post('/admin/permissions/update.php',
        {
          'action': 'delete_'+type,
          'typeID': typeID
        }
        ,function(j){
          return false;
        }
      );

      $('#myModal').modal('hide');
    });

    //check if role name already exists
    $("#section").on("blur",function(){
      formObj.checkIfExists("section",$(this).val(),sectionID,sectionID);
    });
  }
  ,createEditContent: function(sectionID=0){                                  //create section arrContent, pass to formObj.init, add buttons
    //get section's permission types and permissions
    var tmp = Object.keys(permissionsJSON['permissions']);
    var selSections = new Array();
    for (var i = 0; i < tmp.length; i++){
      selSections[permissionsJSON['permissions'][tmp[i]]['id']] = permissionsJSON['permissions'][tmp[i]]['name'];
    }
    var checkDiv = $("<div />",{"id":"section_check","class":"required","text":""});

    //get arrContent, call formObj
    var arrContent  = {"Site":              [$('<input />',{"name":"site","type":"text","value":permissionsJSON['site'],"readonly":"true"}).addClass("form-control"),false,"header"]
                       ,"Section":          [$("<select />",{"id": "sel_sections","name":"section_id"}).addClass("form-control")
                                                                                                       .append(sections.getSelectOptions("section",selSections)),false,"header"]
                       ,"Section Name":     [$("<div />").append(checkDiv)
                                                         .append($('<input />',{"id":"section","name":"section","class":"nullCheck form-control"})),true,"header"]
                       ,"Permission Types": [$("<div />",{"id":"div_pTypes"}),true,"body"]
                      };

    if (sectionID > 0) arrContent['Section'] = [$("<div />").append($("<select />",{"id": "sel_sections","name":"section_id"}).addClass("form-control")
                                                                                                                              .append(sections.getSelectOptions("section",selSections)))
                                                            .append($("<input />",{"type":"hidden","name":"section_id","value":sectionID}))
                                                            ,false,"header"];
    formObj.init('section',arrContent);

    if(sectionID > 0){
      $("#sel_sections").val(sectionID);
      $("#section").val($("option:selected", $("#sel_sections")).text());
      $("#sel_sections").attr("disabled","true");

      sections.updatePermissionTypes(sectionID);
      formObj.updateButtons('section',sectionID);
    }
    else{
      //set default form to new
      sections.updatePermissionTypes("");
      formObj.updateButtons('section',0);
    }

    //marked to show it has been edited from original value
    $(document).on("blur",".pType",function(){
      var idVal = $(this).attr("id").split("_")[1];
      if($(this).data("origVal") != "" && $(this).val() != $(this).data("origVal")) $("#updated_"+idVal).html("Permission Type will be changed.");
      else  $("#updated_"+idVal).html("");
    });
  }
  ,removePermissionTypes: function(typeID){                                   //remove permission types from permissionsJSON
    $.each(permissionsJSON['map_id'],function(key,val){
      tmp = key.split("_");
      if(tmp[0] == typeID) {
        delete permissionsJSON['map_id'][key];
      }
    });
    delete permissionsJSON['permissions']["section_"+typeID];
    permissions.loadTable();
  }
  ,getSelectOptions: function(type,arrRoles,arrSelected=[]){                  //change variables to be more generic
    var optionStr = arrSelected == [] ? '<option value="" selected>Add a new '+type+'</option>' : '<option value="" selected>Add a new '+type+'</option>';
    for(var roleID in arrRoles){
      if($.inArray(roleID,arrSelected) > -1 && arrSelected != []) optionStr += '<option value="'+roleID+'" selected>'+arrRoles[roleID]+'</option>';
      else optionStr += '<option value="'+roleID+'">'+arrRoles[roleID]+'</option>';
    }
    return optionStr;
  }
  ,addPermissionType: function(idVal,pType,type,sectionID){                   //add permission type row
    var $div   = $("<div />",{"id":"div_"+type+"_"+idVal}).addClass("input-group")
                                                          .css("padding-bottom","15px");
    var $edited = $("<div />",{"id":"updated_"+idVal,"class":"updated"});
    var $span = $("<span />").attr("class","input-group-btn");
    var $input = $("<input />",{"type":"text","name":type+"[]","id":type+"_"+idVal,"value":pType,"class":"form-control nullCheck pType","data":{"section":sectionID,"origVal":pType}});
    var $button = $("<button />",{"id":type+"_"+idVal}).addClass("btn btn-danger "+type+"_delete")
                                                       .html($("<span />").addClass("glyphicon glyphicon-trash"));
    $div.append($edited);
    $div.append($input);
    $span.html($button);
    $div.append($span);
    return $div;
  }
  ,updatePermissionTypes: function(sectionID){                                //update permission types form fields and permissionsJSON based on section selection
    $output = $("<div />",{"id":"pType_content"});

    var btn = $('<button />').addClass("btn btn-primary pType_add")
                             .append($("<span />").addClass("glyphicon glyphicon-plus"))
                             .append(" Add Permission Type");

    $("#div_pTypes").html($output)
                    .append(btn)
                    .append($("<input />",{"type":"hidden","name":"delete_pTypes","id":"delete_pTypes"}).addClass("form-control"));

    if(sectionID != ""){
      $.each(permissionsJSON['permissions']["section_"+sectionID].permission_types,function(pType,info){
        $output.append(sections.addPermissionType(info.id,info.name,'pType',sectionID));
      });
    }
    else{
      $output.append(sections.addPermissionType(sections.newID,"","new",sectionID));
      sections.newID++;
    }

    //permission type events
    $(".pType_delete").on("click",function(){
      $("#div_"+$(this).attr("id")).css("display","none");
      var newDeleteVals = $("#delete_pTypes").val() == "" ? $(this).attr("id").replace("pType_","") : $("#delete_pTypes").val() + "|"+$(this).attr("id").replace("pType_","");
      $("#delete_pTypes").val(newDeleteVals);
    });

    $(".pType_add").on("click",function(){
      $output.append(sections.addPermissionType(sections.newID,"","new",sectionID));
      sections.newID++;
    });

    $("#div_pTypes").on("click",".new_delete",function(){
      $("#div_"+$(this).attr("id")).remove();
    });
  }
  ,updateSectionJSON: function(sectionJSON){                                  //update permissionsJSON section properties, called by save button click
    sectionJSON = JSON.parse(sectionJSON);

    //remove duplicate values
    delete(sectionJSON['permission_types']['permission_0']);

    //add roles
    if(typeof permissionsJSON['permissions']["section_"+sectionJSON['id']] != "undefined"){
      $.each(sectionJSON['permission_types'],function(pTypeID,pTypes){
        if(typeof permissionsJSON['permissions']["section_"+sectionJSON['id']]['permission_types'][pTypeID] != "undefined")
          sectionJSON['permission_types'][pTypeID]['roles'] = permissionsJSON['permissions']["section_"+sectionJSON['id']]['permission_types'][pTypeID]['roles'];
        else sectionJSON['permission_types'][pTypeID]['roles'] = [];
      });
    }
    permissionsJSON['permissions']["section_"+sectionJSON['id']] = sectionJSON;
  }
  ,prepareModal: function(sectionID=0){
    sections.init(sectionID);
    $("#myModal").draggable({
        handle: ".modal-header"
    });
  }
}
*/
var permissionTypes = {
  init: function(){
    //popovers for inline editing
    $('a.pType_label').click(function() {
        $(this).attr("title","Edit Permission Type");
        $(this).popover({
            trigger: 'manual',
            placement: 'right',
            html: true,
            id:"popover",
            content: function() {
               var idVal = $(this).attr("id").split("_")[1];
               var message = '<input type="hidden" id="sectionID" value="'+$(this).data("section")+'" />'+
               '<input id="pType_value" type="text" value="'+ $(this).html()+'" class="form-control" />'+
               '<button class="btn btn-success edit_pType pType_button"  id="edit_'+idVal+'"><span class="glyphicon glyphicon-ok"></span></button>'+
               '<button class="btn btn-danger delete_prompt pType_button" id="prompt_'+idVal+'"><span class="glyphicon glyphicon-trash "></span></button>'+
               '<button class="btn btn-default cancel_pType pType_button" id="cancel_'+idVal+'"><span class="glyphicon glyphicon-remove "></span></button>';
                 return message;
            }
        });
        $(this).popover("show");
    });

    $('.section_pType').click(function() {
        $(this).attr("title", "Add Permission Type");

        $(this).popover({
            trigger: 'manual',
            placement: 'left',
            html: true,
            id:"popover",
            content: function() {
               var sectionID = $(this).attr("id").split("_")[1];
               var message = '<input type="hidden" id="sectionID" value="section_'+sectionID+'" />'+
               '<input id="pType_value" type="text" value="" class="form-control" />'+
               '<button class="btn btn-success add_pType pType_button"  id="add_'+sectionID+'"><span class="glyphicon glyphicon-plus"></span></button>'+
               '<button class="btn btn-default cancel_section_pType pType_button" id="cancel_section_'+sectionID+'"><span class="glyphicon glyphicon-remove "></span></button>';
                 return message;
            }
        });
        $(this).popover("show");
    });

    $(document).on("click",".edit_pType",function(){
      var pTypeID = $(this).attr("id").split("_")[1];
      var linkID = "link_"+pTypeID;
      var pTypeVal = $("#pType_value").val();
      $("#"+linkID).html(pTypeVal);
      $("#"+linkID).popover("hide");
      var section =   $("#"+linkID).data('section');
      permissionsJSON['permissions'][section]['permission_types']['permission_'+pTypeID].name = pTypeVal;
      var paramObj = {"action":"edit_pType","pTypeID":pTypeID,"pType":pTypeVal};
      $.post('/admin/permissions/update.php',
        paramObj
        ,function(j){
        }
      );
    });

    $(document).on("click",".cancel_pType",function(){
      var linkID = $(this).attr("id").replace("cancel_","link_");
      $("#"+linkID).popover("hide");
    });

    $(document).on("click",".cancel_section_pType",function(){
      var linkID = $(this).attr("id").replace("cancel_section_","pType_");
      $("#"+linkID).popover("hide");
    })

    $(document).on("click",".add_pType",function(){
      var sectionID = $(this).attr("id").split("_")[1];
      var section   = permissionsJSON['permissions']["section_"+sectionID]['name'];
      var pType     = $("#pType_value").val();
      var paramObj  = {"action":"add_pType","sectionID":sectionID,"pType":pType,"section":section};
      $("#pType_"+sectionID).popover("hide");

      $.post('/admin/permissions/update.php',
        paramObj
        ,function(j){
          if (j != ""){
            newPermission  = "permission"+j;
            permissionsObj = {"id":j,"name":pType,"roles":[]};
            permissionsJSON['permissions']["section_"+sectionID]['permission_types']['permission_'+j] = permissionsObj;
            permissions.loadTable();
          }
        }
      );
    });

    $(document).on("click",".delete_prompt",function(){
      var idVal = $(this).attr("id").split("_")[1];
      $("#modal_title").html("Delete Confirmation");
      $(".modal-body").html("Are you sure you want to delete this permission type?");
      $("#div_btn_grp").html($('<button class="btn btn-danger delete_pType" id="delete_'+idVal+'"><span class="glyphicon glyphicon-trash"></span>Delete</button><button class="btn btn-default"><span class="glyphicon glyphicon-remove"></span> Cancel</button>'));
      $("#myModal").modal("show");
    });

  }
}
