<?php
	$fluid->loadHeader('Section Manager', '
	<link href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
	<link href="/base/lib/chosen/chosen.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="/base/lib/font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="../permissions.css" type="text/css" />
	');

	$fluid->loadPathFile('admin/access_manager/permission_includes.php');
	$bc = $fluid->components['Breadcrumbs'];
	$access = $fluid->components['Access'];
	$access->requirePermission('Fluid Site','Access Manager');
	$db = $fluid->components['DB'];
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
		<li class="active"><a href="/admin/access_manager/sections/"><span class="glyphicon glyphicon-th-list"></span> Manage Sections</a></li>
		<li><a href="/admin/access_manager/groups/" ><i class="fa fa-users" aria-hidden="true"></i> Manage Groups</a></li>
	</ul>
</div>
<div class="tab_content">
<button class="btn btn-primary add_section"><span class="glyphicon glyphicon-plus"></span> Add Section</button>

<div id="icon_help_div"><span class="glyphicon glyphicon-question-sign help"></span></div>
<div id="div_help" style="display: none">
	<h4>What are Sections?</h4>
	<hr style="border: 1px solid #ccc" />

	Sections are user-defined permission configuration scopes.  They can be used for defining layout permissions types, site-wide permission types, or very specific and custom access levels.  The management and complexity of sections is completely defined by the user and their purposes for their site.
<br /><br />
Each section can have one or more permission types.  These are also user defined to represent anything the user would like it to be.  They can be traditional permission types, such as allow, deny, or edit.  They can also represent another access level for that specific section, such as admin, editor or developer.  This means you can have the role of “editor”, but for a specific part of the site (where the section is defined), the role “editor” can have an “admin” permission type.  It would be up to the developer to define what the “admin” permission type would mean.   Meanwhile, in another section of the site, the role “editor” can have a “user” permission type, which the developer would then define in their code.


	<div align="center">
		<button class="btn btn-default close_help">Close</button>
	</div>
</div>

<br /><br />
<table class="display" id="sections_table">
  <thead>
    <tr>
      <th>Section</th>
      <th>Permission Types</th>
			<th style="width: 30px">Action</th>
    </tr>
    </thead>
    <tbody id="tbody_sections"></tbody>
</table>
</div>
</div>
<?php
	$fluid->loadFooter('
    <script src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/base/lib/chosen/chosen.jquery.js"></script>
		<script src="../permission_forms.js"></script>
    <script src="sections.js"></script>
		<script type="text/javascript">
    var currSite     = "'.$fluid->SITENAME.'";
    var sectionsJSON    = '.$access->getSectionsJSON().';
		</script>

	');
?>
