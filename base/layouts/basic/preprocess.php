<?php
// quick access handles
	$Access = $fluid->components['Access'];
	$page = &$fluid->page;
	$menu 	= $fluid->components['Menu'];
	$db		  = $fluid->components['DB'];
	//$directory = $fluid->page->pathinfo['dirname'];
	//$page = $fluid->page->pathinfo['basename'];
	$page->footer_bottom .= '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>';

// analyze the URL
	$pathParts = explode('/', $fluid->site->url );
	$page->action = ($pathParts[0]=='page')?$pathParts[1]:'load';

	switch( $page->action ) {
		case 'add':
		case 'edit': page_edit(); break;

		case 'save': page_save(); break;

		case 'list':
			if($Access->hasPermission("Basic Page","Edit")){
				require_once('list.php');
				exit;
			}
			else $Access->accessDenied();

		case 'load':	default:
			$page->action = 'load';
			if( !page_loadFromDB() )
				return; // can't find page, send redirect
			$fluid->loadComponent('replaceTags');
			$page->basic['content'] = $fluid->components['replaceTags']->fromText( $page->basic['content'] );
	}

	$arrOverrides = $Access->getOverrides($page->basic['id'],"list_basic_overrides","page_id");
	$fluid->page->vars['arrOverrides'] = $arrOverrides;
	if($Access->hasAllow == true && !in_array("Allow",$arrOverrides))
			$Access->accessDenied();
	else{
		// if the page is not published and user is not allowed to view unpublished
		if( !$page->basic['publish'] && !$Access->hasPermission('Basic Page','View Unpublished'))
			$Access->accessDenied();

		// if the page is retired and user is not allowed to view retired/archived
		if( $page->basic['retire'] && !$Access->hasPermission('Basic Page','View Archived'))
			$Access->accessDenied();

		if (in_array("Deny",$arrOverrides))
			$Access->accessDenied();
	}

// send out the page header info using the last modified date from the db entry
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Last-Modified: " . gmdate('D, d M Y H:i:s T', strtotime($page->basic['modified'])) );
	$page->header_top .= '<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
		<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">';
	$fluid->loadHeader();

function page_loadFromDB() {
	global $fluid;
	$db		= $fluid->components['DB'];
	$page = &$fluid->page;

	$sites = $fluid->site->info->sites_for_menu;

	if( gettype($sites) == 'string' )
	$sites = $fluid->StringListToArray($sites); // convert comma string lists to array

	if( !count($sites) )
	array_push($sites,$fluid->site->info->id); // add in the current web site

	array_push($sites,1); // add in global menu items

	$sites = array_unique($sites); // ensure no duplicate values
	$siteIDs = implode(",",$sites);

	// get page info from the database
	$arrParams = array(
		//'SITE_IDS' => $siteIDs,
		'ALIAS' => $fluid->site->url,
		'FILENAME' => $fluid->page->pathinfo['basename']
	);

	$query = "select lb.*,lbo.page_id,lbo.group_id,lbo.access,group_concat(lbo.group_id separator ',') as groupIDs
from fluid.list_basic lb left join fluid.list_basic_overrides lbo on lbo.page_id = lb.id
where lb.site_id in ($siteIDs) and (lb.alias = :ALIAS OR lb.alias = :FILENAME OR lb.id=:FILENAME)
 group by site_id, lbo.access order by lb.alias desc, lb.site_id desc";

	$result = $db->select($query,$arrParams);


	if( !count($result) ) {
		$fluid->page->loadSuccess = false;
		return false; // no db entry found so we will send a page not found error
	}

	$page->basic = $result[0];
	$page->basic['id'] = $page->basic['id']*1;
	$page->basic['menu_id'] = $page->basic['menu_id']*1;
	$page->basic['publish'] = $page->basic['publish']*1;
	$page->basic['retire'] = $page->basic['retire']*1;
	$page->basic['restriction'] = "";
	$page->basic['restrict_groups'] = array();
	$page->basic['edit_groups'] = array();
	$page->title = $page->basic['title'];


	for ($i = 0; $i < count($result); $i++){
		if( $result[$i]['access'] != "Edit"){
			$page->basic['restriction'] = $result[$i]['access'];
		}
		$access = $result[$i]['access'] == "Edit" ? "edit" : "restrict";
		$page->basic[$access."_groups"] = explode(",",$result[$i]['groupIDs']);
	}

	return true;
}

function page_edit() {
	global $fluid;
	$fluid->components['Access']->requirePermission('Basic Page','Edit');

	$db		  	= $fluid->components['DB'];
	$page 		= &$fluid->page;
	$menu 		= $fluid->components['Menu'];
	$menu->load();


	$_SESSION['dynamicFolder'] = "/sites/{$fluid->site->name}/files";

	if( $page->action == 'add')
		page_add();
	else {
		if( !page_loadFromDB() ) {
			$fluid->page->loadSuccess = false;
			return; // no db entry found so we will send a page not found error
		}

		// load in the menu selections and layouts?
		$page->menu = json_decode('{"id":0,"site":"","label":"","url":"","parent":"","weight":0,"target":"","helptext":"","restriction":"","restrict_groups":[],"modified_by":"","created":"","modified":""}');

		$query = "select m.*,site.folder as site, mr.restriction, ifnull(group_concat(mr.group_id separator ','),'') as restrict_groups
							from fluid.menu m
							left join fluid.menu_restrictions mr on m.id = mr.menu_id
							left join fluid.sites site on  m.site_id = site.id
							left join fluid.groups g on g.id = mr.group_id
							where
							m.id = :menu_id
							group by restriction";

		$result = $db->select($query,array("menu_id"=>$page->basic['menu_id']));

		if( count($result) ) {
			$page->menu = $result[0];
			$page->menu['id'] = $page->menu['id']*1;
			$page->menu['parent'] = $page->menu['parent']*1;
			$page->menu['weight'] = $page->menu['weight']*1;
			$page->menu['restriction'] = $result[0]['restriction'];
			$page->menu['restrict_groups'] = explode(",",$result[0]['restrict_groups']) ;
		}
	}

	//get group info- global groups included for now, but not sure if we want to include them?  need to rename to groups later
	$page->keywords = array();
	$result = $db->select("select id,group_name,description from groups where site_id in(1,:site_id)",array(":site_id"=>$fluid->site->info->id));
	for ($i = 0; $i < count($result); $i++){
		array_push($page->keywords,array("id"=>$result[$i]['id'],"group"=>$result[$i]['group_name'],"description"=>$result[$i]['description']));
	}

	$page->header_bottom .= '
		<link href="/lib/chosen/chosen.css" rel="stylesheet" type="text/css" />';

	$page->footer_bottom .= '<script src="/lib/util.js"></script>
		<script src="/layouts/basic/basic.js"></script>
		<script type="text/javascript" src="/lib/chosen/chosen.jquery.js"></script>
		<script src="/lib/ckeditor/ckeditor.js"></script>
		';
}


function page_add() {
	global $fluid;
	$page = &$fluid->page;

	$page->title = 'New Basic Page';
	$page->basic = array(
		'id' => 0,
		'site' => $fluid->site->name,
		'site_id' => $fluid->site->info->id,
		'alias' => '',
		'title' => '',
		'publish' => 0,
		'retire' => 0,
		'content' => '',
		'menu_id' => 0,
		'modified_by' => '',
		'created' => '',
		'modified' => ''
	);

	// load in the menu selections and layouts?
	$page->menu = array(
		'id' => 0,
		'site' => $fluid->site->name,
		'label' => '',
		'url' => '',
		'parent' => '',
		'weight' => 0,
		'target' => '',
		'helptext' => '',
		/*'allow' => Array(),
		'deny' => Array(),*/
		'restriction' => '',
		'restrict_groups' => array(),
		'modified_by' => '',
		'created' => '',
		'modified' => ''
	);
}

function page_save() {
}

function content($theVar,$defaultValue=null) { // get submitted values
		return isset($_REQUEST[$theVar])?$_REQUEST[$theVar]:$defaultValue;
}
