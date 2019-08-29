<?php
/* WAYS THE BASIC PAGE COULD BE CALLED
	/alias
	/page/alias
	/page/# <- id
	/page/add
	/page/edit/#
	/page/save/#
*/

	$page = $fluid->page;
	$fluid->components['Breadcrumbs']->set();

	$page->header_bottom .= "
		<link href='/layouts/basic/basic.css' type ='text/css' rel='stylesheet' />";

	require_once('preprocess.php');

// check permissions for viewing retired or published
	if( !$fluid->page->loadSuccess )
		return;

	$basic = $page->basic;
	$fluid->components['Breadcrumbs']->updateTitleID();

	if( !$basic['publish'] || $basic['retire'] ) {
		$type = $basic['retire']?'archived':'unpublished';
		print '<style>
			body { background: linear-gradient(to bottom, rgba(255,255,255,0) 10%,rgba(255,255,255,0.8) 25%), url("/files/'.$type.'.png") }
		</style>';
	}
?>
<?php

if( content('system_message') ): ?>
	<div role="alert" class="alert alert-success alert-dismissible fade in">
		<button aria-label="Close" data-dismiss="alert" class="close" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
		<strong><?= content('system_message'); ?></strong>
	</div>
<?php endif;
?>

<div class="container-fluid" role="main">
<div class="main_content">
	<?php
	switch( $page->action ) {
		case 'add':
		case 'edit':
		case 'save':
			include('add-edit.php');
			break;
		case 'load':	default:
			
			/*echo '<pre>';
			print_r($fluid->components['Breadcrumbs']->bc);
			echo '</pre>';*/

			if(($Access->hasPermission("Basic Page","Deny") == false && !in_array("Deny",$arrOverrides)) || ($Access->hasPermission("Basic Page","Allow") || in_array("Allow",$arrOverrides)) && $page->action == 'load' ){
				
				if($fluid->site->url == "index.php") $fluid->components['Breadcrumbs']->show = false;


			if($fluid->components['Breadcrumbs']->show == true)
				$fluid->components['Breadcrumbs']->display();

				// edit buttons to show up only when in normal view mode
				if(($Access->hasPermission("Basic Page","Edit") || in_array("Edit",$arrOverrides)) && $page->action == 'load' ){
					echo '<div class="edit_area">';
					echo '<div id="action_btns" style="text-align: right; padding-bottom: 5px">
									<a href="/page/edit/'.$basic['id'].'" class="btn btn-success edit pageBtn" data-toggle="tooltip" title="Edit Page"><span class="glyphicon glyphicon-pencil"></span></a>
								</div>';
					echo $basic['content'] . '</div>';
					echo '</div>';
				}
				else
					echo $basic['content'] . '</div>';
			}
	}
	?>
</div>
</div>
</div>

<?php
	$fluid->loadFooter();
