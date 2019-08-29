<?php
global $fluid;
$db		  = $fluid->components['DB'];

$siteID  = $_GET['siteID'];
$alias = $_GET['alias'];

$result = $db->select("select id from list_basic where site_id = :site_id and alias = :alias", array(":site_id"=>$siteID,":alias"=>$alias), "fluid");

if(sizeof($result) > 0)  echo "true";
else echo "false";
?>
