<?php

$fluid->page->title = ucfirst($fluid->page->action) . " Page";
$fluid->components['Breadcrumbs']->set();

if($fluid->components['Breadcrumbs']->show == true)
	$fluid->components['Breadcrumbs']->display();
?>
<form id="form_basic" class="form-horizontal" onsubmit="return false">
	<div class="title">Page Settings</div>
	<div class="form-content">
		<input id="basic_id" name="id" type="hidden" value="">
		<input id="basic_menu_id" name="menu_id" type="hidden" value="">
		<input id="basic_site_id" name="site_id" type="hidden" value="<?= $fluid->site->info->id?>">

		<div class="form-group">
			<label class="col-sm-2 control-label">Published</label>
			<div class="col-sm-10">
				<input id="basic_publish" name="publish" type="checkbox" value="1">
				site visitors can only view published pages
			</div>
		</div>

		<div class="form-group">
			<label class="col-sm-2 control-label">Title<span class="required">*</span></label>
			<div class="col-sm-10">
				<input id="basic_title" class="form-control nullCheck" name="title" type="text" value="">
			</div>
		</div>

		<div class="form-group">
			<label class="col-sm-2 control-label">URL Alias</label>
			<div class="col-sm-10">
				<div class="required" id="alias_check"></div>
				<input class="form-control" id="basic_alias" name="alias" type="text" value="">
			</div>
		</div>

		<div class="form-group">
			<label class="col-sm-2 control-label">Content</label>
			<div class="col-sm-10">
				<textarea class="form-control" cols="80" id="basic_content" name="content" rows="10"></textarea>
			</div>
		</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">Access Overrides</label>
		<div class="col-sm-3">
			<select id="basic_restriction" name="restriction" class="form-control">
				<option value="">No Overrides</option>
				<option value="Deny">Everyone except for</option>
				<option value="Allow">Only</option>
			</select>
		</div>
		<div class="col-sm-7" id="div_restrictions" style="display: none; left: -20px; top: 3px;">
			<select name="restrict_groups" class="chosen-select form-control" id="basic_restrict_groups" multiple tabindex="4" >
				<?php
				foreach($page->keywords as $g){
					$descriptionStr = $g['description'] == "" ? "" : ' ('.$g['description'].')';
					echo '<option value="'.$g['id'].'">'.$g['group'].$descriptionStr.'</option>';
				}
				?>
			</select>
			can access this page.
			<br />
			<!--<input type="hidden" id="orig_restriction" name="orig_restriction" />
			<input type="hidden" id="orig_restrict_groups" name="orig_restrict_groups" />-->
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">Extend Edit Access</label>

		<div class="col-sm-10">
		<select name="edit_groups" class="chosen-select form-control" id="basic_edit_groups" multiple tabindex="4" >
			<?php
			foreach($page->keywords as $g){
				$descriptionStr = $g['description'] == "" ? "" : ' ('.$g['description'].')';
				echo '<option value="'.$g['id'].'">'.$g['group'].$descriptionStr.'</option>';
			}
			?>
		</select>
		can also edit this page.
		<br />
		<!--<input type="hidden" id="orig_edit_groups" name="orig_edit_groups" />-->
		</div>
	</div>
	</div>
</form>


<form id="form_menu" class="form-horizontal" onsubmit="return false">
	<div class="title">Menu Settings</div>
	<div id="form_menu" class="form-content">

		<input id="menu_id" name="id" type="hidden" value="">
		<input id="menu_site" name="site_id" type="hidden" value="<?= $fluid->site->info->id?>">

		<div class="form-group">
			<label class="col-sm-2 control-label">Display in Menu</label>
			<div class="col-sm-10">
				<input id="menu_display" name="display" type="checkbox" value="1">
			</div>
		</div>

		<div id="tbody_menu">
			<div class="form-group">
				<label class="col-sm-2 control-label">Parent</label>
				<div class="col-sm-10">
					<select class="form-control" id="menu_parent" name="parent">
						<option selected value="0">Menu Bar [Top Level]</option>
						<?= $menu->printMenu() ?>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-2 control-label">Label<span class="required">*</span></label>
				<div class="col-sm-10">
					<input id="menu_label" class="form-control nullCheck" name="label" type="text" value="">
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-2 control-label">Target</label>
				<div class="col-sm-10">
					<select class="form-control" id="menu_target" name="target">
						<option value="">Default</option>
						<option value="_blank">Open in New Tab</option>
						<option value="_top">Page Refresh</option>
						<option value="_self">Same Frame</option>
						<option value="_parent">Parent Frame</option>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-2 control-label">Weight<span class="required">*</span></label>
				<div class="col-sm-10">
					<input class="form-control nullCheck" id="menu_weight" name="weight" type="text" value="0">
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-2 control-label">Help Text</label>
				<div class="col-sm-10">
					<input class="form-control" id="menu_helptext" name="helptext" type="text" value="">
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-2 control-label">Access Restrictions</label>
				<div class="col-sm-3">
					<select id="menu_restriction" name="restriction" class="form-control">
						<option value="">No Access Restrictions</option>
						<option value="Hide">Also Hide From</option>
						<option value="Show">Only Show Menu To</option>
					</select>
				</div>
				<div class="col-sm-7" id="div_menu_restrictions" style="display: none; left: -20px; top: 3px;">
					<select name="restrict_groups" class="chosen-select form-control" id="menu_restrict_groups" multiple tabindex="4" >
						<?php
						foreach($page->keywords as $g){
							$descriptionStr = $g['description'] == "" ? "" : ' ('.$g['description'].')';
							echo '<option value="'.$g['id'].'">'.$g['group'].$descriptionStr.'</option>';
						}
						?>
					</select>
					<br />
					<!--<input type="hidden" id="menu_orig_restriction" name="orig_restriction" />
					<input type="hidden" id="menu_orig_restrict_groups" name="orig_restrict_groups" />-->
			</div>
		</div>
	</div>
	</div>
</form>


<form id="form_buttons" class="form-horizontal" onsubmit="return false">
	<div id="form_buttons" align="center" style="padding: 10px;">
		<button class="btn btn-success save_content"><span class="glyphicon glyphicon-pencil"></span> Save</button>
		<button class="btn btn-danger delete_prompt" data-toggle="tooltip" title="Delete Page"><span class="glyphicon glyphicon-trash"></span> Delete Page</button>
		<button class="btn btn-default cancel"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
	</div>
</form>

<form id="form_redirect" action="" method="post">
	<input type="hidden" id="redirect_message" name="system_message" value="">
</form>


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
	var pageAction = "<?= $page->action ?>";
	var basicInfo = <?= json_encode( $basic )?>;
	var menuInfo = <?= json_encode( $page->menu )?>;
	var keywords = <?= json_encode( $page->keywords )?>;
	var menuList = <?= json_encode( $menu->flatList() )?>;
</script>
