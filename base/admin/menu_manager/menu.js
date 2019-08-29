var dataTable;
//table edit button
$(document).ready(function(){
  menuEdit.init();

  $("#table_menu").on("click", ".edit", function(e){
     formObj.init(this.id,'edit');
  });
  $(".add").on("click", function(e){
      formObj.init('','add');
  });

  //delete dialog buttons
  $(".delete").on("click",function(){
    menuEdit.delete("delete_"+obj.db_id);
    $( "#dialog-confirm" ).dialog( "close" );
  })
  $(".cancel").on("click",function(){
    $( "#dialog-confirm" ).dialog("close");
  });
  $("#myModal").draggable({
    handle: ".modal-header"
  });

  $(".chosen-select").chosen();
  $(".site_filter").on("change",menuEdit.filterBySite);

  //menuEdit.filterBySite();
  dataTable = $("#table_menu").dataTable({
    paging: false,
    ordering: false
  });
  $("#table_menu_info").css("display","none");
  /*$("#table_menu_wrapper").removeClass("dataTables_wrapper");
  $(".dataTables_filter input").attr("class","form-control");*/
});

var menuEdit = {
  $output: $("<tbody />",{"id":"tbody_menu"})
  ,arrSites: new Array()
  ,init: function(){
    $.each(menuJSON, menuEdit.updateReferences); //add submenu and parent references to each object, pad weight #
    menuEdit.arrSites = menuEdit.getSitesArray();
    var menuList = menuEdit.buildMenuList();
    $.each(menuList, menuEdit.drawMenu);    //render submenus and parent references

    menuEdit.sortParentWeight("menu_unk");
    if(typeof menuJSON['menu_unk'].submenu != "undefined") numOrphans = Object.keys(menuJSON['menu_unk'].submenu).length;
    else numOrphans = 0;

   if (numOrphans > 0){
      var trOrphan = $('<tr class="tr_unknown" id="tr_unk">'+
                      '<td colspan="8" class="bg-info" style="color:#337AB7 "><h4 ><span class="glyphicon glyphicon-triangle-bottom" ></span> Orphaned Menu Items ('+numOrphans+')</h4></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '<td style="display:none"></td>'+
                      '</tr>');
      menuEdit.$output.append(trOrphan);
    }
    $.each(menuJSON['menu_unk'].submenu,menuEdit.drawMenu);
    $("#table_menu").append(menuEdit.$output);

    //document.getElementById("tbody_menu").innerHTML = menuEdit.htmlVal;
  }
  ,getSitesArray: function(){
    arrSites = new Array();
    $.each($(".site_filter").val(),function(id,val){
      arrSites.push(val);
    });
    return arrSites;
  }
  ,updateReferences: function(id,m){
    var level = 1;
    m.url = m.url == "null" ? "" : m.url;
    m.path = menuEdit.createPath(id);
    if(menuJSON[m.parent] != null && m.parent  != "menu_0"){
      var parentID = m.parent;
      while (parentID != "menu_0"){
        if(menuJSON[parentID] == null) break;
        else {
          parentID = menuJSON[parentID].parent;
          level++;
        }
      }
    }
    else if (m.id != "menu_unk" && m.parent != "menu_0"){
      if(typeof menuJSON['menu_unk']['submenu'] == "undefined") menuJSON['menu_unk']['submenu'] = {};
      menuJSON['menu_unk']['submenu'][id] = m;
    }

    menuJSON[id].level = level;

  }
  ,createPath: function(menuID){
    var parentID = menuJSON[menuID].parent;
    var path     = menuJSON[menuID].db_id;

    while(parentID != "menu_0"){
      if(menuJSON[parentID] == null){
        return "unk:"+path;
      }
      else{
        path     = menuJSON[parentID].db_id + ":" + path;
        parentID = menuJSON[parentID].parent;
      }
    }

    path = "0:"+path;
    return path;
  }
  ,siteCheck: function(origSite){
    var site = "";
    if(origSite == currSite){
      if (origSite == "[GLOBAL]") origSite = "Global";
      site = '<h5><span class="label label-primary">'+origSite+'</span></h5>';
    }
    else{
      if (origSite == "[GLOBAL]") origSite = "Global";
      site = '<h5><span class="label label-default">'+origSite+'</span></h5>'
    }

    return site;
  }
  ,getRowCSS: function(site){
    cssVal = site == "[GLOBAL]" ? "Global" : site;
    return cssVal;
  }
  ,drawButton: function(isDisabled,menuID,site){
    var toolTip    = isDisabled == "disabled" ? "This item is not managed by the current site." : "";
    if(isDisabled == "disabled") return '<button class="btn btn-default edit" id="'+menuID+'" '+isDisabled+' title="'+toolTip+'"><span class="glyphicon glyphicon-pencil"></span></button>';
    else return '<button class="btn btn-success edit" id="'+menuID+'" '+isDisabled+' title="'+toolTip+'"><span class="glyphicon glyphicon-pencil"></span></button>';;
  }
  ,drawMenu: function(id,m){
    if($.inArray(m.site_id,menuEdit.arrSites) > -1){
      var isDisabled = currSite != m.site ? "disabled" : "";

      var allowStr = "";
      var denyStr  = "";
      var restrictStr = menuEdit.getRestrictGroup(m.restrict_groups);
      if(m.restriction == "Show") allowStr = restrictStr;
      else denyStr = restrictStr;

      m.html = $('<tr id="tr_'+m.db_id+'" class="'+menuEdit.getRowCSS(siteJSON['site_'+m.site_id].site) + " " + isDisabled +'"><td style="padding: 3px;">'+'<span style="padding-left:'+menuEdit.padMenu(m.level)+'px">'+menuEdit.updateLabel(m.url,m.label_render,m.level,isDisabled)+'</a><span></td><td align="right"   style="padding: 3px;">' +m.weight + '</td><td   style="padding: 3px;">'+
                menuEdit.siteCheck(siteJSON['site_'+m.site_id].site) + '</td><td  style="padding: 3px;">'+m.url+'</td><td  style="padding: 3px;">'+m.target+'</td><td   style="padding: 3px;">' + allowStr +
                '</td><td  style="padding: 3px;">' + denyStr + '</td><td  style="padding: 3px;">'+menuEdit.drawButton(isDisabled,m.id,siteJSON['site_'+m.site_id].site)+'</td></tr>');
      menuEdit.$output.append(m.html);
      if(typeof m.submenu != "undefined"){
        $.each(m.submenu,menuEdit.drawMenu);
      }
    }
  }
  ,getRestrictGroup: function(restrict = ""){
    if(restrict == "") return "";

    var arrRestrict = restrict.split(",");
    var restrictStr = "";
    for (var i = 0; i < arrRestrict.length; i++){
      if(typeof groupJSON['group_'+arrRestrict[i]] != "undefined")
        restrictStr += restrictStr == "" ? groupJSON['group_'+arrRestrict[i]].group : ", "+ groupJSON['group_'+arrRestrict[i]].group;
    }
    return restrictStr;

  }
  ,updateLabel: function(url,label,level,isDisabled){
    switch(url){
      case "":                              //section header
        return '<span style="font-weight: bold; font-size: 14px">'+label+'</span>';
      case "[---]":                          //<hr />
        return '<hr style="border: 0.5px solid black; margin-left: '+menuEdit.padMenu(level)+'px" />';
      case "[DISABLED]":
        return '<a class="disabled">'+label+'</a>';
      case "#":
        return label;
      default:
        return '<a href="'+url+'" target="_blank">'+label+'</a>';
    }
  }
  ,formatKeywords: function(keywords){
    var keywordStr = "";
    if (keywords != null){
      var tmp = keywords.split(",");
      for (var i = 1; i < tmp.length-1; i++){
        if(keywordStr != "") keywordStr += ", ";
        keywordStr += tmp[i];
      }
    }
    return keywordStr;
  }
  ,padMenu: function(level){
    return 20 * (parseInt(level)-1);
  }
  ,sortByWeight: function(arrWeight){
    arrWeight.sort(function(a,b){
      //return parseInt(a.weight) - parseInt(b.weight);
      if(typeof a.weight_label == "undefined") return b.weight_label;
      else if (typeof b.weight_label == "undefined") return a.weight_label;
      else{
        if(parseInt(a.weight) > parseInt(b.weight) && a.weight_label < b.weight_label) return true;
        else if (parseInt(a.weight) < parseInt(b.weight) && a.weight_label > b.weight_label) return false;

        else return a.weight_label > b.weight_label;
      }
    });
    return arrWeight;
  }
  ,buildMenuList: function(){
    menuList = {};
    $.each(menuJSON,function(id,m){
      if (typeof menuJSON[m.parent] != 'undefined' && m.parent != "menu_0"){
        if(typeof menuJSON[m.parent]['submenu'] == 'undefined') menuJSON[m.parent]['submenu'] = {};
        menuJSON[m.parent]['submenu'][m.id] = m;
      }
      else if (m.parent == "menu_0") menuList[m.id] = m;
    });
    return menuList;
  }
  ,rebuildMenuList: function(){
    menuList = {};
    submenu  = {};
    var arrWeight = new Array();

    $.each(menuJSON,function(id,m){
      arrWeight.push(m);
    });
    arrWeight = menuEdit.sortByWeight(arrWeight);


    $.each(arrWeight,function(key,value){

      if(value.parent != "menu_0" && value.parent != "menu_unk") {
        if(typeof submenu[value.parent] == 'undefined') submenu[value.parent] = {};
        submenu[value.parent][value.id] = value;
      }
    });

    $.each(arrWeight,function(key,value){
      if(value.parent == "menu_0"){
        menuList[value.id] = value;
        menuList[value.id].submenu = submenu[value.id];
      }
    });

    return menuList;
  }
  ,sortParentWeight: function(parent,weight){
    var arrWeight = new Array();
    $.each(menuJSON,function(id,m){
       if(m.parent == parent) arrWeight.push(m);
    });

    arrWeight = menuEdit.sortByWeight(arrWeight);

    return arrWeight;
  }
  ,getLastChild: function(lastID){
    var arrWeight = new Array();
    if(typeof menuJSON[lastID].submenu != "undefined"){
      if(Object.keys(menuJSON[lastID].submenu).length > 0){
      $.each(menuJSON[lastID].submenu,function(subID,sm){
        arrWeight.push(sm);
      });
      arrWeight = menuEdit.sortByWeight(arrWeight);
      return arrWeight[arrWeight.length-1].db_id;
      }
    }
    return menuJSON[lastID].db_id;
  }
  ,delete: function(deleteID){
    var rowID = deleteID.replace("delete_","");
    $('#myModal').modal('hide');
    $("#tr_"+rowID).remove();
    menuID   = "menu_"+rowID;
    origPath = menuJSON[menuID].path;
    menuEdit.saveOrphans(menuID);
    delete menuJSON[menuID];

    $.post('update.php',
      {
					'action'    : 'delete',
					'table'     : 'menu',
					'id'	      : rowID,
          'origPath'  : origPath
      }
      ,function(j){}
        //,'json'
    );
  }
  ,saveOrphans: function(menuID){
    origPath  = menuJSON[menuID].path;
    origLevel = menuJSON[menuID].level;
    $.each(menuJSON[menuID].submenu,function(subID,sm){
      menuJSON[sm.id].path  = sm.path.replace(origPath,"0");
      menuJSON[sm.id].level = sm.level-origLevel;
      sm.parent = "menu_0";
      menuEdit.updateRowHTML(sm,sm.site);
    });
  }
  ,edit: function(menuID){
    var obj          = menuJSON[menuID];
    var origPath     = obj.path;
    var formParams   = $("form").serializeArray();
    var arrUpdate    = {};
    var params       = {}
    var weightUpdate = false;
    var parentUpdate = false;
    var oldSite      = obj.site;
    var origRestrict = obj.restrict_groups;
    var origRestrictType = obj.restriction;

    params['id'] = obj.db_id;
    $('#myModal').modal('hide');

    for (var i = 0; i < formParams.length; i++){
      fpName = formParams[i]['name'];
      fpVal  = formParams[i]['value'];

      if (fpVal != obj[fpName]){
        if (fpName == "parent" && "menu_"+fpVal != obj[fpName]){
          //arrUpdate['parent'] = formParams[i]['value'].replace("menu_","");
          arrUpdate['parent'] = fpVal.replace("menu_","");

          if(menuJSON[obj.parent] == null && obj.parent != 'menu_0') {
            delete menuJSON['menu_unk']['submenu'][obj.id];
          }
          parentUpdate = true;
        }
        else arrUpdate[fpName] = formParams[i]['value'];
        if(fpName == "weight") weightUpdate = true;
      }

      if (fpName == "parent") fpVal = fpVal.replace("menu_","");
      params[fpName] = fpVal;
    }

    restrict = menuEdit.checkKeywords("restrict",",///,");
    arrUpdate['restrict_groups'] = restrict
    arrUpdate['restriction']     = $("#sel_type").val();

    if(parentUpdate == true){
      weight = weightUpdate == true ? arrUpdate['weight'] : obj.weight;
      obj.weight = weight;
      menuEdit.updateWeightLabel(obj.id);
      menuEdit.updateParent(obj.db_id,obj.parent,'menu_'+arrUpdate['parent'],weight);
    }
    else if(weightUpdate == true){ //ignored if parentUpdate, since this handles it already
      menuEdit.updateWeight(obj.db_id,obj.parent,arrUpdate['weight']);
    }

    menuEdit.updateJSON(menuID,arrUpdate);
    if(typeof arrUpdate['parent'] != "undefined") menuJSON[menuID].parent = "menu_"+arrUpdate['parent'];
    arrUpdate['restriction']     = $("#sel_type").val();

    obj.level = menuEdit.updateLevel(obj.path);
    menuEdit.updateRowHTML(obj,oldSite);
    menuEdit.highlightRow(obj.db_id);

    $.post('update.php',
      {
          'action'	          : 'edit',
          'arrUpdate'	        : arrUpdate,
          'id'                : obj.db_id,
          'restrict'          : restrict,
          'origRestrict'      : origRestrict,
          'origRestrictType'  : origRestrictType,
          'params'            : params
      }
      ,function(j){}
        //,'json'
    );
  }
  ,add: function(){
    $('#myModal').modal('hide');
    var formParams   = $("form").serializeArray();
    var params       = {}

    for (var i = 0; i < formParams.length; i++){
      fpName = formParams[i]['name'];
      fpVal  = formParams[i]['value'];
      if(fpName == "parent") fpVal = fpVal.replace("menu_","");
      if(fpName != "restrictType")
        params[fpName] = fpVal;
    }

    var restrict = menuEdit.checkKeywords("restrict",",///,");

    $.post('update.php',
      {
          'action'	    : 'add',
          'table' 	    : 'menu',
          'restrict'    : restrict,
          'restrictType': $("#sel_type").val(),
          'params'      : params
      }
      ,function(j){
        menuID           = "menu_"+j;
        menuJSON[menuID] = {};
        obj = menuJSON[menuID];
        var restriction  = $("#sel_type").val();
        params = menuEdit.updateAddParams(params,j,restrict,restriction);

        menuEdit.updateJSON(menuID,params);
        menuJSON[menuID].path = menuEdit.createPath(menuID);
        menuEdit.updateWeightLabel(menuID);
        obj.level  = parseInt(menuEdit.updateLevel(obj.path));
        obj.parent = ""; //to sort properly
        var arrWeight = menuEdit.sortParentWeight(params['parent'],obj.weight);
        menuEdit.createNewRow(obj,arrWeight,params['parent']);

        if(obj.parent != "menu_0"){
          if(typeof menuJSON[obj.parent].submenu == "undefined")   menuJSON[obj.parent].submenu = {};
          menuJSON[obj.parent].submenu[obj.id] = obj;
        }


      });
  }
  ,updateAddParams: function(params,dbID,restrict,restrictType){
    params['restrict_groups'] = restrict;
    params['restriction']     = restrictType;
    params['id']              = "menu_"+dbID;
    params['db_id']           = dbID;
    params['parent']          = "menu_"+params['parent'];
    return params;
  }
  ,createNewRow: function(obj,arrWeight,parent){
    menuEdit.updateRowHTML(obj,"");
    var trVal = $("<tr />",{"id":"tr_"+obj.db_id});
    trVal.attr("class",menuEdit.getRowCSS(siteJSON['site_'+obj.site_id].site));
    trVal.css("display","table-row");
    trVal.html(obj.html);
    $("#tbody_menu").append(trVal);
    menuEdit.moveRow(arrWeight,parent,obj.weight_label,obj.db_id);
    obj.parent = parent; //update object's parent
    menuEdit.highlightRow(obj.db_id);
  }
  ,checkKeywords: function(type,origVal){
     if($("#sel_"+type).val() == null && (origVal == "" || origVal == null)){
       return "";
     }
     else{
       if($("#sel_"+type).val() == null) return "";
       return $("#sel_"+type).val().join();
     }
     return false;
   }
  ,updateRowHTML: function(menuObj,oldSite){
    var allowStr = "";
    var denyStr = "";
    var restrictStr = menuEdit.getRestrictGroup(menuObj.restrict_groups);
    if (menuObj.restriction == "Show") allowStr = restrictStr;
    else denyStr = restrictStr;

    var isDisabled = menuObj.site_id == currSiteID ? "" : "disabled";
    menuObj.html = $('<td style="padding: 3px;">'+'<span style="padding-left:'+menuEdit.padMenu(menuObj.level)+'px">'+menuEdit.updateLabel(menuObj.url,menuObj.label,menuObj.level,isDisabled)+'</a><span></td>'+
                     '<td style="padding: 3px;" align="right">'+menuObj.weight+'</td><td style="padding: 3px;">'+menuEdit.siteCheck(siteJSON['site_'+menuObj.site_id].site)+'</td><td style="padding: 3px;">'+menuObj.url+'</td><td>'+menuObj.target+'</td><td>'+allowStr+'</td><td>'+
                       denyStr + '</td><td style="padding: 3px;">'+menuEdit.drawButton(isDisabled,menuObj.id,menuObj.site)+'</td>');
    $("#tr_"+menuObj.db_id).html(menuObj.html);

    var rowClass = $("#tr_"+menuObj.db_id).attr("class");
    newSite = menuObj.site_id == 1 ? "Global" : siteJSON["site_"+menuObj.site_id].site;

    if(typeof rowClass != "undefined"){
      $("#tr_"+menuObj.db_id).removeClass(rowClass);
      oldSite = oldSite == "[GLOBAL]" ? "Global" : oldSite;
      rowClass = rowClass.replace(oldSite,newSite);
    }
    else rowClass = newSite;
    $("#tr_"+menuObj.db_id).addClass(rowClass);
  }
  ,updateJSON: function(menuID,arrUpdate){
    $.each(arrUpdate,function(idVal,val){
      menuJSON[menuID][idVal] = val;
    });

  }
  ,highlightRow: function(rowID){
    $("tr").css("fontWeight","");
    $("#tr_"+rowID).css("fontWeight","bold");
  }
  ,updateParent: function(rowID,oldParent,newParent,weight){
    var menuID    = "menu_"+rowID;
    var priorRow  = $("#tr_"+rowID);
    var arrWeight = menuEdit.sortParentWeight(newParent,weight);

    menuEdit.moveRow(arrWeight,newParent,menuJSON[menuID].weight_label,rowID);

    //update path
    var newPath = newParent == "menu_0" ? "0:"+rowID : menuJSON[newParent].path + ":" + rowID;
    var origPath = menuJSON[menuID].path;
    menuJSON[menuID].path = newPath;

    menuEdit.updateLevel(newPath);

    if(typeof menuJSON[menuID].submenu != 'undefined'){
      menuEdit.updateSubmenuRows(menuID,priorRow,origPath,newPath);
    }

    menuJSON["menu_"+rowID].parent = newParent;
  }
  ,compareWeight(weight1,weight2,weightLabel1,weightLabel2){
    var w1 = parseInt(weight1);
    var w2 = parseInt(weight2);

    if(w1 == w2){
      return weightLabel1 > weightLabel2;
    }
    else{
      return w1 > w2;
    }

  }
  ,moveRow: function(arrWeight,newParent,weightLabel,rowID){
    var priorRowID  = "";
    var priorWeight = "";
    var lastID      = 0;

    var weight = parseInt(weightLabel.split("_")[0]);

    $.each(arrWeight,function(key,value){

      if(value.parent == newParent && value.id != "menu_"+rowID) {

        /*if(typeof a.weight_label == "undefined") return b.weight_label;
        else if (typeof b.weight_label == "undefined") return a.weight_label;
        else{
          if(parseInt(a.weight) > parseInt(b.weight) && a.weight_label < b.weight_label) return true;
          else if (parseInt(a.weight) < parseInt(b.weight) && a.weight_label > b.weight_label) return false;

          else return a.weight_label > b.weight_label;
        }*/

        if(menuEdit.compareWeight(value.weight,weight,value.weight_label,weightLabel) == true){
          if(priorWeight != ""){
            if (parseInt(value.weight) < priorWeight){
              priorRowID  = "#tr_"+value.db_id;
              priorWeightLabel = value.weight_label;
              priorWeight = parseInt(value.weight);
            }
          }
          else{
            priorRowID  = "#tr_"+value.db_id;
            priorWeightLabel = value.weight_label;
            priorWeight = parseInt(value.weight);
          }
        }
        lastID = value.id;

      }
    });

    if(priorRowID == ""){
      parentID = newParent.replace("menu_","");
      lastRowID = lastID == 0 ? parentID : menuEdit.getLastChild(lastID);
      $("#tr_"+rowID).insertAfter($("#tr_"+lastRowID));
    }
    else{
      $("#tr_"+rowID).insertBefore($(priorRowID));
      priorRow = priorRowID.replace("#tr_","");
    }
  }
  ,updateLevel: function(path){
    return path.split(":").length - 1;
  }
  ,updateSubmenuRows: function(menuID,priorRow,origPath,newPath){
    $.each(menuJSON[menuID].submenu, function(subID,sm){
      $("#tr_"+sm.db_id).insertAfter(priorRow);
      sm.path  = sm.path.replace(origPath,newPath);
      sm.level = menuEdit.updateLevel(sm.path);
      $("#tr_"+sm.db_id + " td:nth-child(1)").html('<span style="padding-left: '+menuEdit.padMenu(sm.level)+'px"><a href="'+sm.url+'" target="_blank">'+sm.label+"</a></span>");
      //$("#tr_"+sm.db_id + " td:nth-child(6)").html(sm.path + "!");
      priorRow = $("#tr_"+sm.db_id);
      if (typeof menuJSON[sm.id].submenu != "undefined")
        menuEdit.updateSubmenuRows(sm.id,priorRow,origPath,newPath);
    });
  }
  ,updateWeight: function(rowID,parent,weight){

    var menuID   = "menu_"+rowID;
    var path     = menuJSON[menuID].path;
    menuJSON[menuID].weight = weight;

    menuEdit.updateWeightLabel(menuID);
    var arrWeight = menuEdit.sortParentWeight(parent,weight);
    menuEdit.moveRow(arrWeight,parent,menuJSON[menuID].weight_label,rowID);

    if(typeof menuJSON[menuID].submenu != "undefined"){
      priorRow = $("#tr_"+rowID);
      menuEdit.updateSubmenuRows(menuID,priorRow,path,path);
    }
  }
  ,updateWeightLabel: function(menuID){
    var m = menuJSON[menuID];
    var neg        = menuJSON[menuID].weight.indexOf('-') == 0 ? "-" : "";
    var weightVal  = menuJSON[menuID].weight.indexOf('-') == 0 ?  m.weight * -1 : m.weight * 1;
    menuJSON[menuID].weight_label =  neg + sprintf('%09d',weightVal) + " " + m.label;
  }
  ,filterBySite: function(){
    menuEdit.arrSites = menuEdit.getSitesArray();
    menuList = menuEdit.rebuildMenuList();

    $("#tbody_menu").html("");
    $.each(menuList, menuEdit.drawMenu);    //render submenus and parent references
  }
}

var formObj = {
  init: function(menuID,action){
    formObj.updateModal(action);
    formObj.addContent(menuID,action);

    if(action == "edit"){

    }

    $('#myModal').modal('show');

    $(".nullCheck").on("blur",function(){
      if($(this).val() != "") $(this).css("border","");
      else $(this).css("border","1px solid red");
    });
  }
  ,addContent: function(menuID,action){
    var formTable = $("<table />",{"class":"table table-modal"});

    formTable.append(formObj.getRowsHTML(menuID));
    formObj.createButtonGroup(action);
    $("#div_form").append(formTable);

    $(".chosen-select").chosen();
    $("#sel_restrict_chosen").outerWidth('100%');
    $(".form-control").on("keypress",function(e){
      if (e.keyCode == 13){
        if(action == "edit"){
            if(formObj.validate() == true) menuEdit.edit(menuID);
        }
        else{
            if(formObj.validate() == true) menuEdit.add();
        }
      }
    });

    $("#sel_type").on("change",function(){
      if($(this).val() != "" && $("#div_restrictSelect").html() == ""){
        if(menuID == "")   $("#div_restrictSelect").html(formObj.createKeywordSelect("restrict"));
        else $("#div_restrictSelect").html(formObj.createKeywordSelect("restrict",menuJSON[menuID].restrict_groups));
        $(".chosen-select").chosen();
      }
      else if ($(this).val() == ""){
          $("#div_restrictSelect").html("");
      }
    });

    if(action == "edit"){
      formObj.updateEditEvents(menuID);
    }
    else{
      $(".add_menu").on("click", function(e){
        if(formObj.validate() == true) menuEdit.add();
      });
    }
  }
  ,getRowsHTML: function(menuID){
    if(menuID != ""){
      var obj            = menuJSON[menuID];
      var siteID         = obj.site_id;
      var label          = obj.label;
      var restrictGroups = menuJSON['restrict_groups'];
      var weight         = obj.weight;
      var url            = obj.url;
      var target         = obj.target;
      var helpText       = obj.helptext;
    }
    else{
      var obj = label = restrictGroups = url = target = helpText = "";
      var weight         = 0;
      var siteID         = currSiteID;

    }
    var rows = $(
           '<tr><th>Parent<span class="required">*</span></th><td><input type="hidden" name="site_id" value="'+siteID+'" /><select id="form_sel_parent" class="form-control" name="parent">'+formObj.createParentSelect(menuID)+'</select>'+
           '<tr><th>Menu Name<span class="required">*</span></th><td><input name="label" type="text" value="'+label+'" class="form-control nullCheck" /></td></tr>'+
           '<tr><th>Menu URL</th><td><input name="url" class="form-control" maxlength="255" value="'+url+'" /></td></tr>'+
           '<tr><th>Target</th><td><select name="target" class="form-control">'+formObj.createTargetSelect(target)+'</select></td></tr>'+
           '<tr><th>Help Text</th><td><input type="text" class="form-control" name="helptext" value="'+helpText+'" /></td></tr>'+
           '<tr><th>Weight</th><td><input type="text" name="weight" value="'+weight+'" class="form-control" onkeypress="return utilities.isNumericInput(event);" /></td></tr>'+
           '<tr><th>Access Restrictions</th><td>'+
                '<select id="sel_type" name="restrictType" class="form-control">'+
                '<option value="">Default Menu Access</option>'+
                '<option value="Hide">Also Hide From</option>'+
                '<option value="Show">Only Show Menu To</option>'+
                '</select><div id="div_restrictSelect"></div></td></tr>'
          );
    return rows;
  }
  ,updateEditEvents: function(menuID){

    var restriction    = menuJSON[menuID].restriction;
    var restrictGroups = menuJSON[menuID].restrict_groups;

    if(restriction != "" && restriction != null){
      $("#sel_type").val(restriction);

      $("#sel_type").trigger("change");
      $(".chosen-select").chosen();
      $("#sel_restrict_chosen").css("width","");
      $("#sel_restrict_chosen").outerWidth('100%');
    }

    //delete prompt
    $(".delete_prompt").on("click",function(){
      index = menuID.replace("menu_","");
      obj = menuJSON[menuID];
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
        buttons: { //added to main page
        /*" Yes": function() {
            menuEdit.delete("delete_"+obj.db_id);
            $( this ).dialog( "close" );


          },
        " Cancel": function() {
            $( this ).dialog( "close" );
          }*/
        }
        ,open: function() {
          $(this).dialog({dialogClass:'menu_dialog'}); //override default dialog class
          $(".delete").attr("id","delete_"+obj.db_id);
       }
     });
   });

    //modal edit button
    $(".edit_menu").on("click", function(e){
      if(formObj.validate() == true) menuEdit.edit(menuID);
    });
  }
  ,createTargetSelect: function(target){
    var targetObj = {"Default": "", "Open in New Tab": "_blank", "Page Refresh": "_top", "Same Frame": "_self", "Parent Frame": "_parent"};
    var optionStr = "";
    $.each(targetObj,function(key,value){
      if (value == target) optionStr += '<option value="'+value+'" selected>'+key+'</option>';
      else optionStr += '<option value="'+value+'">'+key+'</option>';
    });
    return optionStr;
  }
  ,createKeywordSelect: function(name,selKeywords=""){
    var arrSelected = selKeywords == "" ? {} : selKeywords.split(",");
    var sel = '<select class="chosen-select form-control '+name+'" multiple tabindex="4"  id="sel_'+name+'">'+formObj.getKeywordOptions(arrSelected)+'</select>';
    return sel;
  }
  ,getKeywordOptions: function(arrSelected){
    var optionStr = "";
    $.each(groupJSON,function(id,val){
      if ($.inArray(val.id,arrSelected) != -1) optionStr += '<option value="'+val.id+'" selected>'+val.group+'</option>';
      else optionStr += '<option value="'+val.id+'">'+val.group+'</option>';
    });
    return optionStr;
  }
  ,updateModal: function(action){
    $("#div_form").empty();
    $("#div_btn_grp").empty();
    $("#modal_title").text(utilities.ucFirst(action) + " Menu Item");
  }
  ,createParentSelect: function(excludeID=""){
    menuList = menuEdit.rebuildMenuList();
    var arrSites = menuEdit.getSitesArray();

    var optionStr = "";
    if(excludeID == "") optionStr = "<option value='menu_0' selected>Top Level</option>";
    else {
      if(menuJSON[excludeID].path.indexOf("unk") >= 0){
        if(typeof menuJSON[menuJSON[excludeID].parent] == 'undefined')
          optionStr = '<option value="'+menuJSON[excludeID].parent+'" selected>Inaccessible Page</option>';
        else
          optionStr = '<option value="'+menuJSON[excludeID].parent+'" selected>'+menuJSON[menuJSON[excludeID].parent].label_render+' (Orphaned Page)</option>';
      }
      else if (menuJSON[excludeID].parent == "menu_0") optionStr =  "<option value='menu_0' selected>Top Level</option>";
      else optionStr = "<option value='menu_0'>Top Level</option>";
    }
    optionStr = formObj.createSubItemSelect(menuList,optionStr,excludeID,arrSites);
    return optionStr;
  }
  ,getIndentStr: function(level){
    var str = "";
    for(var i = 0; i < level; i++){
      str += "&nbsp;&nbsp;&nbsp;";
    }
    return str;
  }
  ,createSubItemSelect: function(sm,optionStr,excludeID="",arrSites){
    $.each(sm,function(subID,sub){
        if(excludeID == ""){
          if( $.inArray(sub.site_id,arrSites) > -1){
            optionStr += '<option value="'+sub.id+'" class="level'+sub.level+'">'+formObj.getIndentStr(sub.level) + sub.label_render + "</option>";
            if(typeof sub.submenu != 'undefined' && $.inArray(sub.site_id,arrSites) > -1) optionStr = formObj.createSubItemSelect(sub.submenu,optionStr,excludeID,arrSites);
          }
        }
        else if (subID != excludeID && sub.path.indexOf(menuJSON[excludeID].path+":") < 0 && $.inArray(sub.site_id,arrSites) > -1){

            if (sub.id == menuJSON[excludeID].parent) optionStr += '<option value="'+sub.id+'" selected class="level'+sub.level+'">'+ formObj.getIndentStr(sub.level) + sub.label+'</option>';
            else optionStr += '<option value="'+sub.id+'" class="level'+sub.level+'">'+formObj.getIndentStr(sub.level) + sub.label_render + "</option>";

          if(typeof sub.submenu != 'undefined' && $.inArray(sub.site_id,arrSites) > -1) optionStr = formObj.createSubItemSelect(sub.submenu,optionStr,excludeID,arrSites);
        }
    });
    return optionStr;
  }
  ,prependButtonIcon: function(action){
    var icon = $("<span />");
    switch(action){
      case "edit":
        glyphicon = "glyphicon-pencil";
        break;
      case "add":
        glyphicon = "glyphicon-plus";
        break;
      case "cancel":
        glyphicon = "glyphicon-remove";
        break;
      case "delete":
        glyphicon = "glyphicon-trash";
        break;
    }

    icon.attr("class","glyphicon "+glyphicon);
    return icon;
  }
  ,createButtonGroup: function(action){
    actionStr = action == "edit" ? "save" : action;
    var actionBtn = $("<button />", {"text": " " + utilities.ucFirst(actionStr) + " Menu Item"});
    actionBtn.attr("class", "btn btn-default "+action+"_menu btn-success");

    actionBtn.prepend(formObj.prependButtonIcon(action));

    var cancelBtn = $("<button />", {"text": " Cancel", "data-dismiss": "modal"});
    cancelBtn.attr("class", "btn btn-default");
    cancelBtn.prepend(formObj.prependButtonIcon('cancel'));

    if(action == "add"){
      actionBtn.attr("class","btn btn-default "+action+"_menu btn-primary");
      $("#div_btn_grp").append(actionBtn)
                       .append(cancelBtn);
    }
    else{

      var deleteBtn = $("<button />",{"text":" Delete Menu Item","data-toggle":"modal"});
      deleteBtn.attr("class","btn btn-default delete_prompt btn-danger");
      deleteBtn.prepend(formObj.prependButtonIcon("delete"));

      $("#div_btn_grp").append(actionBtn)
                       .append(deleteBtn)
                       .append(cancelBtn);

    }
  }
  ,validate: function(){
      var valid = true;
      var nullChecks = $(".nullCheck");
      for (var i = 0; i < nullChecks.length; i++){
        if ($(nullChecks[i]).val() == ""){
          alert("Please enter a value for " + utilities.ucFirst($(nullChecks[i]).attr("name")));
          $(nullChecks[i]).css("border","1px solid red");
          valid = false;
        }
      }
      if (valid == true) return true;
      else return false;
  }
}
