<?php
global $fluid;
$access = $fluid->components['Access'];

$toAdd   = isset($_POST['toAdd']) ? $_POST['toAdd'] : array();
$tDelete = isset($_POST['toDelete']) ? $_POST['toDelete'] : array();

echo json_encode($access->savePermissions($toAdd,$tDelete));
