<?php
//header('Content-Type: text/plain; charset=utf-8');
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

// initialize FLUID core functions, allows additional components to load
	session_start();
	require_once ('base/core.php');
	$fluid = new FLUID_CORE();

/* load core components, order is important for dependant components
	DB 					// allows calls to the database
	Cache 			// quick access to cache items
	LDAP 				// requires DB: gets current user information
	Keywords		// requires LDAP: not yet implemented, manages access
	Menu 				// not yet implemented, gets object of menu items
	dynamicURL 	// requires DB: allows path aliasing
*/
	$fluid->loadComponent( 'DB' );



// load site info from the database and all other possible domain names
	if( isset($_SESSION["xsiteInfo"]) ) {
		$fluid->site->info = (object)$_SESSION["siteInfo"];
	} else {
		//$result = $fluid->components['DB']->select("
		//	select s.*,sd.domain
		//	from fluid.site_domains as sd,
		//		(select site_id from fluid.site_domains where domain = :DOMAIN) as id
		//	left join fluid.sites as s on s.id = id.site_id
		//	where sd.site_id = id.site_id", array(':DOMAIN'=>$fluid->site->domain ) );

		$result = $fluid->components['DB']->select("
		select s.*, d.domain, group_concat(sm.menu_site_id) as sites_for_menu from site_domains d
left join sites as s on d.site_id = s.id
left join sites_menus sm on sm.site_id = s.id
where s.id = (select site_id from site_domains where domain = :DOMAIN)
group by d.id", array(':DOMAIN'=>$fluid->site->domain ) );


			if( !count($result) )
				$fluid->loadErrorPage(500,'The URL has not been registered with FLUID.<br>
															please contact <a href="mailto:qcae.sysadmin@qualcomm.com?subject=Domain Not Registered&body=I would like to add the following site to FLUID: '.$fluid->site->domain.'">qcae.sysadmin</a>');

			$fluid->site->info = (object)$result[0];
			$fluid->site->info->sites_for_menu .= ",".$fluid->site->info->id;
		$_SESSION["siteInfo"] = $fluid->site->info;
	}
	$fluid->site->name = $fluid->SITENAME = $fluid->site->info->folder;
	$fluid->site->path = $fluid->ROOT.'sites/'. $fluid->SITENAME . '/';






	$fluid->loadComponents( array('Cache','LDAP','Access','replaceTags','Menu','pathMap','Breadcrumbs','blockText') );

// run preprocess logic
	$fluid->loadPathFile("preprocess.php");

// load the page
	$fluid->components['pathMap']->load();
