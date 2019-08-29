<?php
class Cache {
	static $COMPONENT_PATH;
	private $requiredComponents = array('DB');

	function __construct(){
		global $fluid;
		// check to see that required components haver already loaded
		foreach($this->requiredComponents as $component)
			if( !isset($fluid->components[$component]) )
				$fluid->loadErrorPage(500,"The $component component has not been loaded");
	}

	public function create($key='',$value='') {
		global $fluid;
		if( empty($key) )
			return false;

		$insertValues = array(
			"keyword" => $key,
			"value" => $value
		);

		$updateArray = array('value');
		$fluid->components['DB']->upsert('cache', $insertValues, $updateArray);
		return true;
	}

	public function fetch($key='') {
		global $fluid;
		if( empty($key) )
			return false;

		$query  = "SELECT value from cache WHERE keyword = :key";
		$params = array(":key"=>$key);

		$row = $fluid->components['DB']->select($query, $params);
		if (sizeof($row) > 0)
			return $row[0]['value'];
		return false;
	}
}
