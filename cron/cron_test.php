<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

	chdir("/prj/qcae_web/FLUID/dev/");



//TODO: update individual variables

	
	$_SERVER['SERVER_NAME'] = "fluid-dev.qualcomm.com";
	$_SERVER['HTTP_HOST'] = "fluid-dev.qualcomm.com";
	$_SERVER['REQUEST_METHOD'] = "GET";
	$_SERVER['QUERY_STRING'] = "";
	$_SERVER['REMOTE_ADDR'] = "127.0.0.1";
	$_SERVER['REQUEST_URI'] ="/"; //stub page, nothing, used to launch fluid 
	$_SERVER['REDIRECT_URL'] ="/"; //stub page, nothing, used to launch fluid 
	$_SERVER['HTTP_SM_USER'] = "qcae_apache";
	$_SERVER['PHP_AUTH'] = "qcae_apache";
	$_SERVER['USER'] = "qcae_apache";

	require_once ('base/core.php');
	
	$fluid = new FLUID_CORE();

	$fluid->loadComponents( array('DB','Cron') );
	#$fluid->loadComponents( array('Cache','LDAP','Access','replaceTags','Menu','pathMap','Breadcrumbs','blockText') );
	#fluid->loadComponents( array('Cache','LDAP') );
	$cron = $fluid->components['Cron'];
	$cron->run();
	print $cron->output;
	#print "END \n";