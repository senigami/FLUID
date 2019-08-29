<?php
global $fluid;
$fluid->loadComponent('DB');

$db         = $fluid->components['DB'];
$action     = $_POST['action'];
$table      = $_POST['table'];
$arrPKVals  = $_POST['arrPKVals'];

switch ($action){
  case "add":
  case "edit":
    $id = $db->upsert($table, $_POST['params'], $_POST['arrUpdate'], $arrPKVals);

    if ($id > 0){
      $pk   = $db->getPrimaryKeys($table);
      $arrPKVals 	= array($pk[0] => $id);
    }
    echo json_encode($db->getLastRow($table,$arrPKVals));
    break;
  case "delete":
    $db->delete($table, $arrPKVals);
    break;
  default:
    break;
}
?>
