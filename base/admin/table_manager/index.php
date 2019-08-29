<?php
	$fluid->loadHeader('Admin Page', '
		<link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
	');

	$fluid->loadComponents(array('Access'));
	$access = $fluid->components['Access'];
	$access->requirePermission('Admin','Permissions Manager');


	$db = $fluid->components['DB'];
	$user = $fluid->components['LDAP']->user;
	$fluid->page->title = "Table Manager";
	$bc = $fluid->components['Breadcrumbs'];
	$bc->set();

  if($bc->show == true){
    $bc->display();
    }
?>
	<style type="text/css">
		table{ font-size: 12px; }
		.edit{ cursor: pointer;	color: #337AB7;}
		td{	max-width: 250px;	word-break: break-all;}
		.ui-dialog {z-index: 9998 !important;}
		.ui-dialog-titlebar-close {  display: none;}
	</style>
	<!-- Modal -->
	<div data-backdrop="static" data-keyboard="false" class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	    <div class="modal-dialog">
	        <div class="modal-content">
	            <div class="modal-header">
	                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	                 <h4 id="modal_title" class="modal-title"></h4>
	            </div>
	            <div class="modal-body"><div class="te"></div>

							<!--dialog-->
							<div id="dialog-confirm" title="Delete Record Confirmation" style="display: none; ">
								<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>This will permanently delete the record from the database. Are you sure you want to delete this record?</p>
							</div>
							<div id="div_delete">
								<button class="btn btn-default delete_prompt"><span class="glyphicon glyphicon-remove"></span> Delete Record</button>
							</div>
							<form>
								<div id="div_form" class="form-group"></div>
							</form>
							</div>
	            <div style="padding-left: 20px"></div>
	            <div class="modal-footer">
	                <div align="center" style="padding: 5px" id="div_btn_grp"></div>
	            </div>
	        </div>
	        <!-- /.modal-content -->
	    </div>
	    <!-- /.modal-dialog -->
	</div>
	<!-- /.modal -->

	<div class="container">
		<h2>Table Manager</h2>

		<?php

			$arrTables = $db->getTableNames();

		?>

		<h3>Update
			<select id="sel_table">
				<?php
					for ($i = 0; $i < sizeof($arrTables); $i++){
						if ($i == 0) echo '<option value="'.$arrTables[$i].'" selected>'.$arrTables[$i].'</option>';
						else echo '<option value="'.$arrTables[$i].'">'.$arrTables[$i].'</option>';
					}
				?>
			</select>
		</h3>
		<button class='btn btn-primary add'><span class="glyphicon glyphicon-plus"></span> Add Row</button>
		<br />
		<div id="div_table"></div>
	</div>

<?php
	$fluid->loadFooter('
		<script type="text/javascript">
		var adminUser = "'.$user.'";
		</script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="admin.js"></script>
	');
 ?>
