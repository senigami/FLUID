<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$access = $fluid->components['Access'];
$access->requirePermission('Admin','Menu Editor');

	$fluid->loadHeader('Menu Manager', '
		<link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
		<link href="/base/lib/chosen/chosen.css" rel="stylesheet" type="text/css" />
	');

	$db    = $fluid->components['DB'];
	$user  = $fluid->components['LDAP']->user;
	$cache = $fluid->components['Cache'];
	$menu  = $fluid->components['Menu'];
	$bc		 = $fluid->components['Breadcrumbs'];
	#$fluid->keywords = $access->listStr;
?>
	<style type="text/css">
		table{ font-size: 11px;}
		table.table-modal{ font-size: 12px}
		.edit{ cursor: pointer; padding: 3px 8px 3px 8px}


		td{	max-width: 250px;	word-break: break-all;}
		.title{ border-radius: 5px 5px 0px 0px; color: white; padding: 5px; font-weight: bold}
		.select_content{ border: 1px solid gray; padding: 10px; border-radius: 0px 0px 5px 5px}
		.required{ color: red; font-weight: bold; font-size: 16px}
		.disabled{color: gray;}
		a.disabled:hover{ text-decoration: none; cursor: not-allowed;}

		.modal-header{
		    -webkit-border-radius: 5px 5px 0px 0px;
		    -moz-border-radius: 5px 5px 0px 0px;
		    border-radius: 5px 5px 0px 0px;
				cursor: pointer;
		}
		.modal-content{
			-webkit-border-radius: 8px 8px 5px 5px;
			-moz-border-radius: 8px 8px 5px 5px;
			border-radius: 8px 8px 5px 5px;
		}

		.menu_dialog {z-index: 9998 !important; background-color: white; border: 1px solid gray;}
		.menu_dialog .ui-dialog-titlebar{padding: 6px;}
								 .ui-dialog-titlebar-close {  display: none;}
								 .ui-dialog-buttonset{ text-align: center}
								 .ui-dialog-buttonset button{ margin: 8px;}
								 .ui-widget-content { background-color: white; border: 0px}


		#div_btn_grp button{ margin: 8px;}
		#dialog-confirm {border: 0px;}
		#dialog-confirm button {  margin: 10px}
		/* add back when chrome allows for css in select options
		.level1{}
		.level2{ padding-left: 20px}
		.level3{ padding-left: 40px}
		.level4{ padding-left: 60px}*/
		#table_menu td {
			overflow: hidden;
			padding: 3px;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		#div_restrictSelect{ padding-top: 10px}
		/*.dataTables_filter label{margin-left: 10px; width: 40%; float: right}
		/*.dataTables_filter input{ width: 100%}*/
	</style>
	<!-- Modal -->
	<div data-backdrop="static" data-keyboard="false" class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	    <div class="modal-dialog">
	        <div class="modal-content">
	            <div class="modal-header btn-primary">
	                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	                 <h4 id="modal_title" class="modal-title ui-draggable-handle"></h4>
	            </div>
	            <div class="modal-body"><div class="te"></div>

							<!--dialog-->
							<div id="dialog-confirm" title="Delete Menu Item Confirmation" style="display: none; padding-top: 10px">
								<p><span class="ui-icon ui-icon-alert" style="color: red; float:left; margin:12px 12px 20px 5px;"></span>
									This will permanently delete the menu item from the database. Are you sure you want to delete this record?</p>
									<div align="center">
										<button class="btn btn-default btn-danger delete"><span class="glyphicon glyphicon-trash"></span> Yes</button>
										<button class="btn btn-default btn-default cancel"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
									</div>
							</div>

							<form>
								<div id="div_form" class="form-group"></div>
							</form>
							</div>
	            <div style="padding-left: 20px"></div>
	            <div class="modal-footer">
	                <div align="center" style="padding: 10px" id="div_btn_grp"></div>
	            </div>
	        </div>
	        <!-- /.modal-content -->
	    </div>
	    <!-- /.modal-dialog -->
	</div>
	<!-- /.modal -->

<div class="container-fluid">
		<?php
		$bc->set();
		$bc->display();
		?>


		<h2>Menu Manager</h2>

		<br />
		<button class="btn  add btn-primary"><span class="glyphicon glyphicon-plus"></span> Add Menu Item</button>
		<br /><br />

		<div class=" bg-primary title">Select Site(s)</div>
		<div class=" select_content" id="div_sites">

			<?php
			/*$arrSites = $db->select("select folder as site from sites");
			for($i = 0; $i < sizeof($arrSites); $i++){
				if ($arrSites[$i]['site'] == "[GLOBAL]" || $arrSites[$i]['site'] == $fluid->site->name)
					echo '<input class="site_filter" checked name="site[]" type="checkbox" value="'.$arrSites[$i]['site'].'" /> '.$arrSites[$i]['site'] . '<br />';
				else
					echo '<input class="site_filter" name="site[]" type="checkbox" value="'.$arrSites[$i]['site'].'" /> '.$arrSites[$i]['site'] . '<br />';
			}*/

			$arrSites = array();
			$result = $db->select("select id,folder as site from sites");
			for($i = 0; $i < count($result); $i++){
				$arrSites["site_".$result[$i]['id']] = array("id"=>$result[$i]['id'],"site"=>$result[$i]['site']);
			}

			$menuSites = explode(",",$fluid->site->info->sites_for_menu);


			?>

			<select id="sel_sites" class="chosen-select form-control site_filter" multiple tabindex="4">
				<?php

				foreach($arrSites as $key=>$arr){
					if($arr['id'] == 1 || in_array($arr['id'],$menuSites))
						echo '<option value="'.$arr['id'].'" selected> '.$arr['site'] . '</option>';
				}

				?>
			</select>
		</div>


		<br />
		<div id="div_menu">
			<table id="table_menu" class="display" >
				<thead>
					<tr>
						<th>Menu Item</th>
						<th style="width: 50px; text-align: right">Weight</th>
						<th>Site</th>
						<th>URL</th>
						<th>Target</th>
						<th>Show</th>
						<th>Hide</th>
						<th>Edit</th>
					</tr>
				</thead>
			<!--<tbody id="tbody_menu"></tbody>-->
		</table>
	</div>
</div>



<?php
	$site = $fluid->SITENAME == "base" ? "[GLOBAL]" : $fluid->site->name;
	$fluid->loadFooter('
		<script type="text/javascript">
		var menuUser = "'.$user.'";
		</script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="/base/lib/chosen/chosen.jquery.js"></script>
		<script type="text/javascript" src="menu.js"></script>
		<script type="text/javascript">
			var currSite			= "'. $site  .'";
			var currSiteID  	= "'.$fluid->site->info->id.'";
			var menuJSON 			= '.json_encode($menu->flatList()).'; 														//flat
			var siteJSON 			= '.json_encode($arrSites).' 																			//sites
			var groupJSON 		= '.$menu->getGroupJSON().';																			//groups
			var restrictJSON  = '.$access->getMenuAccess().';																		//menu access
		</script>

		');
		#var keywordJSON = '.json_encode($db->select("select keyword from keywords")).';		//keywords
 ?>
 <!--
 var menuList 		= '.$menu->jsonData().'; 														//flat
 var menuJSON 		= '.json_encode($menu->flatList()).'; 															//flat-->
