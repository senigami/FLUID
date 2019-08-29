<?php
//url is block/[block]/[action]/[id]
$page = &$fluid->page;
$page->footer_bottom .= '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>';
$blockText = $fluid->components['blockText'];

$ldap = $fluid->components['LDAP'];

//break out url
$pathParts = explode('/', $fluid->site->url );
$action    = $pathParts[2];
if(isset($pathParts[3])) $titleID   = $pathParts[3];
$fluid->page->vars['action'] = $action;

//determine action
switch( $action ) {
  case "add":
    $blockText->add();
   break;
	case 'edit':
    $blockText->edit($titleID);
		break;
  case 'delete':
    $titleID = $_POST['id'];
    $title   = $_POST['title'];
    $blockText->delete($titleID,$title);
    break;
	case 'save':
    //extract save data from post
    $data = $_POST;
    $user = $ldap->user;
    echo $blockText->save($data,$user); //print out redirect url
    break;

	case 'list': require_once('list.php'); exit;
  //else if list
    //get block data by id ($blockText->loadAll()), encode into json
    //include list.php*/

  default:
    break;
}
