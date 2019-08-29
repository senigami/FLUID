<?php
$db = $fluid->components['DB'];
$keyword   = $_GET['keyword'];
$keywordID = $_GET['keywordID'];

$result = $db->select("select group_name from groups where group_name=:group and id != :id",array("group"=>$keyword,"id"=>$keywordID));

if (sizeof($result) > 0) echo "Group already exists";
else echo "";

?>
