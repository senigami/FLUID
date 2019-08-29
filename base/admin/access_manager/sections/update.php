<?php
global $fluid;
$db     = $fluid->components['DB'];
$access = $fluid->components['Access'];
$action = $_POST['action'];

switch($action){
  case "save_section":
    $params = isset($_POST['params']) ? json_decode($_POST['params']) : null;
    if($params != null) if($_POST['typeID'] > 0) $params->section_id = $_POST['typeID'];
    $pTypes       = isset($_POST['permissionTypes']) ? json_decode($_POST['permissionTypes']) : null;
    $newTypes     = isset($_POST['newTypes']) ? json_decode($_POST['newTypes']) : null;
    $descriptions = isset($_POST['descriptions']) ? json_decode($_POST['descriptions']) : null;
    echo json_encode($access->saveSection($params,$pTypes,$newTypes,$descriptions));
    break;
  case "delete_section":
    $sectionID = $_POST['typeID'];
    $db->delete("sections",array("id"=>$sectionID));
    break;
  default:
    break;
}

?>
