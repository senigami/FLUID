<?php
	$fluid->loadHeader('Groups Manager', '
	<link rel="stylesheet" href="/base/lib/font-awesome/css/font-awesome.min.css">
	<link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="/base/admin/access_manager/permissions.css" type="text/css" />
	');

	#COMPONENTS
	$bc = $fluid->components['Breadcrumbs'];
	$access = $fluid->components['Access'];
	$db = $fluid->components['DB'];
	$ldap = $fluid->components['LDAP'];

	$access->requirePermission('Fluid Site','Access Manager');
	$fluid->loadPathFile('admin/access_manager/permission_includes.php');

  //$pageInfo['header_bottom'] = '<link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />';
?>

<style type="text/css">
div.tab_content{background-color: white; padding: 10px; border: 1px solid #ccc; border-top: 0px; border-radius: 0px 0px 5px 5px;}
</style>

<div class="container-fluid" role="main">

<?php
$bc->set();
$bc->display();
?>

<h3>Access Manager</h3>
<br />

<div>
	<ul class="nav nav-tabs">
		<li><a href="/admin/access_manager/main/"><span class="glyphicon glyphicon-check"></span> Manage Permissions</a></li>
		<li><a href="/admin/access_manager/sections/"><span class="glyphicon glyphicon-th-list"></span> Manage Sections</a></li>
		<li class="active"><a href="/admin/access_manager/groups/" ><i class="fa fa-users" aria-hidden="true"></i> Manage Groups</a></li>
	</ul>
</div>

<div class="tab_content">
<button class="btn btn-primary add_group"><span class="glyphicon glyphicon-plus"></span> Add Group</button>


<div id="icon_help_div"><span class="glyphicon glyphicon-question-sign help"></span></div>
<div id="div_help" style="display: none">
	<h4>What are Groups?</h4>
	<hr style="border: 1px solid #ccc" />

	Groups are composed of one or more defined LDAP field type values.  One or more groups can comprise a role, which is used for site permission management.
	<br /><br />
	FLUID currently supports the following LDAP field types:<br /><br />
	<table class="table table-striped">
		<tr><th>LDAP Field Type</th><th>Description</th><th>Example Value</th></tr>
		<tr><td>User</td><td>Username</td><td><?php echo $ldap->userInfo->uid; ?></td></tr>
		<tr><td>Group</td><td>QGroup or Mail List</td><td>qcae.info</td></tr>
		<tr><td>Employee Number</td><td>Specified Employee Number</td><td><?php echo $ldap->userInfo->qcguid; ?></td></tr>
		<tr><td>Department</td><td>Specified department</td><td>
			<?php
				$tmp = explode(" - ",$ldap->userInfo->departmentnumber);
				echo $tmp[0];
			?>
			</td></tr>
		<tr><td>Business Unit</td><td>Specified Business Unit</td><td><?php echo $ldap->userInfo->qcbusinessunitdesc; ?></td></tr>
		<tr><td>Region</td><td>Specified Region</td><td><?php echo $ldap->userInfo->l; ?></td></tr>
	</table>

	<div align="center">
		<button class="btn btn-default close_help">Close</button>
	</div>
</div>

<br/ ><br />
<table class="display" id="keywordTable">
  <thead>
    <tr>
      <th>Group</th>
			<th>Description</th>
      <th>Members</th>
			<th style="width: 30px">Action</th>
    </tr>
    </thead>
		<tbody></tbody>
</table>
</div>

</div>
<?php
	$fluid->loadFooter('
    <script src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
		<script src="../permission_forms.js"></script>
		<script src="groups.js"></script>
		<script type="text/javascript">
			var currSite		= "'.$fluid->SITENAME.'";
			var keywordJSON = '.$access->getGroupJSON().';
			var typeJSON    = '.$access->getTypeJSON().';

			$(document).ready(function(){
			  keywordTable.init();
			});

		</script>

	');
?>
