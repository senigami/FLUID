<?php
$block = $fluid->page->vars['block'];
if($fluid->page->vars['action'] == "edit")  $fluid->page->title = "Edit Block: " . $block['title'];
else $fluid->page->title = "Add Block";

$fluid->page->header_bottom .= '<link href="/layouts/basic/basic.css" rel="stylesheet" type="text/css" />';

$fluid->loadHeader();
?>
<div class="container-fluid" role="main">
	<div class="main_content">
    <?php
    $fluid->components['Breadcrumbs']->set();
    $fluid->components['Breadcrumbs']->display();
    ?>
<form id="form_basic" class="form-horizontal" onsubmit="return false;">
  <div class="form-content">
    <input id="id" name="id" type="hidden" value="">
    <input id="site_id" name="site_id" type="hidden" value="<?=$fluid->site->info->id ?>">

    <div class="form-group">
      <label class="col-sm-2 control-label">Title<span class="required">*</span></label>
      <div class="col-sm-10">
        <input id="title" class="form-control nullCheck" name="title" type="text" value="">
      </div>
    </div>

		<div class="form-group">
      <label class="col-sm-2 control-label">Show Title</label>
      <div class="col-sm-10">
				<input id="show_title" type="checkbox" name="show_title" />
      </div>
    </div>

    <div class="form-group">
      <label class="col-sm-2 control-label">Content</label>
      <div class="col-sm-10">
        <textarea class="form-control" cols="80" id="content" name="content" rows="10"></textarea>
      </div>
    </div>
  </div>


  <div id="form_buttons" align="center" style="padding: 10px;">
    <button class="btn btn-success save_content"><span class="glyphicon glyphicon-pencil"></span> Save Block</button>
		<?php if($fluid->page->vars['action'] == "edit"):?>
		<button class="btn btn-danger delete_prompt"><span class="glyphicon glyphicon-trash"></span> Delete Block</button>
		<?php endif; ?>
		<button class="btn btn-default cancel"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
  </div>
</form>
</div>
</div>

<!-- Modal -->
<div id="dialog-confirm" title="Delete Page Confirmation" style="display: none; padding-top: 10px;">
	<p><span class="ui-icon ui-icon-alert" style="color: red; float:left; margin:12px 12px 20px 5px;"></span>
		This will permanently delete the page and its menu item (if it exists) from the database. Do you wish to proceed?</p>

	<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
		<div class="ui-dialog-buttonset">
			<button id="confirm-delete" class="btn btn-default btn-danger delete"><span class="glyphicon glyphicon-trash"></span> Delete</button>
			<button id="confirm-cancel" class="btn btn-default btn-default cancel"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
		</div>
	</div>

</div>
<!-- /.modal -->

<script type="text/javascript">


  var blockJSON = <?=json_encode($block)?>;


</script>
<script src="/base/lib/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
    basicBlock.init();
  });

  var basicBlock = {
    init: function(){
      //$("#form_basic").attr("action","/block/text/save/"+blockJSON.id+"'.$block['id'].'");

      $.each(blockJSON,function(id,val){
        if(typeof $("#"+id) != "undefined") $("#"+id).val(val);
				if(id == "show_title"){
					if(val == 0) $("#"+id).prop("checked",false);
					else $("#"+id).prop("checked",true);
				}
      });

      var roxyFileman = "/base/lib/fileman/index.html";
      CKEDITOR.replace("content",{filebrowserBrowseURL:roxyFileman,filebrowserImageBrowseUrl:roxyFileman+"?type=image",removeDialogTabs: "link:upload;image:upload"});
    }
		,deleteContent: function(){
			var deleteObj = {	'id': blockJSON.id, "title": blockJSON.title};
		 // list the items that are different
     $.post('/block/text/delete/', deleteObj,function(j){
				 window.location.href = "/"+j;
       }
     );
	  }
  }

  $(".save_content").on("click",function(){
    CKEDITOR.instances['content'].updateElement();
    var params = $("form").serializeArray();

		if(blockJSON == null) blockID = "";
		else blockID = blockJSON.id;

    $.post('/block/text/save/'+blockID,
      params,
      function(j){
        window.location.href = j;
        
    });

  });

  $(".cancel").on("click",function(){
    window.history.back();
  });

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
		$("#dialog-confirm").dialog( "close" );
		basicBlock.deleteContent();
	})

	$('#confirm-cancel').click(function(){
		$("#dialog-confirm").dialog( "close" );
	})

	$(".form-control").on("keypress",function(e){
			if (e.keyCode == 13){
				if(basic.validate() == true) basic.saveContent();
			}
	});

	$(".nullCheck").on("blur",function(){
		isEmpty($(this).val())? $(this).addClass('error') : $(this).removeClass('error');
	});

</script>

<?php
//footer
$fluid->page->footer_bottom .= ('');
$fluid->loadFooter();
?>
