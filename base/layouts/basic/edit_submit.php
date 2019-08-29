<?php

//----- init Variables -----//
global $fluid;
$user = $fluid->components['LDAP']->user;
$updateFields = array();
$itemContent = array();

//----- GATHER DATA -----//
$action = content('action');
$displayMenu  = content('menuDisplay',0); // default for no value

$arrTypes = array("basic","menu");
for ($i = 0; $i < count($arrTypes); $i++){
	$updateFields[$arrTypes[$i]] 	 = array_merge(
																		array('modified_by'),							// default array values
																		content($arrTypes[$i].'_mod',array())			// values from post
																	);
	$itemContent[$arrTypes[$i]]	 	 = array_merge(
																		array('modified_by'=>$user), 			// default array values
																		content($arrTypes[$i].'_content',array())	// values from post
																	);
	$restrictFields[$arrTypes[$i]] = content($arrTypes[$i].'_restrict');
}

$response = array(
	"success" => false, // status of the procedure
	"url" => '', // where to redirect to when finished
	"status" => 'there was a problem saving' // message to display on completion
);

//----- LOGIC FLOW -----//
switch($action) {
	case 'add':
	case 'edit':
		addUpdatePage($displayMenu,$response,$updateFields,$itemContent,$restrictFields);
		break;
	case 'delete':
	  deleteItem( 'menu',       $itemContent['basic']['menu_id'] );
		deleteItem( 'list_basic', $itemContent['basic']['id'] );
		$response['success'] = true;
		$response['status'] = $itemContent['basic']['title'].' page has been deleted';
		$response['url'] = '/page/list';
	break;

	default:
		// bad action abort
}

echo json_encode($response);

//----- SPECIFIC TASKS -----//
function deleteItem($table, $id){ // delete menu or page from database
	global $fluid;
	$db   = $fluid->components['DB'];
	if( $id )
	  $db->delete( $table, array('id'=>$id) );
}
function content($theVar,$defaultValue=null) { // get submitted values
		return isset($_REQUEST[$theVar])?$_REQUEST[$theVar]:$defaultValue;
}
function addUpdatePage($displayMenu,&$response,&$updateFields,&$itemContent,$restrictFields){
	global $fluid;
	$db	    = $fluid->components['DB'];
	$access = $fluid->components['Access'];

	$access->prepareRestrictions();		//initialize access restriction/override variables- can be updated here

	/******ADD TO DB AND UPDATE RESTRICTIONS/OVERRIDES******/
	if(empty($itemContent['basic']['id'])){
		$itemContent['basic']['id'] = $access->addRestrictItem($itemContent['basic'],$updateFields['basic'],$restrictFields['basic'],"basic");
	}
	else{
		$access->editRestrictItem($itemContent['basic'],$updateFields['basic'],$restrictFields['basic'],"basic");
	}
	$pageID = $itemContent['basic']['id'];
	/******************************************************/

	$updateFields['basic'] = array(); // clear out updated fields

	$response['status'] = $itemContent['basic']['title'].' page has been updated';
	if( $pageID && empty($itemContent['basic']['id']) ) {
		$itemContent['basic']['id'] = $pageID;
		$response['status'] = $itemContent['basic']['title'].' page has been added';
	}

	// check for alias set response url
	$response['url'] = empty($itemContent['basic']['alias'])? '/page/'.$itemContent['basic']['id'] : '/'.$itemContent['basic']['alias'];

	if( $displayMenu ) {
		$itemContent['menu']['url'] = $response['url'];
		$updateFields['menu'][] = 'url';

		if(empty($itemContent['menu']['id']))
			$menuID = $access->addRestrictItem($itemContent['menu'],$updateFields['menu'],$restrictFields['menu'],"menu");
		else{
			$access->editRestrictItem($itemContent['menu'],$updateFields['menu'],$restrictFields['menu'],"menu");
			$menuID = $itemContent['menu']['id'];
		}

	 	$itemContent['basic']['menu_id'] = $menuID;
		$updateFields['basic'] = array('menu_id');
		$db->upsert("list_basic",$itemContent['basic'],$updateFields['basic']);
	}
	else {
		if( !empty($itemContent['menu']['id']) ) {
			// delete existing menu item
			deleteItem( 'menu', $itemContent['menu']['id'] );
			$itemContent['basic']['menu_id'] = '';
			$updateFields['basic'][] = 'menu_id';
			$db->upsert("list_basic",$itemContent['basic'],$updateFields['basic']);
		}
	}
	$response['success'] = true;
};
