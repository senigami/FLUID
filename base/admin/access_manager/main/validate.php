<?php
global $fluid;
$db = $fluid->components['DB'];
$site = $fluid->SITENAME;

$param = $_GET['param'];
$value = $_GET['value'];
$id    = $_GET['id'];

switch($param){
  case "role":
    $query = "select name from roles where lower(name)=lower(:role) and site=:site and id != :id";
    
    $result = $db->select($query,array($param=>$value,"site"=>$site,"id"=>$id));
    if(sizeof($result) > 0) echo "Role already exists.";
    break;
  case "section":
    $query = "select section from sections where lower(section)=lower(:section) and site=:site and id != :id";
    $result = $db->select($query,array($param=>$value,"site"=>$site,"id"=>$id));
    if(sizeof($result) > 0) echo "Section already exists.";
    break;
  /*case "pType":
    $sectionID = $_GET['sectionID'];
    $query = "select id from site_permission where name = :pType and site=:site and section_id = :section_id";
    $result = $db->select($query,array($param=>$value,"site"=>$site,"section_id"=>$sectionID));
    if(sizeof($result) > 0) echo "Permission Type already exists for this section";
    break;*/
}
?>
