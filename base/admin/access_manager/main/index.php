<?php

	$fluid->loadHeader('Access Manager', '
		<link href="/base/lib/chosen/chosen.css" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="/base/lib/font-awesome/css/font-awesome.min.css">
		<link rel="stylesheet" href="/base/admin/access_manager/permissions.css" type="text/css" />
	');

	$bc = $fluid->components['Breadcrumbs'];
	$db = $fluid->components['DB'];
	$access = $fluid->components['Access'];
	$access->requirePermission('Admin','Permissions Manager');
	$user = $fluid->components['LDAP']->user;

	$fluid->loadPathFile('admin/access_manager/permission_includes.php');
?>

<style type="text/css">
.table_header,.header{cursor: pointer}
td,th{
	text-align: center;
	width: 100px;
}
tr.section_header td{ text-align: left; font-weight: bold; z-index: 50}
th.permission{ text-align: left; position: relative; left: 20px;}
tr.btn-default:hover{  background-position: 0px;}
tr.btn-primary:hover{ background-position: 0px;}
tr.btn-success:hover{ background-position: 0px;}
.popover h3{color: black;}
div.description{ font-size: 11px; color: gray; font-weight: normal;}
div.tab_content{background-color: white; padding: 10px; border: 1px solid #ccc; border-top: 0px; border-radius: 0px 0px 5px 5px;}

span.checkbox{
	font-size: 12px;
	padding: 2px;
	 padding-left: 6px;
	 padding-right: 6px;
	 margin-top: 2px;
}
span.checkbox input{
	visibility: hidden
}
.glyphicon-none:before {
    content: "\2122";
	  visibility: hidden;
}



</style>
<div class="container-fluid" role="main">

<?php
$bc->set();
$bc->display();
?>


<h3>Access Manager</h3>
<br />

<div id="alert_text"></div>

<div >
	<ul class="nav nav-tabs">
		<li class="active"><a href="/admin/access_manager/main/"><span class="glyphicon glyphicon-check"></span> Manage Permissions</a></li>
		<li><a href="/admin/access_manager/sections/"><span class="glyphicon glyphicon-th-list"></span> Manage Sections</a></li>
		<li><a href="/admin/access_manager/groups/" ><i class="fa fa-users" aria-hidden="true"></i> Manage Groups</a></li>
	</ul>
</div>
<div class="tab_content">
<button class="btn btn-default btn-collapse"><span class="glyphicon glyphicon-chevron-down"></span> Collapse All</button>

<!--TODO later-->
<span id="span_permission_filter"></span>
<span id="span_role_filter"></span>
<!-- -->


<div id="icon_help_div"><span class="glyphicon glyphicon-question-sign help"></span></div>
<div id="div_help" style="display: none">
	<h4>How Do I Configure My Permissions?</h4>
	<hr style="border: 1px solid #ccc" />

	Permissions are configured by defining which section’s permission types are assigned for each role.  Permissions can be assigned by clicking on the buttons corresponding to the appropriate permission type and role.  Any new changes from the last saved values will show up with yellow backgrounds.  Once all the desired changes have been made, you will want to click on the “Save” button at the bottom.  You should see all checkboxes turned to green at this point.  If you have not yet saved your permissions, and would like to revert back to the original values, you can do so by clicking on “Revert”.


	<div align="center" style="padding-top: 10px">
		<button class="btn btn-default close_help">Close</button>
	</div>
</div>
<br /><br />

<form onsubmit="return false;">
<div id="div_permissions"  ></div>
</form>
</div>

</div>
<?php
	//}
	$fluid->loadFooter('
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
		<script type="text/javascript" src="/base/lib/chosen/chosen.jquery.js"></script>
		<script type="text/javascript">
		var siteID = '.$fluid->site->info->id.';
		</script>
		<script src="permissions.js"></script>
		<script type="text/javascript">
			var permissionsJSON	 	 = '.$access->getPermissionsJSON().';
		</script>
	');
?>
