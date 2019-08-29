<?php
global $fluid;
//$fluid->loadComponent('DB');
$menu   = $fluid->components['Menu'];
$db     = $fluid->components['DB'];
$action = $_POST['action'];
$user   = $fluid->components['LDAP']->user;
$params = array();

switch($action){
  case "add":
    $params = $_POST['params'];
    $restrict     = isset($_POST['restrict']) ? $_POST['restrict'] : "";
    $restrictType = isset($_POST['restrictType']) ? $_POST['restrictType'] : "";

    unset($params['restrictType']);
    $params['modified_by'] = $user;
    $menu->addMenuItem($params,$restrict,$restrictType);
    break;
  case "edit": //add to come later
    $params   = $_POST['params'];
    $restrictType = $params['restrictType'];
    unset($params['restrictType']);

    $restrict         = isset($_POST['restrict']) ? $_POST['restrict'] : "";
    $origRestrict     = isset($_POST['origRestrict']) ? $_POST['origRestrict'] : "";
    $origRestrictType = isset($_POST['origRestrictType']) ? $_POST['origRestrictType'] : "";

    $arrUpdate = isset($_POST['arrUpdate']) ? $_POST['arrUpdate'] : array();
    $arrUpdate['user']  = $user;
    $menu->editMenuItem($params,$arrUpdate,$restrict,$restrictType,$origRestrict,$origRestrictType);
    break;
  case "delete":
    $id = $_POST['id'];
    $db->delete("menu",array("id"=>$id));
    $menu->updateChildren($id);
    break;
  default:
    break;
}
