<?php
class DB{
	static $COMPONENT_PATH;
	private $connections;																//FLUID's DB connection info
	private $arrCols, $arrPKs = array();
	public $dbh; 																				//PDO

	function __construct($params = null){
		global $fluid;
		require( $fluid->getPathFilePath('_config/settings.php') );
		$fluid->site->env = 'prod';
		foreach( $env as $m => $e )
			if( !empty($m) && strpos($fluid->site->domain,$m)>-1 ) {
				$fluid->site->env = $e;
				break;
			}
		$this->connections = $DB_config[ $fluid->site->env ];
		if($params != null){
			$this->connections = $params;
		}

	}

	private function connect(){
		global $fluid;
		try {

			if($fluid->site->env == "prod"){
				$this->dbh = new PDO("mysql:host=".$this->connections['host'].";port=".$this->connections['port'].";dbname=".$this->connections['db'], $this->connections['user'],$this->connections['pass']);
			}
			else{
				$this->dbh = new PDO("mysql:host=".$this->connections['host'].";port=".$this->connections['port'].";dbname=".$this->connections['db'],
				$this->connections['user'],
				$this->connections['pass'],
					array(
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_PERSISTENT => true
					)
				); 
			}
			

		}catch (PDOException $e){ echo $e->getMessage();}
	}

	private function execute($query, $arrParams = array(), $db = "fluid") {
		try{
			$stmt = $this->dbh->prepare($query);
			$stmt->execute($arrParams);



			return $stmt;
		}catch(PDOException $e){
			echo $e->getMessage();
		}
	}

	//for more custom queries
	public function execPublic($query,$arrParams=array(),$db="fluid"){
		// temporarily supporting legacy calls
		return $this->exec($query,$arrParams,$db);
	}

	//for functions requiring mysql variables
	public function execMultiple($setQuery,$query,$arrParams=array(),$db="fluid"){
		$this->connect();
		$stmt = $this->dbh->prepare($setQuery);
		$stmt->execute();

		$stmt2 = $this->dbh->prepare($query);
		$stmt2->execute();
		$this->close();

		return $stmt2->fetchAll(PDO::FETCH_ASSOC);
	}

	public function exec($query,$arrParams=array(),$db="fluid"){
		$result = '';
		$this->connect();
		try{
			$stmt = $this->dbh->prepare($query);
			if( !$stmt->execute($arrParams) ) {
				$result = $stmt->errorInfo();
				if( count($result) > 1 )
					$result = $result[2];
			}
		}catch(PDOException $e){
			$result = $e->getMessage();
		}
		$this->close();
		return $result;
	}

	public function select($query,$arrParams=array(),$db = "fluid"){
		$this->connect();
		$stmt = $this->execute($query, $arrParams,$db);
		$this->close();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function selectOBJ($query,$arrParams=array(),$db = "fluid"){
		$this->connect();
		$stmt = $this->execute($query, $arrParams);
		$this->close();
		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}

	public function upsert($dbTable, $insertArray, $updateFieldNames=null, $db = "fluid") {


		// table: full table path, i.e. fluid.keywords
		// insertArray: fieldname => value pairs of all the data needed for an insert
		// $updateFieldNames: an array list of just the field names to be used when doing an update
		$arrParams = array();
		$updateArray = array();
		if( !isset($updateFieldNames) )
			$updateFieldNames = array_keys($insertArray);

		$autoInc = $this->getAutoIncrementField(str_replace($db,"",$dbTable),$db); //get array of autoincremented fields- want to remove these in updateArray



		foreach($insertArray as $field=>$value){
			$arrParams[':'.$field] = $this->toString($value);
			if(in_array($field,$updateFieldNames) && $field != $autoInc)
				array_push($updateArray, "$field=:$field");
		}

		$insertKeys = implode(', ', array_keys($insertArray) );
		$placeholders = implode(', ', array_keys($arrParams) );


		//$updateStr = implode(', ', $updateArray );
		if(in_array("id",$updateArray))
			$updateStr = count($updateArray) ? " ON DUPLICATE KEY UPDATE " . implode(', ', $updateArray ).', id=LAST_INSERT_ID(id)' : "";
		else
			$updateStr = count($updateArray) ? " ON DUPLICATE KEY UPDATE " . implode(', ', $updateArray ) : "";

		$query = "INSERT into $db.$dbTable ($insertKeys) VALUES ($placeholders)
				$updateStr;";

				

		$this->connect();
		$this->execute($query, $arrParams);
		$id	= $this->dbh->lastInsertID();

		$this->close();
		return $id; // should return the actual id if exists
		// note: it is up to the programmer to then request the values from this update with a select
	}

	public function upsertPrint($dbTable, $insertArray, $updateFieldNames, $db = "fluid") {
		// table: full table path, i.e. fluid.keywords
		// insertArray: fieldname => value pairs of all the data needed for an insert
		// $updateFieldNames: an array list of just the field names to be used when doing an update
		$arrParams = array();
		$updateArray = array();

		$autoInc = $this->getAutoIncrementField(str_replace($db,"",$dbTable),$db); //get array of autoincremented fields- want to remove these in updateArray

		foreach($insertArray as $field=>$value){
			$arrParams[':'.$field] = $this->toString($value);
			if(in_array($field,$updateFieldNames) && $field != $autoInc)
				array_push($updateArray, "$field=:$field");
		}

		$insertKeys = implode(', ', array_keys($insertArray) );
		$placeholders = implode(', ', array_keys($arrParams) );
		
		if(in_array("id",$updateArray))
			$updateStr = count($updateArray) ? " ON DUPLICATE KEY UPDATE " . implode(', ', $updateArray ).', id=LAST_INSERT_ID(id)' : "";
		else
			$updateStr = count($updateArray) ? " ON DUPLICATE KEY UPDATE " . implode(', ', $updateArray ) : "";


	 $query = "INSERT into $db.$dbTable ($insertKeys) VALUES ($placeholders)
				 $updateStr;";

	}


	public function delete($table,$arrPKVals,$db='fluid'){
		$arrKeys		  = array_keys($arrPKVals);
		$arrCols 	 		= array_unique($arrKeys);
		$cols   	 		= implode(",",$arrCols);
		$placeholders = preg_replace('/(\w+)/',':${1}',$cols);
		$arrParams    = $this->generateExecArray($arrPKVals,$placeholders);
		$deleteStr		= $this->generateConditionStr($arrCols,"delete");
		$dbTable			= "$db.$table";
		$query				= "DELETE FROM $dbTable where $deleteStr";

		$this->connect();
		$stmt = $this->execute($query, $arrParams, $db);
		$this->close();
		return true;
	}

	/*******************FOR UPSERT FUNCTION******************/
	private function toString($value){ // converts non string values into DB format
		switch( gettype($value) ){
			case 'boolean':
				return $value?'1':'0';

			case 'array':
				return ','.implode(',',$value).',';

			case 'object':
				return json_encode($value);

			case 'integer':
			case 'double':
			case 'string':
				return $value;

			case 'resource':
			case 'NULL':
			case 'unknown type':
			default:
				break;
		}
		return '';
	}

	private function generateExecArray($arrParams,$placeholders){
		$tmp  = explode(",",preg_replace('/:(\w+)/',':${1}',$placeholders));
		$tmp2 = array_values($arrParams);
		$arr  = array_combine($tmp,$tmp2);
		return $arr;
	}

	private function generateConditionStr($arrCols,$type){
		$condStr = "";
		for ($i = 0; $i < sizeof($arrCols); $i++){
				if ($condStr != "") {
						if ($type == "update") $condStr .= ", ";
						else $condStr .= " AND "; //for delete field
				}
				if ($type == "update"){
					if (!in_array($arrCols[$i], $this->arrPKs)) $condStr .= $arrCols[$i] . "= :" . $arrCols[$i];
				}
				else
					$condStr .= $arrCols[$i] . "= :" . $arrCols[$i];
		}
		return $condStr;
	}

	//private function getLastRow($table,$arrPKVals,$db="fluid"){
	public function getLastRow($table,$arrPKVals,$db="fluid"){
		$pkStr = "";																	//where clause for query, containing primary key fields/values
		$arrParams = array();													//array for executing PDO query

		foreach($arrPKVals as $pk => $val){
			$arrParams[":".$pk] = $val; //prepend : to array keys
			$pkStr .= $pkStr == "" ? $pk . "= :".$pk : " AND " . $pk . "= :".$pk;
		}

		$query = "SELECT * from $table WHERE " . $pkStr;
		$this->connect();
		$stmt = $this->execute($query, $arrParams);
		$this->close();

		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}
	/****************END FOR UPSERT FUNCTIONS****************/

	/******************AUXILIARY FUNCTIONS*******************/
	public function getColumns($table, $db= "fluid"){
			$this->connect();
			$stmt = $this->execute("SHOW columns FROM $db.$table");

			$this->arrCols = $stmt->fetchAll(PDO::FETCH_OBJ);
			$this->close();
			return $this->arrCols;
	}

	public function getPrimaryKeys($table,$db="fluid"){
		if (sizeof($this->arrPKs == 0)){
			if (sizeof($this->arrCols) == 0) $this->getColumns($table,$db);
			for ($i = 0; $i < sizeof($this->arrCols); $i++){
				if ($this->arrCols[$i]->Key == "PRI") array_push($this->arrPKs, $this->arrCols[$i]->Field);
			}
		}
		return $this->arrPKs;
	}

	public function getTableNames($db="fluid"){
		$arrTables = array();
		$this->connect();
		$stmt = $this->execute("show tables in $db");
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
		for ($i = 0; $i < sizeof($row); $i++){
			array_push($arrTables,$row[$i]['Tables_in_'.$db]);
		}
		$this->close();
		return $arrTables;
	}

	public function getCompositePKVals($row,$arrPKVals){
		$key = "";
		for ($i = 0; $i < sizeof($arrPKVals); $i++){
			if ($key != "") $key .= "|";
			$key .= $row->$arrPKVals[$i];
		}
		return $key;
	}

	public function getAutoIncrementField($table,$db="fluid"){
		$arrCols = $this->getColumns($table,$db);
		for ($i = 0; $i < sizeof($arrCols); $i++){
			if($arrCols[$i]->Extra == "auto_increment"){
				return $arrCols[$i]->Field;
			}
		}
	}

	public function getPrefixTables($prefix,$db="fluid"){
		$this->connect();
		$stmt = $this->execute("show tables like '$prefix%'");
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$rowCount = count($row);
		$arrTables = array();
		for($i = 0; $i < $rowCount; $i++){
			array_push($arrTables,$row[$i]['Tables_in_'.$db.' ('.$prefix.'%)']);
		}

		$this->close();
		return $arrTables;
	}

	/****************END AUXILIARY FUNCTIONS****************/

	private function close(){
		$this->dbh = null;
	}

}
