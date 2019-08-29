<?php
	$Menu = $fluid->components['Menu'];
	$Menu->load();
	//print_r($Menu->flatList());exit;

	$Menu->Template = array(
		'link' 		=> '<li class="nav-top [ACTIVE]"><a data-toggle="tooltip" class="[ACTIVE]" href="[URL]" target="[TARGET]" title="[HELPTEXT]">[LABEL]</a></li>',
		'divider' => '<li class="divider" role="separator"></li>',
		'header' 	=> '<li class="nav-top dropdown-header bg-primary" title="[HELPTEXT]">[LABEL]</li>',
		'disabled'=> '<li class="disabled"><a class="disabled" href="#" title="[HELPTEXT]">[LABEL]</a></li>'
	);

	$Menu->Template['topSub'] = <<< EOT
		<li class="dropdown">
			<a data-toggle="tooltip" href="[URL]" target="[TARGET]" title="[HELPTEXT]" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">[LABEL] <span class="caret"></span></a>
			<ul class="dropdown-menu">
EOT;

	$Menu->Template['listSub'] = <<< EOT
		<li><a data-toggle="tooltip" href="[URL]" target="[TARGET]" title="[HELPTEXT]">[LABEL] <span class="caret"></span></a>
			<ul class="dropdown-menu">
EOT;

	function makeMenu($menuObject=null, $level=0) {
		global $fluid;
		$menuTemplate = $fluid->components['Menu']->Template;
		
		if( !isset($menuObject) )
			$menuObject = $fluid->components['Menu']->tree;
		
		$output = '';
		
		foreach($menuObject as $obj) {
			$item = '';
			$type = (isset($obj['submenu']) && count($obj['submenu']))? '[SUBMENU]' : $obj['url'];

			switch( $type ) {
				case '': // no link
					$item = $menuTemplate['header'];
					$item = str_replace('[LABEL]',$obj['label'],$item);
					$item = str_replace('[HELPTEXT]',$obj['helptext'],$item);
					break;
			
				case '[---]': // seperator
					$item = $menuTemplate['divider'];
					break;

				case '[DISABLED]': // disabled
					$item = $menuTemplate['disabled'];
					$item = str_replace('[LABEL]',$obj['label'],$item);
					$item = str_replace('[HELPTEXT]',$obj['helptext'],$item);
					break;

				case '[SUBMENU]': // has a sub menu
					$item = $level?$menuTemplate['listSub']:$menuTemplate['topSub']; // different template for top item
					$item = str_replace('[URL]',$obj['url'],$item);
					$item = str_replace('[TARGET]',$obj['target'],$item);
					$item = str_replace('[HELPTEXT]',$obj['helptext'],$item);
					$item = str_replace('[LABEL]',$obj['label'],$item);

						//repeat process for sub menu items and close tags
						$item .= makeMenu($obj['submenu'],$level+1) . '</ul></li>';
					break;

				default: // standard link

					$item = $menuTemplate['link'];
					$active = ('$currentpage'==$obj['url'])?'active':'';
					
					$item = str_replace('[ACTIVE]',$active,$item);
					$item = str_replace('[URL]',$obj['url'],$item);
					$item = str_replace('[TARGET]',$obj['target'],$item);
					$item = str_replace('[HELPTEXT]',$obj['helptext'],$item);
					$item = str_replace('[LABEL]',$obj['label'],$item);
			}
			$output .= $item."\n";
		}		
		return $output;
	}
?>
<div class="global-header">
	<div class="pull-left">
		<a class="qual-logo pull-left" target="_blank" href="http://qualnet.qualcomm.com">Qualnet</a>
		<span class="l-h28">Welcome</span>
	</div>
	<ul class="pull-right qual-link">
		<li class="people-ico"><a href="http://people.qualcomm.com" target="_blank">People</a></li>
		<li class="search-ico"><a href="http://search.qualcomm.com" target="_blank">Qualnet Search</a></li>
	</ul>
</div>

<header id="header" class="container-fluid">
		<a class="logo" href="http://qcaeweb.qualcomm.com">
			<img src="/files/header_logo.png" height=52 title="QCAE: Qualcomm Computer Aided Engineering">
		</a>
		<span class="welcome" style="float: right;">Welcome <?= $fluid->components['LDAP']->userInfo->gecos; ?></span>
</header>

<nav class="navbar" data-spy="affix" data-offset-top="100">
	<div class="menu ui-state-active ui-widget">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
<!--	<a class="navbar-brand" href="/" style="padding: 4px 8px;">
				<img src="/base/files/fluid_logo.png" alt="FLUID Logo" style="display: inline-block; height: 32px;">
			</a>
-->
		</div>
		
		<!-- navbar -->
		<div id="navbar" class="navbar-collapse collapse">
			<!-- Left nav -->
			<ul class="nav navbar-nav">
				<?= $fluid->components['replaceTags']->fromText( makeMenu() ) ?>
			</ul>
			<!-- Right nav -->
			<ul class="nav navbar-nav navbar-right">
				
			</ul>
		</div>
		<!--/.nav-collapse -->

	</div>
</nav>
