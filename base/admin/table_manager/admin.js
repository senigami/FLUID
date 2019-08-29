var dt;
var cols = [];
var colLength 	 = 0;
var ignoreKeys 	 = ["DT_RowId", "jsonIndex"];
var ignoreFields = ["modified"];
var autoInc 		 = false;
/***********************************************************************/

$(document).ready(function(){
	delete Array.prototype.has;
	tableObj.loadTable($("#sel_table").val());

	$(".add").on("click", function(e){
			formObj.updateModal('add');
			formObj.init('','','add');
			$('#div_delete').css("display","none");
			$('#myModal').modal('show');
	});

	$("#sel_table").on("change",function(){
		tableObj.loadTable($(this).val());
	});
});

var fieldsObj = {
  init: function(fields, jsonCols){
    if(fields.length == 0){
      var i = 0;
      for (var key in jsonCols){
	        cols[i] = jsonCols[key];
	        i++;
      }
      colLength = cols.length;
    }else{
      cols      =  Object.keys(fields[0]);
			if(cols[cols.length-1] == "id") colLength =  Object.keys(fields[0]).length - 2; //ignoring row id and id fields, add one for action column
			else colLength = colLength =  Object.keys(fields[0]).length - 1; 								//ignoring row id and id fields, add one for action column
    }

    tableObj.init();
    if (fields.length == 0) $(".dataTables_empty").attr("colspan",colLength+1);
  }
};

var tableObj = {
  init: function(){
    var tmp = new Array();
    for (var i = 0; i < colLength; i++){
      tmp[i] = {"mDataProp": cols[i], };
    }
		tableObj.createTable(tmp);
  }
  ,loadTable: function(table){
  	$("#div_table").load("get_table.php?table="+table);
  }
  ,createTable: function(tmp){
		dt =	$("#table_fields").DataTable({
				"aaData": fields,
				"aoColumns": tmp,
				"bDestroy": true,
				"order": [[ 0, "desc" ]]
				,"fnRowCallback": function (nRow, aaData, iDisplayIndex){
					for (var i = 0; i < colLength; i++){
							if (i == 0) $('td:eq('+i+')', nRow).html('<button id="btn_'+aaData['jsonIndex']+'" class="btn btn-default edit"><span class="glyphicon glyphicon-pencil"></span></button>');
							else{
								$('td:eq('+i+')', nRow).html('<div class="truncate">'+aaData[cols[i]]+'</div>');
							}
					}
					return nRow;
				}
			 ,"fnDrawCallback": function (oSettings){
				 //edit
				 $(".edit").on("click", function(e){
						formObj.updateModal("edit");
						var row = this.id.replace("btn_","");
						var index= row - 1;
						formObj.init(index,row,'edit');

						$('#div_delete').css("display","block");
						$(".delete_prompt").attr("id","delete_"+index);

						$('#myModal').modal('show');
				 });
					//truncate
					$(function(){
							$.each($('td').not(':empty'), function(i,v){
							var count = parseInt($(this).text().length);
							var maxChars = 210;
							if(count > maxChars){
								var str = $(this).text();
								var trimmed = str.substr(0, maxChars);
								$(v).text(trimmed + '...');
							}
						});
					});
				}
		});
		dt.draw();
  }
};

var formObj = {
  init: function(index, idVal, action){

		var n = 0;
		var colIndex = 0;
    var obj;
    var formTable = $("<table />");

    //get columns
    if (action == 'add'){
			var arrPKs  = formObj.getPrimaryKeys();
			console.log(jsonCols);
			for (var key in jsonCols){
        if (jQuery.inArray(jsonCols[key]['Field'],ignoreKeys) < 0){
					console.log(key + jsonCols[key]);
					var rowIndex = n%colLength;
					formTable.append(formObj.createRow(jsonCols[key]['Field'], rowIndex, '', action));
       }
			 n++;
      }
    }
    else{
			obj = fields[index];
			for (var key in jsonCols){
        if (jQuery.inArray(jsonCols[key]['Field'],ignoreKeys) < 0 ){
         var rowIndex = n%colLength;
				 keyVal = jsonCols[key]['Field'];
         formTable.append(formObj.createRow(jsonCols[key]['Field'], rowIndex, obj[keyVal], action));
       }
			 n++;
      }
    }

    formTable.attr("class","table");
    $("#div_form").append(formTable);
    formObj.createButtonGroup(action);

    $(".delete_prompt").on("click",function(){
			index = this.id.replace("delete_","");
      obj = fields[index];

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
        buttons: {
        "Yes": function() {
            formObj.deleteRecord(idVal,obj.id,cols);
            $( this ).dialog( "close" );
          },
          Cancel: function() {
            $( this ).dialog( "close" );
          }
        }
        ,open: function() {
         $('.ui-dialog-buttonpane').find('button:contains("Yes")').addClass('delete');
         $('.ui-dialog-buttonpane').find('button:contains("Yes")').attr("id",obj.id);
       }
      });
    });

    $(".edit_row").on('click',function(){
      formObj.saveRecord(idVal, obj.id);
      index = idVal - 1;
      //formObj.updateJSON(index,"edit");
    });

    $(".add_row").on('click',function(){
			var valid = true;
			var nullChecks = $(".nullCheck");
			for (var i = 0; i < nullChecks.length; i++){
				if ($(nullChecks[i]).val() == ""){
					alert("Please enter a value for " + utilities.ucFirst($(nullChecks[i]).attr("name")));
					$(nullChecks[i]).css("border","1px solid red");
					valid = false;
				}
			}
			if (valid == true) formObj.addRecord('');
    });

		$(".nullCheck").on("blur",function(){
			if($(this).val() != "") $(this).css("border","");
			else $(this).css("border","1px solid red");
		});
  }
  ,updateModal: function(action){
    $("#div_form").empty();
    $("#div_btn_grp").empty();
    $("#modal_title").text(utilities.ucFirst(action) + " Row");
  }
  ,createButtonGroup: function(action){
    var actionBtn = $("<button />", {"text": utilities.ucFirst(action) + " Row"});
    actionBtn.attr("class", "btn btn-default "+action+"_row");

    var cancelBtn = $("<button />", {"text": "Cancel", "data-dismiss": "modal"});
    cancelBtn.attr("class", "btn btn-default");

    $("#div_btn_grp").append(actionBtn)
                     .append(" ")
                     .append(cancelBtn);
  }
  ,deleteRecord: function(rowID,id,cols){
		var params 		 = $("form").serializeArray();
		var arrPKs		 = formObj.getPrimaryKeys();
		var arrPKVals  = {};

		for (i = 0; i < params.length; i++){
			if(jQuery.inArray(params[i]['name'],arrPKs) >= 0){
				arrPKVals[params[i]['name']] = params[i]['value'];
			}
		}

    $.post('update.php',
      {
        'action': 'delete',
        'id'    : id,
        'table' : $("#sel_table").val(),
				'arrPKVals': arrPKVals
      }
      ,function(j){
          objID = rowID - 1;
          fields.splice(objID,1);

          for (var i = 0; i < fields.length; i++){
            var tmpIndex = i+1;
            fields[i]['jsonIndex'] = tmpIndex;
            fields[i]['DT_RowId']  = "tr_"+tmpIndex + "";
          }

          dt.row(objID).remove();
          dt.clear();
          dt.rows.add(fields);
          dt.draw();

          $('#myModal').modal('hide');
          //alert("The record has been deleted.");
      }
    );
  }
  ,saveRecord: function(rowID, id){
		var origParams = fields[rowID-1];
		var formParams  = $("form").serializeArray();
		var autoInc 		= formObj.isAutoInc();
		var arrPKs		  = formObj.getPrimaryKeys('addForm');
		var arrUpdate   = [];

		var params 		  = {};
		var arrPKVals   = {};

		for (var i = 0; i < formParams.length; i++){
			if (jQuery.inArray(formParams[i]['name'],ignoreFields) < 0 && formParams[i]['name'] != origParams[formParams[i]['name']]){

					params[formParams[i]["name"]] = formParams[i]["value"];
				arrUpdate.push(formParams[i]['name']);
			}
			if(jQuery.inArray(formParams[i]["name"],arrPKs) >= 0){
				arrPKVals[formParams[i]["name"]] = formParams[i]["value"];
			}
		}

    $.post('update.php',
      {
					'action'	  : 'edit',
					'table' 	  :  $("#sel_table").val(),
					'params'	  : params,
					'arrPKVals' : arrPKVals,
					'autoInc'	 	: autoInc,
					'arrUpdate' : arrUpdate
      }
      ,function(j){
        //alert("The record has been saved.");
        $('#myModal').modal('hide');
        var i = 0;
				var colIndex = 0;
				formObj.updateJSON(rowID,j);

        $("#tr_"+rowID +" td").each( function(){
					//function(j){
						if (jQuery.inArray(cols[colIndex],ignoreKeys) < 0){
							var count = parseInt(fields[rowID-1][cols[colIndex]].length);
							var maxChars = 210;
							var str = fields[rowID-1][cols[colIndex]];
							if(count > maxChars){
								var trimmed = str.substr(0, maxChars);
								str = trimmed + '...';
							}

							$(this).html('<div class="truncate">'+str+'</div>');
							i++;
					 }
					colIndex++;
        }
			);
        formObj.highlightRow(rowID);

      }
        //,'json'
    );
  }
  ,highlightRow: function(rowID){
    $("tr").css("fontWeight","");
    $("#tr_"+rowID).css("fontWeight","bold");
  }
  ,addRecord: function(id){
    var formParams  = $("form").serializeArray();
		var autoInc 				= formObj.isAutoInc();
		var arrPKs		  = formObj.getPrimaryKeys('addForm');
		var arrUpdate	  = [];
		var params 		  = {};
		var arrPKVals   = {};

		for (var i = 0; i < formParams.length; i++){
			if (jQuery.inArray(formParams[i]['name'],ignoreFields) < 0){
				if ((autoInc == true && formParams[i]['name'] != "id") || (autoInc == false)){
					params[formParams[i]["name"]] = formParams[i]["value"];
					arrUpdate.push(formParams[i]['name']);
				}
			}
			if(jQuery.inArray(formParams[i]["name"],arrPKs) >= 0){
				arrPKVals[formParams[i]["name"]] = formParams[i]["value"];
			}
		}

    $.post('update.php',
      {
          'action'	  : 'add',
          'table' 	  :  $("#sel_table").val(),
          'params'	  : params,
					'arrPKVals' : arrPKVals,
					'autoInc'	  : autoInc,
					'arrUpdate' : arrUpdate
      }
      ,function(j){
        //alert("The record has been added.");
				console.log(j);
        $('#myModal').modal('hide');
        formObj.addRow(params,j);
        formObj.highlightRow(fields.length+1);
				autoInc = false;
      }
      //,'json'
    );
  }
	,isAutoInc: function(){
		for (i = 0; i < jsonCols.length; i++){
			if (jsonCols[i]['Extra'] == "auto_increment"){
				return true;
				break;
			}
		}
		return false;
	}
	,getPrimaryKeys: function(action=null){
		var arrPKs = [];

		if (action == "addForm"){ //to avoid inserting auto-increment/default fields
			for (i = 0; i < jsonCols.length; i++){
				if (jsonCols[i]['Key'] == "PRI") {
					arrPKs.push(jsonCols[i]['Field']);
				}
			}
		}
		else{
			for (i = 0; i < jsonCols.length; i++){
				if (jsonCols[i]['Key'] == "PRI") {
					arrPKs.push(jsonCols[i]['Field']);
				}
			}
		}
		return arrPKs;
	}
  ,updateJSON: function(rowID,rowVals=null){
			var index = rowID - 1;
			var editObj = JSON.parse(rowVals)[0];

			if(editObj.id == null) editObj.id = rowID + "";
			var idStr   = "";
			var arrPKs  = formObj.getPrimaryKeys('');

		  $.each(editObj, function(key, value){
				if(jQuery.inArray(key,arrPKs) >= 0){
					idStr += idStr == "" ? value : "|" + value;
				}
				fields[index][key] = value;
			});

  }
  ,addRow: function(params,rowVals=null){

    $(".dataTables_empty").attr("colspan","");
    var row_ID = fields.length+1;
    if (row_ID == 1){
        tableObj.loadTable($("#sel_table").val());
    }
    else{
			var addObj = JSON.parse(rowVals)[0];
			if (addObj.id == null) addObj.id = row_ID + "";
			else{
				var idStr   = "";
				var arrPKs  = formObj.getPrimaryKeys('');
			  $.each(addObj, function(key, value){
					if(jQuery.inArray(key,arrPKs) >= 0){
						idStr += idStr == "" ? value : "|" + value;
					}
				});
				addObj.id = idStr + "";
			}
			addObj.jsonIndex = row_ID + "";
			addObj.DT_RowId = "tr_"+row_ID;
		  dt.row.add(addObj).draw(true);

			var index  = fields.length;
			fields[index] = addObj;
		}
  }
  ,hasMultiplePKs: function(){
    var numPrimary = 0;
    for (var i = 0; i < jsonCols.length; i++){
      if (jsonCols[i]['Key'] == "PRI") numPrimary++;
    }
    if (numPrimary > 1) return true;
    else return false;
  }
  ,createRow: function(key, index, value, action){
		if (index < 0) index = 0;
    var trElem = $('<tr />');
    var thElem = $('<th />', {'html': utilities.ucFirst(key) + '<br />('+jsonCols[index]['Type']+")", 'width': 150});
    var tdElem = $('<td />');

		if(action != "add" && jsonCols[index]['Key'] == "PRI")
			tdElem.append(formObj.createReadOnlyField(index, value));
		else if (jsonCols[index]['Extra'] == "auto_increment"){
			if (action == "add")
				value="Automatically Incremented";
				tdElem.append(formObj.createReadOnlyField(index, value));
		}
		else if (jsonCols[index]['Type'] == "timestamp" && jsonCols[index]['Extra'] == "on update CURRENT_TIMESTAMP"){
			value = utilities.createCurrentDateTime();
			tdElem.append(formObj.createReadOnlyField(index, value));
		}
		else if (jsonCols[index]['Type'] == "timestamp"){
			value = utilities.createCurrentDateTime();
			tdElem.append(formObj.createField(index, value));
		}
		else	tdElem.append(formObj.createField(index, value));

    trElem.append(thElem);
    trElem.append(tdElem);
    return trElem;
  }
	,createReadOnlyField: function (index, value){
		if (index < 0) index  = 0;
    var name = jsonCols[index]['Field'];
    return $('<input />', {'name': name, 'id': "text_"+name, 'class': 'form-control', 'value': value, 'readonly': true});
  }
  ,createField: function (index, value){
		if (index < 0) index  = 0;
		var type 		 = formObj.setFieldType(index);
    var name 		 = jsonCols[index]['Field'];
		var maxLimit = formObj.getInputMaxLimit(jsonCols[index]['Type']);
		var nullOK   = jsonCols[index]['Null'] == "YES"  ? "" : "nullCheck";
    return formObj.createElement(type, name, value, maxLimit, nullOK);
  }
  ,setFieldType: function(index){
    var fieldType = jsonCols[index]['Field'] == "modified_by" ? "user" : jsonCols[index]['Type'];			//may need improvement later

    var arrTypes = {};
    var type = "text";

    arrTypes.numTypes  = ["int", "decimal", "float", "double"];
    arrTypes.textTypes = ["char", "text", "blob"];
    arrTypes.selTypes  = ["enum", "set"];
    arrTypes.dateTypes = ["date", "time", "year"];
		arrTypes.userTypes = ["user"];

    $.each(arrTypes, function(key, value){
      $.each(value, function (key2, cell){
        if (fieldType.indexOf(cell) !== -1){
          if (key == "textTypes"){
            type = formObj.textOrMultiline(fieldType);
            return false;
          }
          else{
            type = key;
            return false;
          }
        }
      });
    });
    return type;
  }
  ,textOrMultiline: function(type){
    var re = /[0-9]+/g;
    var num = type.match(re);
    if (num > 0) return num > 25 ? "textarea" : "text";
    else return "textarea";
  }
  ,createElement: function(type, name, value, maxLimit, nullCheck){
    switch (type){
      case "textarea":
        return $('<textarea />', {'name': name, 'id': "textarea_"+name, 'class': 'form-control '+nullCheck, 'text': value, 'maxlimit': maxLimit});
      case "select": //TO DO: for foreign keys?
        return $('<select />', {'name': name, 'id': "sel_"+name, 'class': 'form-control '+nullCheck, 'value': 'TEST'});
			case "numTypes":
				return $('<input />', {'name': name, 'id': "text_"+name,
															 'class': 'form-control number '+nullCheck,
															 'value': value, 'onkeypress':
															 'return utilities.isNumericInput(event);',
															 'maxlimit': maxLimit
														  });
			case "userTypes":
				return $('<input />', {'name': name, 'id': "text_"+name, 'class': 'form-control number '+nullCheck, 'value': adminUser, 'maxlimit': maxLimit});	//adminUser defined in index.php
      default:
        return $('<input />', {'name': name, 'id': "text_"+name, 'class': 'form-control '+nullCheck, 'value': value, 'maxlimit': maxLimit});
    }
  }
	,getInputMaxLimit: function(colType){
		if (colType.indexOf("(") >= 0){
			return colType.replace(/([a-z]+)(\(){1}([0-9]+)(\)){1}/i,"$3");
		}
		return "";
	}

};

var utilities = {
	ucFirst: function(string){
		return string.charAt(0).toUpperCase() + string.slice(1);
	}
	,dedupArray: function(array){
		var a = array.concat();
	    for(var i=0; i<a.length; ++i) {
	        for(var j=i+1; j<a.length; ++j) {
	            if(a[i] === a[j])
	                a.splice(j--, 1);
	        }
	    }
	    return a;
	}
	,createCurrentDateTime: function(){
		var d = new Date,
    dformat = [	d.getFullYear(),
								(d.getMonth()+1).padLeft(),
	              d.getDate().padLeft()
						  ].join('-')+' '+
              [	d.getHours().padLeft(),
	              d.getMinutes().padLeft(),
	              d.getSeconds().padLeft()
							].join(':');
		return dformat;
	}
	,isNumericInput(event){
		return event.charCode >= 48 && event.charCode <= 57 || event.charCode == 0 || event.charCode == 45; //numbers, backspace, and -
	}
};

Number.prototype.padLeft = function(base,chr){
    var  len = (String(base || 10).length - String(this).length)+1;
    return len > 0? new Array(len).join(chr || '0')+this : this;
}
