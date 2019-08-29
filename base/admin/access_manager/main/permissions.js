$(document).ready(function(){
  permissions.init();                                                         //create permissions table
});

//permissions table object
var permissions = {
  numRoles: 0
  ,toDelete: {}
  ,toAdd: {}

	,sections: {}		 // keyed by id_##
	,sectionSort: [] // sorted array
	,items: {}			 // keyed by id_##
	,roles: {}			 // keyed by id_##
	,roleSort: []		 // sorted array
	,access: {}			 // keyed by id_##
	
  ,init: function(){
		// create a map of the roles i.e. groups and also create a sorted reference
		$.each(permissionsJSON.roles, function(idx,item){
			item.sortKey = (item.site_id+'_'+item.role).toLowerCase();
			permissions.roles['id_'+item.id] = item;
			permissions.roleSort.push(item);
		});
		permissions.roleSort.sort(permissions.sortKey);
    permissions.numRoles = permissions.roleSort.length;

		// create the sections and items as well as a sorted section reference
		$.each(permissionsJSON.permissions, function(idx,item){
			permissions.items['id_'+item.access_id] = item;
			
			sID = 'id_'+item.section_id;
			if( typeOf(permissions.sections[sID]) == 'undefined' ) {
				
				permissions.sections[sID] = {
						'id': item.section_id,
						'name': item.section,
						'site_id': item.site_id,
						'sortKey': (item.site_id+'_'+item.section).toLowerCase(),
						'items': []
					};
				permissions.sectionSort.push(permissions.sections[sID]);
			}
			// creates a cross reference
			permissions.sections[sID].items.push( permissions.items['id_'+item.access_id] );
		
			// create stub entries for the access mapping
			$.each(permissions.roles, function(rID,role){
				// access id = ROW_COLUMN
				permissions.access[item.access_id + '_' + role.id] = 0;
			});

		});
		permissions.sectionSort.sort(permissions.sortKey);
		
		// set the access mapping for checked values
		$.each(permissionsJSON.accessMap, function(idx,item){
			// access id = ROW_COLUMN
			permissions.access[item.access_id + '_' + item.group_id] = item.group_access_id*1;
		});

    permissions.loadTable();

    //button collapse/expand
    $(".btn-collapse").on("click",function(){
      if($(this).text() == " Collapse All") permissions.updateCollapse($(this),"glyphicon-chevron-up","Expand","none");
      else permissions.updateCollapse($(this),"glyphicon-chevron-down","Collapse","table-row");
    });
    
    //reset to original permissions (excepting role/sections)
    $(".reset").on("click",permissions.resetPermissions);

    //checkbox button functionality
    $(document).on("click",".checkbox",function(){
      permissions.updateCheckbox($(this));
    });

    $(document).on("click",".save_content",function(){                          //for permissions
      //update alert text

			// need, items to add, and items to delete
      var dataObj = {
          'action'	    : 'save_'+this.id.replace("btn_",""),
          'toDelete'      : permissions.toDelete,
          'toAdd'         : Object.keys(permissions.toAdd)
      }
			
      $.post('/admin/access_manager/main/update.php',
        dataObj
        ,function(j){
		      console.log(j);
          resultJSON = JSON.parse(j);
          $.each(resultJSON,function(id,val){
						permissions.access[id] = val; // set item with new db id
          });
		      permissions.updateCheckboxColors();
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
  }
  ,loadTable: function(){
    permissions.createTableContent();
    $(".header").on("click",function(e){
      if(e.target.tagName.toLowerCase() == "td"){
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
  }
  ,createTableHeader: function(){
    $tr = $("<tr class='table_header btn-default inactive hover' />");
    $tr.append("<th />");
		
    $.each( permissions.roleSort,function(i,item){
      $a = $("<span />",{"text":item['role'],"id":"role_"+i,"title":item['description']});
      if(item['site_id'] == "1")
				$a.addClass("global");
      $tr.append($('<th />').append($a));
    });
    return $tr;
  }
  ,createTableContent: function(){                                            //create table for permissions
    $output = $("<table />").addClass("table table-striped");
    $output.append(permissions.createTableHeader());

    $.each(permissions.sectionSort,function(idx,sectionDetails){
      $output.append(permissions.createSectionHeader(sectionDetails));
      $.each(sectionDetails.items.sort(permissions.sortName),function(idx,pItem){
        $output.append(permissions.createCheckboxes(pItem,pItem));
      });
    });

    $("#div_permissions").html($output)
                         .append(  permissions.createButtonGroup('permissions'));
  }
  ,createSectionHeader: function(sectionDetails){                     //called by createTableContent, returns section headers
    var colSpan = permissions.numRoles+1;

    if(sectionDetails.site_id == "1")
    row = $('<tr class="section_header btn-success header" id="row_'+sectionDetails.id+'"><td colspan="'+colSpan+'"><span id="span_'+sectionDetails.id+'" class="glyphicon glyphicon-chevron-down expand-collapse"></span> '+
          '<span class="global_section header">'+utilities.ucFirst(sectionDetails.name)+'</span>'+
          ' </tr>');
    else
    row = $('<tr class="section_header btn-primary header" id="row_'+sectionDetails.id+'"><td colspan="'+colSpan+'"><span id="span_'+sectionDetails.id+'" class="glyphicon glyphicon-chevron-down expand-collapse"></span> '+
          utilities.ucFirst(sectionDetails.name)+
          ' </tr>');

    return row;
  }
  ,createCheckboxes: function(pt,pItem){          //called by createTableContent, returns checkbox objects wrapped in a span
		$tr = $('<tr />');
    if(pt.description != "") $tr.append($('<th />',{"class":"permission"}).html(pt.name + "<div class='description'>" + pt.description+"</div>"));
    else $tr.append($('<th />',{"class":"permission"}).html(pt.name));
    $.each(permissions.roleSort,function(idx,role){
			key = pItem.access_id +"_"+ role.id;
			
      var $td = $("<td />");
      if( permissions.access[key] )
        $td.append($("<span />",{"id":"span_"+key,"name":"span_"+key}).addClass("btn btn-success checkbox")
					.append($("<i />",{"id": "icon_"+key}).addClass("glyphicon glyphicon-ok"))
					.append($("<input />",{"name": "permissions[]", "type": "checkbox", "value": key, "checked": true, "id": "chk_"+key })));
      else
        $td.append($("<span />",{"id":"span_"+key,"name":"span_"+key}).addClass("btn btn-default checkbox")
					.append($("<i />",{"id": "icon_"+key}).addClass("glyphicon glyphicon-none"))
					.append($("<input />",{"name": "permissions[]","type": "checkbox", "value": key, "id": "chk_"+key})));

      $tr.append($td);
    });
    return $tr;
  }
  ,updateCheckboxColors: function(){
		// TODO, change to danger class if it wasn't saved
    $.each($("#div_permissions .btn-warning,#div_permissions .btn-danger"),function(){
      var id = $(this).attr("id").replace("span_","");
			
      if( $("#chk_"+id).prop("checked") )
				if( permissions.access[id] )
					$(this).switchClass("btn-warning btn-danger","btn-success");
				else
					$(this).switchClass("btn-warning","btn-danger");
      else
				if( permissions.access[id] )
					$(this).switchClass("btn-warning","btn-danger");
				else
					$(this).switchClass("btn-warning btn-danger","btn-default");
    })
  }
  ,createButtonGroup: function(type){                                         //button group for permissions
    return $('<div align="center" style="padding: 10px;"><button id="btn_'+type+'" class="btn btn-success save_content"><span class="glyphicon glyphicon-ok"></span> Save</button> '+
             '<button class="btn btn-default reset"><span class="glyphicon glyphicon-refresh reset"></span> Revert</button></div>');
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
    var $chkbox = $("#"+$btn.attr("id").replace("span","chk"));
    var $icon   = $("#"+$btn.attr("id").replace("span","icon"));
    var idVal = $chkbox.attr("id").replace("chk_","");

    if( $chkbox.prop("checked") ) {
			// box was already checked
      $chkbox.prop("checked",false);
      if( permissions.access[idVal] ){
				// change from default set to delete
				permissions.toDelete[idVal] = permissions.access[idVal]; // id of access to delete
        $btn.attr("class","btn btn-warning checkbox");
        $icon.attr("class","glyphicon glyphicon-none");
      }
      else{
				// restore to default don't add it
				delete permissions.toAdd[idVal];
        $btn.attr("class","btn btn-default checkbox");
        $icon.attr("class","glyphicon glyphicon-none");
      }
    }
    else{
			// box was not checked
      $chkbox.prop("checked",true);
      if( permissions.access[idVal] ){
				// restore to default don't delete it
				delete permissions.toDelete[idVal];
        $btn.attr("class","btn btn-success checkbox");
        $icon.attr("class","glyphicon glyphicon-ok");
      }
      else{
				// change from default, set to add
				permissions.toAdd[idVal] = true;
        $btn.attr("class","btn btn-warning checkbox");
        $icon.attr("class","glyphicon glyphicon-ok");
      }
    }
  }                                   //checkbox functionality, called when checkbox clicked                                           //update checkbox functionality
	,resetPermissions: function(){
		$("input[type=checkbox]").prop("checked",false);
		$(".checkbox").attr("class","btn btn-default checkbox");
		$(".checkbox i").attr("class","glyphicon glyphicon-none");
	
		$.each(permissions.access,function(buttonID,mapID){
			if(mapID) {
				$("#chk_"+buttonID).prop("checked",true);
				$("#span_"+buttonID).attr("class","btn btn-success checkbox");
				$("#icon_"+buttonID).attr("class","glyphicon glyphicon-ok");
			}
		});
	}
	,sortKey: function(a,b){
		return (a.sortKey > b.sortKey) - (a.sortKey < b.sortKey);
	}
	,sortName: function(a,b){
		return (a.name > b.name) - (a.name < b.name);
	}
}
