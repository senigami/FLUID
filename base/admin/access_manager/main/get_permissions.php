<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $fluid;
$db = $fluid->components['DB'];

$result = $db->select("select a.id as access_id,a.access,a.description as access_description,ga.id as map_id,g.id as group_id, g.group_name, g.site_id as group_site,g.description as group_description,si.folder as site,s.id as section_id,s.section,s.site as section_site from access a
 left join groups_access ga on ga.access_id = a.id
 left join groups g on g.id = ga.group_id
 left join sections s on  a.section_id = s.id
 left join sites si on si.id = g.site_id
 where s.site_id = 4 OR s.site_id=1
UNION
select a.id as access_id,a.access,a.description as access_description,ga.id as map_id, g.id as group_id, g.group_name, g.site_id as group_site,g.description as group_description,si.folder as site, s.id as section_id,s.section,s.site as section_site from groups g
 left join groups_access ga on ga.group_id = g.id
 left join access a on a.id = ga.access_id
 left join sections s on  a.section_id = s.id
 left join sites si on si.id = g.site_id
 where g.site_id = 4 OR g.site_id = 1
order by section_site,section,access asc
");

$arrPermissions = array("site"=>$fluid->SITENAME,"roles"=>array(),"map_id"=>array(),"permissions"=>array());
for ($i = 0; $i < sizeof($result); $i++){
  if(!is_null($result[$i]['group_name'])){

    $arrPermissions["roles"][$result[$i]['group_id']] = array("role"=>$result[$i]['group_name'],
                                                             "description"=>$result[$i]['group_description'],
                                                             "site"=>$result[$i]['site']);
  }
  if(!is_null($result[$i]['map_id']))
    $arrPermissions["map_id"][$result[$i]['section_id']."_".$result[$i]['access_id']."_".$result[$i]['group_id']] = $result[$i]['map_id'];
  if(!isset($arrPermissions['permissions']["section_".$result[$i]['section_id']]) && !is_null($result[$i]['section_id']))
    $arrPermissions['permissions']["section_".$result[$i]['section_id']] = array("name"=>$result[$i]['section'],
                                                                                "site"=>$result[$i]['section_site'],
                                                                                "id"=>$result[$i]['section_id'],
                                                                                "permission_types"=> array());
  if(!is_null($result[$i]['section'])){
    if(!isset($arrPermissions['permissions']["section_".$result[$i]['section_id']]['permission_types']))
      $arrPermissions['permissions']["section_".$result[$i]['section_id']]['permission_types'] = array();
    if(!isset($arrPermissions['permissions']["section_".$result[$i]['section_id']]['permission_types']["permission_".$result[$i]['access_id']]))
      $arrPermissions['permissions']["section_".$result[$i]['section_id']]['permission_types']["permission_".$result[$i]['access_id']] = array('name'=>$result[$i]['access'],'id'=>$result[$i]['access_id'],"roles"=>array(),'description'=>$result[$i]['access_description']);
    array_push($arrPermissions['permissions']["section_".$result[$i]['section_id']]['permission_types']["permission_".$result[$i]['access_id']]['roles'],$result[$i]['group_id']);
  }
}

if ($arrPermissions['map_id'] == array()) $arrPermissions['map_id'] = (object)array();

?>
