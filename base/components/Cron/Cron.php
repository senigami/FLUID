<?php

class Cron{
	static  $COMPONENT_PATH;
	private $requiredComponents = array('DB');
	protected $db,$user,$fluid;
	public $info = array();

	public $id = 1; //test ID
	private $table = "cron";
	private $currentTime;
	public $output = "";

	 function __construct(){
		global $fluid;
		if(!isset($_SESSION)) session_start();

		$this->fluid = &$fluid;
		$this->user  = $fluid->userID;
		$this->db    = $fluid->components['DB'];		
		$this->currentTime =  strtotime("now");
	}

	public function getInfo(){
		$result = $this->db->select("select * from fluid.cron where id = :id",array("id"=>$this->id), "fluid");
		if(count($result)){
			if($result[0]['scheduled'] == "0000-00-00 00:00:00") $result[0]['scheduled'] = "";
			$this->info = $result[0];
		}
		return $this->info;
	}

	public function getAllJobs(){
		$result =  $this->db->select("select * from fluid.cron",array(),"fluid");
		return $result;
	}


	public function getActiveCrons(){
		$nowDate = date("Y-m-d H:i:00",$this->currentTime); 
		$result  = $this->db->select("select id, url, params, frequency, last_ran, next_run, domain, email_contact from fluid.cron where next_run <= :next_run and frequency != ''",array("next_run"=>$nowDate), "fluid");



		if(count($result)) return $result;
		else return array();
	}

	public function loadIndex($domain){
		global $fluid;

		$fluid = new FLUID_CORE();
		$fluid->loadComponent("DB");


		#$this->output .= print_r($fluid->site,true);

		$result = $fluid->components['DB']->select("
						select s.*, d.domain, group_concat(sm.menu_site_id) as sites_for_menu from fluid.site_domains d
				left join fluid.sites as s on d.site_id = s.id
				left join fluid.sites_menus sm on sm.site_id = s.id
				where s.id = (select site_id from fluid.site_domains where domain = :DOMAIN)
				group by d.id", array(':DOMAIN'=>$fluid->site->domain ) );


	

		$fluid->site->info = (object)$result[0];
		$fluid->site->info->sites_for_menu .= ",".$fluid->site->info->id;
	
		$fluid->site->name = $fluid->SITENAME = $fluid->site->info->folder;
		$fluid->site->path = $fluid->ROOT.'sites/'. $fluid->SITENAME . '/';

		$fluid->loadComponents( array('Cache','LDAP','Access','replaceTags','Menu','pathMap','Breadcrumbs','blockText') );

		$fluid->userID = "qcae_apache";
		// run preprocess logic
		$fluid->loadPathFile("preprocess.php");

		// load the page
		$fluid->components['pathMap']->load();
	}

	public function run(){
		global $fluid;
		$crons = $this->getActiveCrons();
		

		for($i = 0; $i < count($crons); $i++){
		

			$job = $crons[$i];

			$this->fluid->cronOutput = "";

			$_SERVER['SERVER_NAME'] 	= $job['domain'];
			$_SERVER['HTTP_HOST'] 		= $job['domain'];
			$_SERVER['REQUEST_METHOD'] 	= "GET";
			$_SERVER['QUERY_STRING'] 	= $job['params'];
			$_SERVER['REMOTE_ADDR']		= "127.0.0.1";
			$_SERVER['REQUEST_URI'] 	= $job['url'];
			$_SERVER['REDIRECT_URL'] 	= $job['url'];

			

			$_GET = array();
			parse_str($job['params'],$_GET);
			
			$this->setNextRun($job['id'],$job['frequency'],$job['next_run']);

			OB_START();		
				$this->loadIndex($job['domain']);

			$result = OB_GET_CLEAN();
			
			if(!empty($fluid->cronOutput))
				$this->output .= $this->fluid->cronOutput . " SUCCESS\n"; //change this to print later
			else
				$this->output .= $this->fluid->cronOutput . " EMPTY\n"; //change this to print later

			#mail($job['email_contact'],"Cron Run", $job['url'] . "\n" . $this->output);
		}
	}

	private function setNextRun($id,$frequency,$setNextRun){
		#$this->output .= $this->id;

		#$nextRun   = date("Y-m-d H:i:00",strtotime("+".$frequency,$this->currentTime));
		$nextRun   = date("Y-m-d H:i:00",strtotime("+".$frequency,strtotime($setNextRun)));
		#$lastRun   = date("Y-m-d H:i:00",$this->currentTime);
		$lastRun   = $setNextRun;

		$arrUpdate = array("updated"=>$lastRun, "updated_by"=>$this->user, "changes"=>array("job run"));
		$updateJSON = $this->updateHistory($arrUpdate,$id);
		
		$insertArray = array("id"=>$id,"last_updated_by"=>$this->user, "last_ran"=>$lastRun,"next_run"=>$nextRun,"history"=>$updateJSON);		
	
		$this->db->upsert($this->table, $insertArray, array("last_updated_by","last_ran","next_run","history"), "fluid");
	}

	public function runNow($id){ //simulates DB update after a cron has been run

		//get frequency and last run of id
		$result = $this->db->select("select frequency from fluid.cron where id = :id",array("id"=>$id), "fluid");
		
		//update to next run
		$frequency = $result[0]['frequency'];
		$this->setNextRun($id,$frequency);
		$this->output .= "CRON RUN\n";
		#echo "<br />CRON RUN<br />";
	}

	public function getHistoryInfo($id){
		$result = $this->db->select("select title, description, history from fluid.cron where id = :id", array("id"=>$id), "fluid");
		if(count($result)) return $result[0];
		else return array();
	}

	public function updateHistory($arrUpdate,$id){ //update with error message, if error message results
		$history = $this->getHistoryInfo($id);

		if($history['history'] == "" || $history['history'] == null) $updateJSON = json_encode(array($arrUpdate));
		else{
			$history = json_decode($history['history'],true);	
			array_unshift($history,array($arrUpdate));
			$updateJSON = json_encode($history);	
		} 

		#$this->output .= "HISTORY UPDATED\n";
		return $updateJSON;
	}

	public function updateProperties($arrUpdate=array(),$id){
		$dateFields = array("scheduled","next_run");
		if(isset($arrUpdate['scheduled'])){
			$arrUpdate['scheduled'] = $this->updateDateFormat($arrUpdate['scheduled']);
			$arrUpdate['next_run']  =  $this->updateDateFormat(strtotime("+".$arrUpdate['frequency'],strtotime($arrUpdate['scheduled'])));
		}
		else{
			$arrUpdate['next_run']  = "";
		}

		$arrUpdate  		  = array("updated"=>updateDateStrFormat($this->currentTime), "updated_by"=>$this->user, "changes"=> $arrUpdate);
		$arrUpdate['history'] = $this->updateHistory($arrUpdate,$id);

		$updateFieldNames 	  = array_keys($arrUpdate);
		$this->db->upsert("cron", $arrUpdate, $updateFieldNames, "fluid");	

	}

	private function updateDateFormat($date){
		$newDate = date("Y-m-d H:i:00",strtotime($date));
		return $newDate;
	}

	private function updateDateStrFormat($dateStr){
		$newDate = date("Y-m-d H:i:00",$dateStr);
		return $newDate;
	}
}
