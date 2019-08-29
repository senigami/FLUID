<?php
	chdir("/prj/qcae_web/FLUID/prod/");
	$_SERVER = array(
		"SERVER_NAME"=>"fluid.qualcomm.com",
		"REQUEST_METHOD"=>"GET",
		"QUERY_STRING" => "",
		 "REMOTE_ADDR" => "127.0.0.1",
		 "REQUEST_URI" => "/stub", //stub page, nothing, used to launch fluid 
		  "REDIRECT_URL" => "/stub" //stub page, nothing, used to launch fluid
		);
	
	require_once ('base/core.php');
	$fluidCron = new FLUID_CORE();
	$fluidCron->loadComponents( array('DB','Cron') );
	$crons = $fluidCron->components['Cron']->run(); //json output
	
	
//move this into $crons->run
	foreach($crons=>$job){
		$_SERVER = array(
		"SERVER_NAME"=>$job['domain'],
		"REQUEST_METHOD"=>"GET",
		"QUERY_STRING" => $job['params'],
		 "REMOTE_ADDR" => "127.0.0.1",
		 "REQUEST_URI" => $job['url'],
		  "REDIRECT_URL" => $job['url']
		);

		$fluidCron->components['Cron']->setNextRun($job['id'],$job['interval']);

		OB_START();
			require('index.php');
		$result = OB_GET_CLEAN();

		if( $result ){
			
			print $fluid->cronOutput;
		}
		else print "error";

		unset($fluid); //unsets iterated $fluid instance

	}
	