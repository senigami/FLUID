<?php
$db     = $fluid->components['DB'];
$user   = $fluid->components['LDAP']->user;
$access = $fluid->components['Access'];
$action = $_POST['action'];


switch($action){
  case "add":
  case "edit":
    $arrMemberID   = isset($_POST['memberID']) ? $_POST['memberID'] : array();
    $keyword       = $_POST['keyword'];
    $deleteMembers = $_POST['delete_members'] == "" ? null : $_POST['delete_members'];
    echo json_encode($access->saveKeyword($_POST['keyword_id'],$keyword,$_POST['description'],$arrMemberID,$deleteMembers));
    break;
  case "delete":
    $keywordID = $_POST['keyword_id'];
    $keyword   = $_POST['keyword'];
    $access->deleteKeyword($keywordID,$keyword);
    break;
  default:
    break;
}
?>
