<?php
class Access {
	static $COMPONENT_PATH;
	private $requiredComponents = array('DB','LDAP');

	public $keys = array();
	public $permissions = array();
	public $keyStr = '';
	public $siteID = 0;
	public $arrGroupIDs = array();

	//--RESTRICTION/OVERRIDE VARIABLES--//
	public $restrictTables = array();
	public $restrictCols = array();
	public $arrAccess = array();
	public $arrAccessIDs  = array();
	public $hasAllow = false;

	function __construct(){
		global $fluid;
		// check to see that required components haver already loaded
		foreach($this->requiredComponents as $component)
			if( !isset($fluid->components[$component]) )
				$fluid->loadErrorPage(500,"The $component component has not been loaded");

		$this->loadKeywords();
		$this->loadPermissions();
	}
	private function createTypeString(){
		global $fluid;
		$db = $fluid->components['DB'];
		$result = $db->select("select * from groups_types",array());
		$typeStr = "";
		$userInfo = $fluid->components['LDAP']->userInfo;

		if( isset($userInfo->error) )
			return '';


		for ($i = 0; $i < sizeof($result); $i++){


				$addToType = "";

				$ldap_key = array();
				if( isset($userInfo->$result[$i]['ldap_key']) )
					 $ldap_key = $userInfo->$result[$i]['ldap_key'];
					 
				if ($result[$i]['type'] == "Group") $typeStr .= "km.member in ('".implode("','",$ldap_key)."')";
				else if ($result[$i]['type'] == "Department Number" && isset($userInfo->departmentnumber) ){
					$tmp = explode(" - ",$userInfo->departmentnumber);
					$deptNum = str_pad($tmp[0], 5,"0",STR_PAD_LEFT);
					$addToType .= "km.member = '$deptNum'";
				}

				/*
				need to figure out searching for "Engineer" vs. "Engineer, Senior" vs. "Manager, Senior"
				else if ($result[$i]['type'] == "Title"){
					$tmp = explode(",",preg_replace('/[^\da-z]/i', ',', $userInfo->title));
					$titleStr = "";
					for ($i = 0; $i < sizeof($tmp); $i++){
						if($titleStr != "") $titleStr .= " OR ";
						$titleStr .=
					}
				}*/
				else{
					if(isset($userInfo->$result[$i]['ldap_key']))
						$addToType .= "km.member = '".$ldap_key."'";
					else if ($result[$i]['type'] == "Employee Number" && isset($userInfo->employeenumber) )
						$addToType .= "km.member = '". $userInfo->employeenumber ."'";
				}


				if($typeStr != "" && $addToType != ""){
					$typeStr .= ") OR (";	
					$typeStr .= "kt.type = '".$result[$i]['type']."' AND " . $addToType;
				} 

			}

		return $typeStr;
	}
	private function loadKeywords() {
		global $fluid;
		$db = $fluid->components['DB'];
				
		$arrParams = array();
		$arrParams['user'] = $fluid->components['LDAP']->user;
		$siteID = $fluid->site->info->id;
		$query = "select DISTINCT k.group_name, k.id as groupID from groups k, groups_members km, groups_types kt
							where  k.site_id in(1,$siteID) and km.group_id = k.id and km.type_id = kt.id AND ((" . $this->createTypeString() .")) or km.member = :user";

			
		$result = $db->select($query,$arrParams);
		foreach( $result as $obj ){
			array_push($this->keys, $obj['group_name'] );
			array_push($this->arrGroupIDs,$obj['groupID']);
		}

		$query  = "select s.admin, g.id from sites s, groups g where s.id = :siteID and s.admin = :user and g.group_name = :default_admin ";
		$arrParams = array(":siteID"=>$siteID,":user"=>$fluid->components['LDAP']->user,":default_admin"=>"default_admin");
		$result = $db->select($query,$arrParams);
		if(count($result)){
			array_push($this->keys,'default_admin');
			array_push($this->arrGroupIDs,$result[0]['id']);
		}

		// ensure we do not have duplicates
		array_unique($this->keys);
		array_unique($this->arrGroupIDs);

		//$this->keyStr = '"'.implode('","',$this->keys).'"';
		$this->keyStr = $fluid->arrayToQuotedString($this->keys);
	}

	public function reloadPermissions($keys=null){
		global $fluid;
		// used for adding in supplemental dynamic keys after permissions have been loaded
		// $keys can be either a single key, a commatext list of items, or an actual array
		// these are supplemental keys to add in addition to the ones pulled from the database and are used for dynamic enteries
		if( gettype($keys) == 'string' )
			$keys = $fluid->StringListToArray($keys);
		if( !empty($keys) && gettype($keys)=='array' ) {
			$db = $fluid->components['DB'];
			$siteID = $fluid->site->info->id;
			$keysList = $fluid->arrayToQuotedString($keys);



			$query = "select DISTINCT k.group_name, k.id as groupID 
					from fluid.groups k
					left join fluid.groups_members km on km.group_id = k.id  
					left join fluid.groups_types kt on  km.type_id = kt.id 
					where k.site_id in(1,5) and k.group_name in($keysList);";

			$result = $db->select($query);

			

			foreach( $result as $obj ){
				array_push($this->keys, $obj['group_name'] );
				array_push($this->arrGroupIDs,$obj['groupID']);
			}
		}

	

		// ensure we do not have duplicates
		array_unique($this->keys);
		array_unique($this->arrGroupIDs);
		$this->keyStr = $fluid->arrayToQuotedString($this->keys);

		$this->loadPermissions();		
	}

	private function loadPermissions() {
		global $fluid;
		$db = $fluid->components['DB'];
		$keywords = $this->keys;
		$keywordStr = "";

		//generate keyword string for db query
		if( count($keywords) && count($keywords) > 0){
			$keywordStr = "r.group_name like '%". implode("%' OR r.group_name LIKE '%",$keywords) . "%' and";


		//get site id: change once site info pulled from db
		$result = $db->select("select id from sites where folder = :sitename",array("sitename"=>$fluid->site->name));
		$siteID = count($result)?$result[0]['id']:1;

		$result = $db->select("select p.section_id, p.access, s.section from fluid.groups as r
			left join fluid.groups_access m on m.group_id = r.id
			left join fluid.access p on p.id = m.access_id
			left join fluid.sections s on s.id = p.section_id
			where $keywordStr s.site_id in (1,$siteID)");

			for ($i = 0; $i < count($result); $i++){
				$this->permissions[$result[$i]['section']][$result[$i]['access']] = true;
			}
		}
	}
	public function hasPermission($section,$type){
		return( isset($this->permissions[$section]) && isset($this->permissions[$section][$type]) );
	}
	public function getOverrides($pageID,$table,$col,$allow="Allow"){
		global $fluid;
		$db = $fluid->components['DB'];

		//check if there is an allow override
		$result = $db->select("select id from fluid.$table where $col = $pageID and access = :access",array("access"=>$allow));
		if(count($result) ) $this->hasAllow = true;

		if(empty($this->arrGroupIDs)) return array();

		$groupIDs = implode(",",$this->arrGroupIDs);

		$result = $db->select("select access from $table where $col = $pageID and group_id in ($groupIDs)");
		$arrOverrides = array();
		for($i = 0; $i < count($result); $i++){
			array_push($arrOverrides,$result[$i]['access']);
		}
		$arrOverrides = array_unique($arrOverrides);
		return $arrOverrides;
	}
	public function requirePermission($section,$type,$redirectUrl=null){
		if( isset($this->permissions[$section]) && isset($this->permissions[$section][$type]) )
			return;
		if( $redirectUrl ) {
			header('Location: ' . $redirectUrl, true, 302);
			die();
		}
		else
			$this->accessDenied();
	}
	public function accessDenied() {
		global $fluid;
		$fluid->loadErrorPage(403,'You are not authorized to view this page');
	}
	public function savePermissions( $toAdd=array(), $toDelete=array() ){
		global $fluid;
		$db = $fluid->components['DB'];
		if( !isset($toAdd) ) $toAdd = array();
		if( !isset($toDelete) ) $toDelete = array();
		$changed = array();

		foreach($toAdd as $item) {
			$tmp = explode("_",$item);
			$updateItems = array(
				"access_id" => $tmp[0],
				"group_id" => $tmp[1]
			);
			$permissionID = $db->upsert("groups_access", $updateItems );
			if($permissionID > 0)
				$changed[$item] = $permissionID;
		}

		foreach($toDelete as $item=>$id){
			$db->delete("groups_access",array("id"=>$id));
			$changed[$item] = 0;
		}

    return $changed;
	}

	public function saveSection($params,$pTypes,$newTypes,$descriptions){
		global $fluid;
		$db = $fluid->components['DB'];

		$siteID = $fluid->site->info->id;

		//update section
	    if(!isset($params->section_id)){
			$arrParams = array("section"=>$params->section,"site_id"=>$siteID);
			$sectionID = $db->upsert("sections", $arrParams);
		}
	    else{
	        $db->upsert("sections", array("section"=>$params->section,"id"=>$params->section_id),array("section","section_id"));
	        $sectionID = $params->section_id;
	    }

	    if(isset($params->delete_pTypes) && $params->delete_pTypes != "")	$this->deleteUserSelected($params->delete_pTypes,"access");		//delete user deleted permission types
			$arrPermissionTypes = $this->pTypeArrayUpdate($pTypes,$newTypes,$descriptions,$params,$sectionID,$params->delete_pTypes);																//update permission types
	    $arrPermissions = array("id"=>$sectionID,"name"=>$params->section,"permission_types"=>$arrPermissionTypes);

			return $arrPermissions;
	}
	private function deleteUserSelected($deleteStr,$table){
		global $fluid;
		$db = $fluid->components['DB'];

		$arrDelete = explode("|",$deleteStr);
		for ($i = 0; $i < sizeof($arrDelete); $i++){
			$arrParams = array("id"=>$arrDelete[$i]);
			$db->delete($table,$arrParams);
		}
	}
	private function pTypeArrayUpdate($pTypes,$newTypes,$descriptions,$params,$sectionID,$deleteStr){
		global $fluid;
		$db = $fluid->components['DB'];
		$arrPermissionTypes = array();
		$siteID = $fluid->site->info->id;
		$delete_pTypes = explode("|",$deleteStr);

		if($pTypes != null){
			foreach($pTypes as $pTypeID=>$pType){
				$desc 	 = isset($descriptions->$pTypeID) ? $descriptions->$pTypeID : "";
				$pTypeID = str_replace("pType_","",$pTypeID);
				if(!in_array($pTypeID,$delete_pTypes)){
						$arrParams = array("access"=>$pType, "section_id"=>$sectionID, "id"=>$pTypeID,"description"=>$desc);
						$db->upsert("access",$arrParams,array('access','section_id','id','description'));


						$arrPermissionTypes["permission_".$pTypeID] = array("id"=>$pTypeID,"name"=>$pType,"roles"=>array(),"description"=>$desc);
				}
			}
		}
		foreach($newTypes as $newID=>$newType){
				$desc = isset($descriptions->$newID) ? $descriptions->$newID : "";
				$arrParams = array("access"=>$newType, "section_id"=>$sectionID,'description'=>$desc);
				$pTypeID = $db->upsert("access",$arrParams,array('access','section_id','description'));
				$arrPermissionTypes["permission_".$pTypeID] = array("id"=>$pTypeID,"name"=>$newType,"roles"=>array(),"description"=>$desc);
		}
		return $arrPermissionTypes;
	}
	public function addPermissionType($sectionID,$pType,$section){
		global $fluid;
		$db = $fluid->components['DB'];
		$siteID = $fluid->site->info->id;

    return $db->upsert("access",array("section_id"=>$sectionID,"access"=>$pType,"site_id"=>$siteID),array("section_id","access","site_id"));
	}
	public function saveKeyword($selKeywords,$keyword,$description,$arrMemberID,$deleteMembers){
		global $fluid;
		$db 	= $fluid->components['DB'];
		$user = $fluid->components['LDAP']->user;
		$arrMembers = array();

		if($selKeywords == "") $keywordID = $db->upsert("groups",array("group_name"=>$keyword,"description"=>$description,"site_id"=>$fluid->site->info->id),array("group_name","description","site_id"));
    else{
			$keywordID = $selKeywords;
			$db->upsert("groups",array("group_name"=>$keyword,"id"=>$keywordID,"description"=>$description,"site_id"=>$fluid->site->info->id),array("group_name","id","description","site_id"));
		}

    $arrKeyword = array("id"=>$keywordID,"keyword"=>$keyword,"description"=>$description,"members"=>array());

		if($arrMemberID != 0){
			for ($i = 0; $i < sizeof($arrMemberID); $i++){
	      $arrParams = array("group_id"=>$keywordID,"type_id"=>$_POST['type'][$i],"member"=>$_POST['value'][$i],"modified_by"=>$user);
	      if($arrMemberID[$i] > 0) $arrParams["id"] = $arrMemberID[$i];
	      $arrKeys   = array_keys($arrParams);
	      $tmpID = $db->upsert("groups_members",$arrParams,$arrKeys);
	      if($arrMemberID[$i] == 0) $arrMemberID[$i] = $tmpID;
	      $arrMembers["member_".$arrMemberID[$i]] = array("id"=>$arrMemberID[$i],
	                                               "modified_by"=>$user,
	                                               "type_id"=>$_POST['type'][$i],
	                                               "value" =>$_POST['value'][$i]);
	    }
		}

    $arrKeyword['members'] = $arrMembers;

    //delete deleted values

    if($deleteMembers != null) {
			$this->deleteUserSelected($deleteMembers,"groups_members");
			$arrDelete = explode("|",$deleteMembers);
			for ($i = 0; $i < sizeof($arrDelete); $i++) unset($arrKeyword['members']['member_'.$arrDelete[$i]]);
		}

		return $arrKeyword;
	}
	public function deleteKeyword($keywordID,$keyword){
		global $fluid;
		$db 	= $fluid->components['DB'];
		$db->delete("groups",array("id"=>$keywordID));
	}

	/*get json code*/
	public function getPermissionsJSON(){
		global $fluid;
		$db = $fluid->components['DB'];

		//check if current site is in exclusive show for any sections
		$siteID = $fluid->site->info->id;
		$result = $db->select("select section_id from sections_restrictions where restriction='Show' and site_id != $siteID");
		$restrictStr = "";
		if(count($result)){
			$arrRestrict = array();
			for ($i = 0; $i < count($result); $i++)
					array_push($arrRestrict,$result[$i]['section_id']);
			if(count($arrRestrict)) $restrictStr = " and s.id not in ('" . implode(",",$arrRestrict) ."')";
		}

		$sections = $db->select("
			select a.id as access_id, a.access as name, a.description,
				s.id as section_id, s.section,
				t.id as site_id
			from fluid.access as a
				left join fluid.sections as s on s.id = a.section_id
				left join fluid.sites as t on t.id = s.site_id
				left join fluid.sections_restrictions as r on r.section_id = s.id and r.site_id = t.id
			where t.id in(1, :siteID)
				$restrictStr
				and (r.restriction is null or r.restriction != 'hide')
			order by t.id, section, access",array(":siteID"=>$fluid->site->info->id));

		$roles = $db->select("
			select g.id, g.site_id, g.group_name as role, g.description
			from fluid.groups as g
			where g.site_id = :siteID",array(":siteID"=>$fluid->site->info->id));

		$accessMap = $db->select("
			select ga.access_id, ga.id as group_access_id, ga.group_id
			from fluid.groups_access as ga
			left join fluid.groups as g on g.id = ga.group_id
			where g.site_id = :siteID",array(":siteID"=>$fluid->site->info->id));

		$arrPermissions = array("site"=>$fluid->SITENAME);
		$arrPermissions["roles"] = $roles;
		$arrPermissions['permissions'] = $sections;
		$arrPermissions["accessMap"] = $accessMap;

		return json_encode($arrPermissions);
	}

	public function getTypeJSON(){
		global $fluid;
		$db = $fluid->components['DB'];

		$result = $db->select("select * from groups_types");
		return json_encode($result);
	}
	public function getGroupJSON(){
		global $fluid;
		$db = $fluid->components['DB'];

	  $result = $db->select("select g.id, g.group_name, g.site_id, ifnull(g.description,'') as description,kt.type,km.id as member_id,km.member,km.modified_by,km.modified,km.type_id,s.admin from groups g
													 left join groups_members km on km.group_id = g.id
													 left join groups_types kt on kt.id = km.type_id
													 left join sites s on s.id = g.site_id
													 where g.site_id = :siteID  order by group_name",array(":siteID"=>$fluid->site->info->id));
	  $arrKeywords = array();
	  for ($i = 0; $i < sizeof($result); $i++){
	    if(!isset($arrKeywords['keyword_'.$result[$i]['id']])){
	      $arr = array(
	        "id"      => $result[$i]['id'],
	        "keyword" => $result[$i]['group_name'],
					"description" =>$result[$i]['description'],
	        "members" =>array()
	      );
	      $arrKeywords['keyword_'.$result[$i]['id']] = $arr;
	    }
			if(isset($result[$i]['member_id'])){
				$arr = array(
					"id"          => $result[$i]['member_id'],
					"type_id"     => $result[$i]['type_id'],
					"value"       => $result[$i]['member'],
					"modified"    =>$result[$i]['modified'],
					"modified_by" => $result[$i]['modified_by']
				);
				$arrKeywords['keyword_'.$result[$i]['id']]['members']['member_'.$result[$i]['member_id']] = $arr;
			}
	  }
		return json_encode($arrKeywords);
	}
	public function getSectionsJSON(){
		global $fluid;
		$db = $fluid->components['DB'];

		$result = $db->select("select sp.id,sp.access,s.section,s.id as section_id,sp.description from sections s
		left join access sp on s.id = sp.section_id where s.site_id =".$fluid->site->info->id);
		$sections = $pTypes = array();
		for ($i = 0; $i < sizeof($result); $i++){
		  if(!isset($sections["section_".$result[$i]['section_id']]))
		    $sections["section_".$result[$i]['section_id']] = array("id"=>$result[$i]['section_id'],"name"=>$result[$i]['section'],"permission_types"=>array());
		  if(is_null($result[$i]['access']))   $sections["section_".$result[$i]['section_id']]['permission_types'] = array();
		  else
		    $sections["section_".$result[$i]['section_id']]['permission_types']['permission_'.$result[$i]['id']] = array("id"=>$result[$i]['id'],"name"=>$result[$i]['access'],"description"=>$result[$i]['description']);
		}
		return json_encode($sections);
	}

	//called by menu to get menu permissions for overrides
	public function getMenuAccess($menuSection="menu",$sites=array()){
		global $fluid;
		$db = $fluid->components['DB'];

		$arrPermissions = array();

		if( gettype($sites) == 'array' && !count($sites) )
			$sites = $fluid->site->info->sites_for_menu;
		if( gettype($sites) == 'string' )
		$sites = $fluid->StringListToArray($sites); // convert comma string lists to array

		if( !count($sites) )
			array_push($sites,$fluid->site->info->id); // add in the current web site
		array_push($sites,1); // add in global menu items

		$siteIDs = implode(",",$sites);

		$result = $db->select("select a.id, a.access, a.description from access a, sections s where a.section_id = s.id and s.site_id in ($siteIDs) and s.section = 'menu'");

		for ($i = 0; $i < count($result); $i++){
			$arrPermissions[$result[$i]['access']] = array("id"=>$result[$i]['id'], "description"=>$result[$i]['description']);
		}
		return json_encode($arrPermissions);
	}
	public function prepareRestrictions(){
		$this->arrTables 			= array("basic"=>"list_basic","basic_restrictions"=>"list_basic_overrides","menu"=>"menu","menu_restrictions"=>"menu_restrictions");
		$this->restrictCols   = array("basic"=>"page_id","basic_restrictions"=>"access","menu"=>"menu_id","menu_restrictions"=>"restriction");															//to update easily
		$this->arrAccess 			= array("basic" => array("Allow","Deny","Edit"), "menu" => array("Show","Hide"));		//to update easily
	}
	public function addRestrictItem($itemTypeContent,$updateTypeFields,$restrictTypeFields,$type){
		global $fluid;
		$db =  $fluid->components['DB'];

		$itemID = $db->upsert($this->arrTables[$type],$itemTypeContent,$updateTypeFields);
		if($restrictTypeFields['restriction'] != "") {
			$this->addRestrictions($itemID,$restrictTypeFields['restrict_groups'],$restrictTypeFields['restriction'],$type);
			if($type == "basic"){
				if (isset($restrictTypeFields['edit_groups']))
					$this->addRestrictions($itemID,$restrictTypeFields['edit_groups'],"Edit",$type);
			}
		}
		return $itemID;
	}
	public function editRestrictItem($itemTypeContent,$updateTypeFields,$restrictTypeFields,$type){
		global $fluid;
		$db =  $fluid->components['DB'];
		$db->upsert($this->arrTables[$type],$itemTypeContent,$updateTypeFields);
		$this->updateRestrictions($restrictTypeFields,$type,$itemTypeContent['id']);
	}
	public function updateRestrictions($restrictTypeFields,$type,$itemID){
		global $fluid;
		$db = $fluid->components['DB'];

		$origRestriction = $restrictTypeFields['orig_restriction'];
		$restriction		 = $restrictTypeFields['restriction'];

		//delete all restrictions if different restriction, else remove deleted and add new
		if($restrictTypeFields['orig_restriction'] != $restrictTypeFields['restriction'] || $restrictTypeFields['restriction'] == ""){
			if($restrictTypeFields['orig_restriction'] != ""){
				$db->exec("delete from ".$this->arrTables[$type."_restrictions"] ." where " . $this->restrictCols[$type] ."= $itemID and ".$this->restrictCols[$type."_restrictions"]."='$origRestriction'");
			}
		}
		else if(isset($restrictTypeFields['delete_groups']))
			$this->removeRestrictions($itemID,$restrictTypeFields['delete_groups'],$restriction,$type);

		if(isset($restrictTypeFields['restrict_groups']))
			$this->addRestrictions($itemID,$restrictTypeFields['restrict_groups'],$restriction,$type);

		//if basic, update for edit override
		if($type == "basic"){
			if(isset($restrictTypeFields['delete_edit']))
				$this->removeRestrictions($itemID,$restrictTypeFields['delete_edit'],"Edit",$type);
			if(isset($restrictTypeFields['edit_groups']))
				$this->addRestrictions($itemID,$restrictTypeFields['edit_groups'],"Edit",$type);
		}
	}
	/*public function populateArrAccessIDs($type,$restriction){
		global $fluid;
		$db   = $fluid->components['DB'];

		$arrParams = array("section"=>$this->section[$type],"site_id"=>$fluid->site->info->id);
		$result = $db->select("select a.id,a.access from access a,sections s where s.id = a.section_id and s.section = :section
													 and s.site_id in (1,:site_id)",$arrParams);
		for ($i = 0; $i < count($result); $i++){
			if(in_array($result[$i]['access'],$this->arrAccess[$type]))
				$this->arrAccessIDs[$type][$result[$i]['access']]['id'] =$result[$i]['id'];
		}
	}*/
	public function removeRestrictions($itemID,$restrictGroups,$restriction,$type){
		global $fluid;
		$db = $fluid->components['DB'];
		//$accessID = $this->arrAccessIDs[$type][ucFirst($restriction)]['id'];

		for ($i = 0; $i < count($restrictGroups); $i++){
			$arrParams = array(":item_id"=>$itemID,":group_id"=>$restrictGroups[$i],":restriction"=>$restriction);
			$db->exec("delete from ".$this->arrTables[$type."_restrictions"] ." where " . $this->restrictCols[$type] ."= :item_id and group_id = :group_id and ".$this->restrictCols[$type."_restrictions"]." =:restriction",$arrParams);
		}
	}
	public function addRestrictions($itemID,$restrictGroups,$restriction,$type){
		global $fluid;
		$db = $fluid->components['DB'];
		for ($i = 0; $i < count($restrictGroups); $i++){
			$arrParams 			 = array($this->restrictCols[$type]=>$itemID,"group_id"=>$restrictGroups[$i],$this->restrictCols[$type."_restrictions"]=>$restriction);
			$arrUpdateFields = array_keys($arrParams);
			$db->upsert($this->arrTables[$type."_restrictions"],$arrParams,$arrUpdateFields);
		}
	}
}
