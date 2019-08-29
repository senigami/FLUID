/*
	pageAction
	basicInfo{}
	menuInfo{}
	keywords{}
	menuList{}
*/

$(document).ready(function(){
  basic.init(pageAction);
  menu.init(pageAction);
});

function compareItems(o1,o2){
	switch(typeOf(o1)){
		case 'array':
		case 'object':
			return (JSON.stringify(o1) == JSON.stringify(o2));
		break;
		default:
			Log(typeOf(o1));
		case 'number':
		case 'string':
			// proceed to default return
	}
	return o1 == o2;
}

var basic = {
  action: null
  ,init: function(){
		// set the form values
		$.each(basicInfo,function(n,v){
				$F("#basic_"+n,v);
			});
		basic.initCustomFieldTypes();
		basic.initFormActions();
		basic.initFormValidation();
  }
	,initCustomFieldTypes: function(){
		// activate ckeditor
		var roxyFileman = "/base/lib/fileman/index.html";
		CKEDITOR.replace("basic_content",{filebrowserBrowseURL:roxyFileman,filebrowserImageBrowseUrl:roxyFileman+"?type=image",removeDialogTabs: "link:upload;image:upload"});
    if(pageAction == "edit") basic.presetBasicAccess();
    // activate Multiselect
    $(".chosen-select").chosen();
    $(".chosen-container").outerWidth('60%'); // fix for bootstrap resizing
	}
	,initFormActions: function(){
		//Save text editor content
    $(".save_content").on("click",function(){
      basic.saveContent();
    });

    $("#form_buttons .cancel").on("click",function(){
      window.history.back();
      return false;
    })

    $(".delete_prompt").on("click",function(){
			$( "#dialog-confirm" ).dialog({
				dialogClass: "dialog_confirm",
        resizable: false,
        height: "auto",
        width: 400,
        modal: true,
        overlay: {
          backgroundColor: "#000000",
          opacity: 0.5,
          zIndex: 9998
        },
        buttons: {}
			});
		});
		$('#confirm-delete').click(function(){
			basic.deleteContent();
		})
		$('#confirm-cancel').click(function(){
			$("#dialog-confirm").dialog( "close" );
		})

    $(".form-control").on("keypress",function(e){
        if (e.keyCode == 13){
          if(basic.validate() == true) basic.saveContent();
        }
    });

    $("#basic_restriction").change(function(){
      if($(this).val() != "" && $("#basic_restrict_groups").css("display") == "none"){
        $("#div_restrictions").css("display","block");
      }
      else if($(this).val() == ""){
        $("#basic_restrict_groups").val("");
        $('select').trigger("chosen:updated");
        $("#div_restrictions").css("display","none");
      }
    });
	}
  ,presetBasicAccess(){
    var types = new Array("restrict","edit");
    if($("#basic_restriction").val() != ""){
      $("#div_restrictions").css("display","block");
      $("#orig_restriction").val($("#basic_restriction").val());
    }
    for (var i = 0; i < types.length; i++){
      $("#basic_"+types[i]+"_groups").trigger("chosen:updated");
      if($("#basic_"+types[i]+"_groups").val() != null)
        $("#orig_"+types[i]+"_groups").val($("#basic_"+types[i]+"_groups").val().join(","));
    }
  }
	,initFormValidation: function(){
		//Form Validation
    $(".nullCheck").on("blur",function(){
			isEmpty($(this).val())? $(this).addClass('error') : $(this).removeClass('error');
    });

    $("#basic_alias").on("blur",function(e){
      var siteID  = $("#basic_site_id").val();
      var alias = $(this).val();
      basic.aliasCheck(siteID,alias);
    });
	}
  ,aliasCheck: function(siteID,alias){
    var ignore = isEmpty(alias);
    if( !ignore && !isNaN(alias)){
      $("#alias_check").html("Alias cannot be an integer");
      $("#layout_alias").addClass('error');
    }
    else{
      if(pageAction == "edit"){
        if(basicInfo['site_id'] == siteID && basicInfo['alias'] == alias){
          ignore = true;
          $("#alias_check").html("");
          $("#layout_alias").removeClass('error');
        }
      }
      if(ignore == false){
        $.get("/layouts/basic/validateAlias.php",{"siteID": siteID, "alias": alias},function(data){
          if(data == "true"){
            $("#alias_check").html("Alias already exists for this site.  Please enter another value");
            $("#alias").addClass('error');
          }
          else{
            $("#alias_check").html("");
            $("#alias").removeClass('error');
          }
        });
      }
      else{
        $("#alias_check").html("");
        $("#alias").removeClass('error');
      }
    }
  }
  ,validate: function(){
		if($("#alias_check").html() != ""){
			alert("Please enter a valid URL alias");
			return false;
		}
		if( isEmpty($F('#basic_title')) ){
			alert("Please enter a value for the page title");
			$('#basic_title').addClass('error');
				return false;
		}

    var empty = false;
    if(basic.multiSelectValidate("div_restrictions","basic_restrict_groups") == true) empty = true;
    if(basic.multiSelectValidate("div_menu_restrictions","menu_restrict_groups") == true) empty = true;

    if(empty == true){
      alert("Please enter one or more valid groups");
      return false;
    }

		if( isEmpty($F('#menu_label')) )
			$F('#menu_label',$F('#basic_title'));
		if( isEmpty($F('#menu_weight')) )
			$F('#menu_weight',0);

		return true;
  }
  ,multiSelectValidate: function(div,sel){
    empty = false;
    if($("#"+div).css("display") == "block" && isEmpty($F("#"+sel))){
      empty = true;
      $("#"+sel+"_chosen").css("border","1px solid red");
    }
    else $("#"+sel+"_chosen").css("border","");
    return empty;
  }
  ,getRestrictFields(type){
    var arrVals = ["restriction","restrict_groups"];
    if (type == "basic") arrVals.push("edit_groups");
    var objRestrict = {};
    for (var i = 0; i < arrVals.length; i++){
      objRestrict[arrVals[i]] = $F("#"+type+"_"+arrVals[i]);
    }
    return objRestrict;
  }
  ,removeRestrictFields(type,obj){
    var arrVals = ["restriction","restrict_groups"];
    if (type == "basic") arrVals.push("edit_groups");
    for (var i = 0; i < arrVals.length; i++){
      delete obj[arrVals[i]];
    }
    return obj;
  }
  ,getUpdateItems: function(fields,info){
    var arrUpdate = [];
    $.each(fields,function(key,value){
      if( !compareItems(fields[key],info[key]) )  //updated to false to account for undefined/null items
        arrUpdate.push(key);
    });
    return arrUpdate;
  }
  ,prepareFields(type,info){
    var obj = {};
    // get standard form fills.
    obj['fields'] = $("#form_"+type).serializeArray().reduce(function(m,o){ m[o.name] = o.value; return m;}, {});

    //get restriction/override values and remove irrelevant items from standard form fills.  then populate updates.
    obj['restrict']      = basic.getRestrictFields(type);
    obj['fields']        = basic.removeRestrictFields(type,obj['fields']);


    obj['restrict']['orig_restriction'] = info['restriction'];

    if(info['restriction'] != obj['restrict']['restriction'] || obj['restrict']['restriction'] == "")
      obj['restrict']['delete_groups']  = info['restrict_groups'] == "" ? null : info['restrict_groups'];
    else{
      obj['restrict']['delete_groups'] =  $(info['restrict_groups']).not(obj['restrict']['restrict_groups']).get();
    }

    //if basic, include ckeditor, add site_id
    if(type == "basic"){
      obj["fields"]["content"] = CKEDITOR.instances["basic_content"].getData();
      obj['restrict']['delete_edit'] =$(info['edit_groups']).not(obj['restrict']['edit_groups']).get();
    }
    obj['fields']['site_id'] = $F("#basic_site_id");  //not getting from serializeArray


    obj['updates']       = basic.getUpdateItems(obj['fields'],info);
    return obj;
  }
  ,saveContent: function(){
			// TODO: display SAVING animation
     if(basic.validate() == true){
      var basicObj    = basic.prepareFields("basic",basicInfo);
      var menuObj     = basic.prepareFields("menu",menuInfo);

      var menuDisplay = menuObj['fields']["display"];
			delete menuObj['fields']["display"];

      $.post('/layouts/basic/edit_submit.php',
         {
					'action': pageAction,

					'basic_mod'          : basicObj['updates'],
					'basic_content'      : basicObj['fields'],
          'basic_restrict'     : basicObj['restrict'],
          'basic_restrict_mod' : basicObj['restrict_mod'],

					'menuDisplay'        : menuDisplay,
					'menu_mod'           : menuObj['updates'],
					'menu_content'       : menuObj['fields'],
          'menu_restrict'      : menuObj['restrict'],
          'menu_restrict_mod'  : menuObj['restrict_mod']
         }
         ,function(j){
						if( j.success ){
							//window.location.href = j.url;
							$('#redirect_message').val(j.status);
							$('#form_redirect').attr("action", j.url).submit();
						}
						else {
							alert('There was an error saving');
						}
						return false;
         }
        ,'json'
      );
    }
  }
  ,deleteContent: function(){
			// TODO: display SAVING animation

			// get standard form fills
			basicFields = {
				'id': basicInfo.id,
				'menu_id': basicInfo.menu_id,
				'title': basicInfo.title
			};

			// list the items that are different
       $.post('/layouts/basic/edit_submit.php',
         {
					'action': 'delete',
					'basic_content': basicFields,
         }
         ,function(j){
						if( j.success ){
							//window.location.href = j.url;
							$('#redirect_message').val(j.status);
							$('#form_redirect').attr("action", j.url).submit();
						}
						else {
							alert('There was an error deleting');
						}
						return false;
         }
        ,'json'
       );
  }
};

var menu = {
  init: function(){
		$("#tbody_menu").hide();
		$F('#menu_display',menuInfo.id*1);
		if( menuInfo.id) {

			$("#tbody_menu").show();
			$.each(menuInfo,function(n,v){
				$("#menu_"+n).val(v);
			});
      menu.presetMenuAccess();
		}

    //Toggle menu display disable/enable
    $("#menu_display").on("click",function(){
			this.checked? $("#tbody_menu").slideDown() : $("#tbody_menu").slideUp();
    });

    $("#menu_restriction").change(function(){
      if($(this).val() != "" && $("#menu_restrict_groups").css("display") == "none"){
        $("#div_menu_restrictions").css("display","block");
      }
      else if($(this).val() == ""){
        $("#menu_restrict_groups").val("");
        $('select').trigger("chosen:updated");
        $("#div_menu_restrictions").css("display","none");
      }
    });

		/*
        case "parent":
					if(menuJSON[excludeID].path.indexOf("unk") >= 0){
						if(typeof menuJSON[menuJSON[excludeID].parent] == 'undefined')
							optionStr = '<option value="'+menuJSON[excludeID].parent.replace("menu_","")+'" selected>Inaccessible Page</option>';
						else
							optionStr = '<option value="'+menuJSON[excludeID].parent.replace("menu_","")+'" selected>'+menuJSON[menuJSON[excludeID].parent].label+' (Orphaned Page)</option>';
					}

        case "weight":
          content.on("keypress",function(e){
            return utilities.isNumericInput(e);
          })
    */
  }
  ,presetMenuAccess: function(){
    if($("#menu_restriction").val() != ""){
      $("#div_menu_restrictions").css("display","block");
      $("#menu_orig_restriction").val($("#menu_restriction").val());

      $("#menu_restrict_groups").trigger("chosen:updated");
      if($("#menu_restrict_groups").val() != null)
        $("#menu_orig_restrict_groups").val($("#menu_restrict_groups").val().join(","));
    }
  }
}
