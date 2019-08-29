<?php
/*
DB fields
	colname				[type] 			helptext	{rules}
	-----------------------------------------------------------------------------------------------------
	id						[int]				auto incrementing unique value	{"editable":false}
	site					[text]			which site the link is restricted to	text	{"length":20}
	label					[text]			The text shown in the menu	{"length":20}
	url						[text]			The page the menu item links to. For different sites use the http prefix	text	{ "validation": "url", "length":255 }
	parent				[fieldref]	the parent menu item the links falls under	sites.id
	order					[int]				Display order folowed by alphabetical listing	{"unique":false}
	target				[dropdown]	how should the link be treated when clicked	{ "Default":"", "Open in new Tab":"_blank", "Page Refresh":"_top", "Same Frame":"_self", "Parent Frame":"_parent" }
	helptext			[text]			appears in hover help	{"length":255}
	hidden				[bool]			for links not in the menu to be used elsewhere on the page	{}
	modified_by		[user]			last user to modify this entry	current_user
	modified			[timestamp]	auto time stamp of last field modification	{"editable":false}
*/
class Menu {
	static $COMPONENT_PATH;
	private $requiredComponents = array('DB','Cache','LDAP','Access','replaceTags');
	public $tree = array();
	public $items = array();
	public $masterList = array();

	function __construct(){
		global $fluid;
		// check to see that required components haver already loaded
		foreach($this->requiredComponents as $component)
			if( !isset($fluid->components[$component]) )
				$fluid->loadErrorPage(500,"The $component component has not been loaded");
	}

	private function arrayToQuotedString($array, $quote='"', $delim=','){
		$wrapArray = array();
		foreach($array as $item)
			array_push($wrapArray, $quote.$item.$quote);
		return implode($delim,$wrapArray);
	}

	public function load( $sites = array() ) { // also accepts comma list of sites
		global $fluid;
		$db = $fluid->components["DB"];
		$replaceTags = $fluid->components['replaceTags'];

		if( gettype($sites) == 'array' && !count($sites) )
			$sites = $fluid->site->info->sites_for_menu;
		if( gettype($sites) == 'string' )
		$sites = $fluid->StringListToArray($sites); // convert comma string lists to array

		if( !count($sites) )
			array_push($sites,$fluid->site->info->id); // add in the current web site
		array_push($sites,1); // add in global menu items

		$sites = array_unique($sites); // ensure no duplicate values
		$siteIDs = $this->arrayToQuotedString($sites);

		$denyAccess = "Hide";
		$keywords = $fluid->components['Access']->keys;


		$groupDenial = "";
		$arrGroups = array();
		if(count($fluid->components['Access']->arrGroupIDs)){
			$groupIDs = $this->arrayToQuotedString($fluid->components['Access']->arrGroupIDs);
 			$groupDenial = "and group_id in ($groupIDs)";
			$arrGroups = $fluid->components['Access']->arrGroupIDs;
		}
		//$regexKeys = $this->arrayToQuotedString($keywords, ',', '|'); // ,item,|,item,|,item,

		// get all the menu items and place them in a master list
		$masterList = array( 'menu_0' => array('submenu' => array()) ); // add the the top entry
		$query =	"select m.*,s.folder as site, mr.id as restriction_id, mr.restriction, ifnull(group_concat(mr.group_id separator ','),'') as restrict_groups, lb.id as basic_id
								from fluid.menu m
								left join fluid.menu_restrictions mr on m.id = mr.menu_id
								left join fluid.sites s on m.site_id = s.id
								left join fluid.groups g on g.id = mr.group_id
								left join fluid.list_basic lb on lb.menu_id = m.id
								where m.site_id in ($siteIDs) and m.id NOT in
								(
									select menu_id as id from menu_restrictions mr
									where mr.restriction = '$denyAccess' $groupDenial
								)
								group by m.id, mr.restriction order by parent,weight,label";

		$row = $db->select($query);

		foreach($row as $obj){

			if($obj['restriction'] == "Show"){
				$arrShow = explode(",",$obj['restrict_groups']);
				if(count(array_intersect($arrShow,$arrGroups)) == 0)
					continue; //skip rest of the loop if group is not in exclusive show groups
			}

			$masterList['menu_'.$obj['id']] = $obj;
			$masterList['menu_'.$obj['id']]['db_id'] = $obj['id'];
			$masterList['menu_'.$obj['id']]['id'] = 'menu_'.$obj['id'];
			$masterList['menu_'.$obj['id']]['parent'] = 'menu_'.$obj['parent'];
			$masterList['menu_'.$obj['id']]['label_render'] = $replaceTags->fromText( $obj['label'] );
			$masterList['menu_'.$obj['id']]['weight_label'] = sprintf('%09d', $obj['weight']*1) . " " . $obj['label'];
			$masterList['menu_'.$obj['id']]['submenu'] = array(); // stub entry for sub items
		}

		// create reference submenu items for each thing
		foreach($masterList as $id => &$obj) {
			// place item in parent's submenu by reference
			if( isset($obj['parent'])
				 && !empty($obj['parent'])
				 && isset($masterList[$obj['parent']])
			) {
				$masterList[$obj['parent']]['submenu'][] = &$obj;
			}
		}
		$this->tree = $masterList['menu_0']['submenu'];
		unset($masterList['menu_0']);
		$this->items = $masterList;

	}

	public function jsonData() {
		// returns json object of the menu structure, note does not preserve references
		return json_encode($this->tree);
	}

	public function flatArray($item=null, $level=0) {
		// returns a single layer array for screen readable output
		if( !isset($item) )
			$item = $this->tree;

		$output = array();

		foreach($item as $id => $obj) {
			//echo str_repeat("	",$level) . $obj['label']."\n";
			$newObj = $obj;
			$newObj['level'] = $level;
			unset($newObj['submenu']);

			array_push($output,$newObj);
			if( isset($obj['submenu']) && count($obj['submenu']) ) {
				//repeat process for sub menu items
				$sub = $this->flatArray($obj['submenu'],$level+1);
				$output = array_merge($output,$sub);
			}
		}
		return $output;
	}

	public function flatList() {
		// returns a single layer array for screen readable output

		$item = $this->items;

		$output = array();

		foreach($item as $id => $obj) {
			//echo str_repeat("	",$level) . $obj['label']."\n";
			$newObj= $obj;
			//$newObj['submenu'] = array();
			unset($newObj['submenu']);

			//$newObj['weight'] = sprintf('%09d', $newObj['weight'] );

			$output[$id] = $newObj;

		}

		$output['menu_unk'] = array();
		$output['menu_unk']['id'] = "menu_unk";

		return $output;
	}

	public function editMenuItem($params,$arrUpdate,$restrict,$restrictType,$origRestrictStr,$origRestrictType){
		global $fluid;
		$db = $fluid->components['DB'];
		$access = $fluid->components['Access'];
		$arrUpdateFields = array_keys($arrUpdate);

		$arrCols = $db->getColumns("menu");
		for ($i = 0; $i < sizeof($arrCols); $i++){
			if($arrCols[$i]->Extra == "auto_increment") $arrUpdateFields = array_diff($arrUpdateFields,array($arrCols[$i]->Field));
		}

		$access->prepareRestrictions();
		$arr   		= explode(",",$restrict);
		$origRestrict 	= explode(",",$origRestrictStr);
		$deleteGroups 	= array_diff($origRestrict,$arr);
		$restrictGroups = $restrictType == $origRestrictType ? array_values(array_diff($arr,$origRestrict)) : $arr;

		$arrRestrict = array("restriction"					=> $restrictType,
												 "orig_restriction" 		=> $origRestrictType,
												 "restrict_groups" 			=> $restrictGroups,
											 	 "delete_groups"				=> $deleteGroups);

		$access->editRestrictItem($params,$arrUpdateFields,$arrRestrict,"menu");
	}

	public function addMenuItem($params,$restrict,$restriction){
		global $fluid;
		//$db 						= $fluid->components['DB'];
		$access 				= $fluid->components['Access'];
		$updateFields 	= array_keys($params);
		$restrictGroups = explode(",",$restrict);

		$arrRestrict = array("restriction"=>$restriction, "restrict_groups"=>$restrictGroups);

		$access->prepareRestrictions();
		$menuID = $access->addRestrictItem($params,$updateFields,$arrRestrict,"menu");
		echo $menuID;
	}

	public function getGroupJSON(){
		global $fluid;
		$db = $fluid->components['DB'];
		$arrGroups = array();

		$result = $db->select("select g.*,s.folder from groups g, sites s where s.id = g.site_id and g.site_id in (1,:site_id)",array(":site_id"=>$fluid->site->info->id));
		for ($i = 0; $i < count($result); $i++){
			$arrGroups['group_'.$result[$i]['id']] = array("id"					=>$result[$i]['id'],
																										 "group"			=>$result[$i]['group_name'],
																										 "description"=>$result[$i]['description'],
																										 "site_id"		=>$result[$i]['site_id'],
																										 "site"				=>$result[$i]['folder']
																										);
		}
		return json_encode($arrGroups);
	}

	public function updateChildren($parentID){
		global $fluid;
		$db = $fluid->components['DB'];
		$db->execPublic("update menu set parent = 0 where parent = :parentID",array(":parentID"=>$parentID));
	}

	public function printMenu($submenu=null, $level=1){
		if( !isset($submenu) )
			$submenu = $this->tree;

		for ($i = 0; $i < sizeof($submenu); $i++){
			echo '<option class="level'.$level.'" value="'.$submenu[$i]['db_id'].'">' . $submenu[$i]['label'] . "</option>\n";
			if (sizeof($submenu[$i]['submenu']) > 0) echo $this->printMenu($submenu[$i]['submenu'], $level+1);
		}
	}
}
