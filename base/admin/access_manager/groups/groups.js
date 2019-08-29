var keywords= {                                                               //used for keyword form-specific functions
  id: 0
  ,init: function(){
    $(document).on("click",".add_member",function(){
      members.$output.append(members.createRow());
      $("#table_members").append(members.$output);
      return false;
    });

    $(document).on("click",".delete_member",function(){
      if($(this).hasClass("new") ) members.delete($(this).attr("id"),"new");
      else members.delete($(this).attr("id"),"existing");
    });

    $(document).on("blur",".nullCheck",function(){
      if($(this).val() == "") $(this).css("border","1px solid red");
      else $(this).css("border","");
    });

    $(document).on("blur","#keyword",function(){
      var regexp = /^[a-zA-Z0-9_]+$/;
      if ($(this).val().search(regexp) == -1)
          $("#keyword_check").html("Please enter a valid group (Alphanumeric and underscores only)");
      else{
        $.get("group_validate.php?keywordID="+keywords.id+"&keyword="+$(this).val(),function(data){
          $("#keyword_check").html(data);
          if(data != "") $(this).css("border","1px solid red");
          else $(this).css("border","");
          }
        );
        if($(this).val() == "") $(this).css("border","1px solid red");
      }
    });
  }
  ,createContent: function(keywordID=0){                                      //add
    var checkDiv   = $("<div />",{"id":"keyword_check","class":"required","text":""});
    var arrContent = {"Site"    : [$('<input />',{"name":"site","type":"text","value":currSite,"readonly":"true"}).addClass("form-control"),false,"header"]
                      ,"Group"  : [$("<div />").append(checkDiv)
                                               .append($("<input />",{"id":"keyword", "name": "keyword","class":"nullCheck form-control"}))
                                               .append($("<input />",{"id":"keyword_id","type":"hidden", "name":"keyword_id"})),true,"body"]
                      ,"Description": [$("<input />",{"id":"keyword_desc","name":"description","type":"text","class":"form-control"}),false,"body"]
                      ,"Members": [$("<div />",{"id":"div_members"}),false,"body"]
                     };
    formObj.init('group',arrContent,keywordID);

    if (keywordID > 0){
      $("#keyword_id").val(keywordJSON['keyword_'+keywordID].id);
      $("#keyword").val(keywordJSON['keyword_'+keywordID].keyword);
      $("#keyword_desc").val(keywordJSON['keyword_'+keywordID].description);
      members.loadMembers(keywordID);
    }
    else members.loadMembers();
  }
  ,save: function(table=keywordTable){                                                          //save keyword
    if(keywords.validate() == true){
      params =  $("form").serializeArray();
      params.push({"name":"action","value":"add"});
      $.post('/admin/access_manager/groups/update.php',
        params
        ,function(obj){
          $("#myModal").modal("hide");
          keywordJSON['keyword_'+obj.id] = obj;
          table.loadTable();
        }
        ,'json'
      );
    }
  }
  ,validate: function(){
    valid = true;
    var nullChecks = $(".nullCheck");
    for (var i = 0; i < nullChecks.length; i++){
      if ($(nullChecks[i]).val() == ""){
        var title = $(nullChecks[i]).attr("name").replace("[]","");
        if (title != "keyword") title = "Member " + title;
        else if (title == "keyword") title = "group";
        alert("Please enter a value for " + utilities.ucFirst(title));
        $(nullChecks[i]).addClass('error');
        return false;
      }
      else if ($("#keyword_check").html() != ""){
        alert("Please enter a valid group");
        return false;
      }
    }
    return valid;
  }
  ,delete: function(keywordID){
    $.post('/admin/access_manager/groups/update.php',
      {"action":"delete","keyword_id":keywordID,"keyword":keywordJSON['keyword_'+keywordID].keyword}
      ,function(){
        delete keywordJSON['keyword_'+keywordID];
        keywordTable.loadTable();
      }
    );
  }
}

var members = {                                                               //member portion of keyword form
  $output: $("<tbody />")
  ,deleteID: 0
  ,init: function(keywordID){
    //init function
  }
  ,getTypeSelect: function(selType,memberID){
    defaultVal = 1;
    $select = $("<select />",{"name":"type[]","id":"type_"+memberID,"class":"form-control"});
    $.each(typeJSON,function(id,val){
      if(defaultVal == "") defaultVal = val.id;
      $select.append($("<option />",{"value":val.id,"text": val.type}));
    });
    if(selType != "") $select.val(selType);
    else $select.val(defaultVal);
    return $select;
  }
  ,loadMembers: function(keywordID=0){
    members.$output.html("");
    $table = $("<table />",{"id":"table_members","class":"table"});
    members.$output.append($("<tr />").append($("<th />").html("Type"))
                              .append($("<th />").html("Value"))
                              .append($("<th />").html("Action")));

    if(keywordID > 0){
      $.each(keywordJSON["keyword_"+keywordID].members,function(id,member){
        members.createRow(member.type_id,member.id,member.value);
      });
    }
    else{
      members.createRow();
    }

    $table.append(members.$output);
    $("#div_members").append($table)
                     .append($("<button />",{"class":"btn btn-default add_member","text":" Add Member"}).prepend($("<span />",{"class":"glyphicon glyphicon-plus"}))) //add member
                     .append($("<input />",{"type":"hidden","id":"delete_members","name":"delete_members"}));                                                         //delete member
  }
  ,createRow: function(typeID=1,memberID=0,value=""){
    $tdType   = $("<td />").html(members.getTypeSelect(typeID,memberID));
    $tdValue  = $("<td />").html($("<input />",{"name":"value[]","value":value,"class":"form-control"}))
                          .append($("<input />",{"type":"hidden","name":"memberID[]","value":memberID}));
    if(memberID > 0){
      $tdAction = $("<td />").html($("<button />",{"class":"btn btn-danger delete_member","id":"btn_"+memberID}).html($("<span class='glyphicon glyphicon-trash'></span>")));
      $tr = $("<tr />",{"id":"row_"+memberID});
    }
    else {
      $tdAction = $("<td />").html($("<button />",{"class":"btn btn-danger delete_member new","id":"new_"+members.deleteID}).html($("<span class='glyphicon glyphicon-trash'></span>")));
      $tr = $("<tr />",{"id":"row_new_"+members.deleteID});
      members.deleteID++;
    }

    $tr.append($tdType)
       .append($tdValue)
       .append($tdAction);
    members.$output.append($tr);
  }                        //create row of members
  ,delete: function(idVal,type){
    memberID = idVal.split("_")[1];
    if(type == "existing"){
      deleteStr = $("#delete_members").val() == "" ? memberID : $("#delete_members").val() + "|"+memberID;
      $("#delete_members").val(deleteStr);
      $("#row_"+memberID).remove();
    }
    else{
      $("#row_new_"+memberID).remove();
    }
  }
}

var keywordTable={                                                            //keyword table
  init: function(){
    keywordTable.loadTable();
    $("#keywordTable").DataTable();
    keywords.init();
    help.init();

    $("#div_btn_grp .delete_prompt").append(" Group");

    $(document).on("click",".edit_group",function(){
      keywords.id = $(this).attr("id").split("_")[1];
      keywords.createContent(keywords.id);
    });

    $(".add_group").on("click",function(){
      keywords.createContent();
    });

    $(document).on("click",".save_group",function(){
      keywords.save();
    });

    $(document).on("click","#delete_group",function(){
      keywords.delete(keywords.id);
      $( "#dialog-confirm" ).dialog( "close" );
      $("#myModal").modal("hide");
    });
  }
  ,loadTable: function(){
    $output = $("<tbody />",{"id":"tbody_keywords"});
    $output.html("");
    $.each(keywordJSON,function(key,kObj){
      $output.append($('<tr><td>'+kObj.keyword+'</td><td>'+kObj.description+'</td><td>'+keywordTable.getMemberString(kObj.id)+'</td><td><button id="btn_'+kObj.id+'" class="btn btn-success edit_group small_btn"><span class="glyphicon glyphicon-pencil"></span></button></td></tr>'));
    });
    $("#keywordTable").children("tbody").html($output.html());
  }
  ,getMemberString: function(keywordID){
    var memberStr = "";
    $.each(keywordJSON['keyword_'+keywordID].members,function(key,member){
      memberStr += memberStr == "" ? keywordTable.getPeopleLink(member.value) : ", " + keywordTable.getPeopleLink(member.value);
    });
    return memberStr;
  }
  ,getPeopleLink: function(member){
    return '<a href="http://people.qualcomm.com/People?query='+member+'" target="_blank">'+member+"</a>";
  }
}
