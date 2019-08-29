<?php
// environment mapping based on domain matching
	$env = array(
		'' => 'prod',
		'-local' => 'local',
		'-dev' => 'dev',
		'-tst' => 'test',
		'-stg' => 'stage'
	);

// configurations for components will be mapped to a variable of the same name
	$DB_config = array(
		'Xlocal'  => array(
			'host' => '127.0.0.1',
			'port' => '3306',
			'db' => 'fluid',
			'user' => 'root',
			'pass' => 'root'
		),
		'intern'    => array(
			'host' => 'qcae-db-dev',
			'port' => '3307',
			'db' => 'fluid',
			'user' => 'qcaephp',
			'pass' => 'qcaeqcae'
		),
		'local'    => array(
			'host' => 'qcae-db-dev',
			'port' => '3307',
			'db' => 'fluid',
			'user' => 'qcaephp',
			'pass' => 'qcaeqcae'
		),
		'dev'    => array(
			'host' => 'qcae-db-dev',
			'port' => '3307',
			'db' => 'fluid',
			'user' => 'qcaephp',
			'pass' => 'qcaeqcae'
		),
		'test'    => array(
			'host' => 'qcae-db-tst',
			'port' => '3307',
			'db' => 'fluid',
			'user' => 'qcaephp',
			'pass' => 'qcaeqcae'
		),
		'stage'    => array(
			'host' => 'qcae-db',
			'port' => '3307',
			'db' => 'fluid',
			'user' => 'qcaephp',
			'pass' => 'qcaeqcae'
		),
		'prod'        => array(
			'host' => 'qcae-db',
			'port' => '3307',
			'db' => 'fluid',
			'user' => 'qcaephp',
			'pass' => 'qcaeqcae'
		)
	);
