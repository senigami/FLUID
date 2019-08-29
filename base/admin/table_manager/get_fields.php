<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $fluid;
$fluid->loadComponent('DB');
$db = $fluid->components['DB'];

$table = $_GET['table'];

$row = $db->selectOBJ("SELECT
       @rownum := @rownum + 1 AS jsonIndex,t.*
  FROM $table t,
       (SELECT @rownum := 0) r");

#get column names, types
$cols     = $db->getColumns($table);
$arrCols  = array();
for ($i = 0; $i < sizeof($cols); $i++){
  $arrCols[$i]['Field'] = $cols[$i]->Field;
  $arrCols[$i]['Type']  = $cols[$i]->Type;
  $arrCols[$i]['Null']  = $cols[$i]->Null;
  $arrCols[$i]['Key']   = $cols[$i]->Key;
  $arrCols[$i]['Default'] = $cols[$i]->Default . "";
  $arrCols[$i]['Extra'] = $cols[$i]->Extra . "";
}

$jsonCols = json_encode($arrCols);



$numPKs = 0;
$pk = "";
for ($i = 0; $i < sizeof($cols); $i++){
	if ($cols[$i]->Key == "PRI"){
		$numPKs++;
		$pk = $cols[$i]->Field;
	}
}

$pk = $db->getPrimaryKeys($table);

for ($i = 0; $i < sizeof($row); $i++){
	$rowNum = $i+1;
	$row[$i]->DT_RowId = "tr_".$rowNum;
  if ($numPKs != 1) $row[$i]->id   = $db->getCompositePKVals($row[$i],$pk);
  else $row[$i]->id = $row[$i]->$pk[0];
}

$fields = json_encode($row);
?>
