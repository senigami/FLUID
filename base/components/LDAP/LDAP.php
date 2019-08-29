<?php
class LDAP {
	static $COMPONENT_PATH;
	private $host, $port, $rootDN, $peopleDN, $groupsDN, $connected, $connection, $arrParams; //ldap vars
	private $db, $groupStr; //mysql
	public  $user, $ldapJSON, $userInfo, $keywords;
	public $groups = array();
	private $requiredComponents = array('DB','Cache');

	function __construct(){
	  global $fluid;
	  // check to see that required components haver already loaded
	  foreach($this->requiredComponents as $component)
	    if( !isset($fluid->components[$component]) )
	      $fluid->loadErrorPage(500,"The $component component has not been loaded");

	  $this->host     = "qed-ldap.qualcomm.com";
	  $this->port     = 389;
	  $this->peopleDN = 'dc=qualcomm,dc=com ';
	  $this->groupsDN = 'ou=groups,dc=qualcomm,dc=com';

	  $this->db	 	= $fluid->components['DB'];

		if ($this->user == null){
			if( isset($_SERVER['HTTP_SM_USER']) && !empty($_SERVER['HTTP_SM_USER']) )
				$this->user = $_SERVER['HTTP_SM_USER'];
			elseif( isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER']) )
				$this->user = $_SERVER['REMOTE_USER'];
		}

	  $this->ldapJSON = $this->getCache($this->user);
		$this->userInfo = json_decode($this->ldapJSON);

		if( isset($this->userInfo->uid) )
			$fluid->userID = $this->userInfo->uid;
		else {
			$fluid->userID;
			$this->userInfo = json_decode('{
				"qcqbsid": "",
				"qcguid": "",
				"givenname": "",
				"qchiredate": "",
				"qcextension": "",
				"qccitizenship": "",
				"manager": "",
				"title": "",
				"uid": "anon",
				"qcnickname": [ "Anonymous", "User" ],
				"qcmailhost": "",
				"qcmaildomain": "",
				"qclookelsewhere": "",
				"employeestatus": "",
				"gecos": "Anonymous User",
				"homedirectory": "",
				"qchomedrive": "",
				"roomnumber": "",
				"uidnumber": "",
				"mail": "",
				"ou": "",
				"gidnumber": "",
				"fullname": "Anonymous",
				"workforceid": "",
				"givennamehr": "Anonymous",
				"fullnamehr": "Anonymous",
				"snhr": "Anon",
				"qcworkshift": "",
				"qcadcontext": "",
				"qcaddomain": "",
				"qcadassociation": "",
				"telephonenumber": "",
				"qcreceivedomain": ["",""],
				"departmentnumber": "",
				"employeetype": "",
				"knowledgeinformation": "",
				"qcaffiliation": "",
				"objectclass": [],
				"qcobjectguid": "",
				"qcbusinessunit": "",
				"cn": "Anon",
				"qcbusinessunitdesc": "",
				"sn": "Anon",
				"qcworkarea": "",
				"l": "",
				"qcadmailacct": "",
				"groupmembership": []
			}');
		}
	}

	//get userInfo
	public function getUserInfo($user=null){
	    if($user == null) $user = $this->user;
	  	$userInfo = $this->getCache($user);
	  	if($userInfo == false) return false;
	  	return json_decode($this->getCache($user));
	}

	private function connect(){
		$this->connection = ldap_connect($this->host, $this->port);
		$this->connected = ldap_bind($this->connection);
	}

	private function getQuestionableParams(){
		return array('userpassword','qcadtermservprofile','qcuniquealias','qcdefaulthomedirpath',
					 'loginshell','qcadhomefolder','qchomedirserver','qchomedirectory','qcadtermservhomefolder',
					 'sambaacctflags','sambapwdlastset','qcsudoflag','sambasid','sambaprimarygroupsid');
	}

	#set cache, if not found and is actual user.  return cache
	private function getCache($user){
	  global $fluid;

	  $cache = $fluid->components['Cache']->fetch("access_".$user);
	  if($cache == '{"error":"User not found"}') $cache = false;

	  if($cache == false){
	    $this->connect();
	    if($this->connected){
	      $result = ldap_search($this->connection, $this->peopleDN, "uid=".$user, array("*","groupMembership"));
	      if(isset($result)){
	        $entries = ldap_get_entries($this->connection, $result);
	        if (isset($entries[0])){
	          $this->getGroupList($entries[0]['groupmembership']);
	          $arrRemove = $this->getQuestionableParams();

	          for($i = 0; $i < count($arrRemove); $i++)
	            unset($entries[0][$arrRemove[$i]]);

	          $entries[0]["groupmembership"] = $this->getGroupArray($entries[0]['groupmembership']);
	          $d = preg_split("/ - /",  $entries[0]['departmentnumber'][0], 2);
	          $entries[0]['department'] = $d[0];
	          $entries[0]['departmentdesc'] = $d[1];
	          $entries[0]['matches'] = count($entries); // how many results matched the query

	          $cache = json_encode($this->cleanUpArray($entries[0]));	//JSON_PRETTY_PRINT-- use in later version			

	          if($user == $this->user){
	            $fluid->components['Cache']->create("access_".$user, $cache);
	            $this->getGroupList($entries[0]['groupmembership']);
	          }
	        }else{
	        	$cache = false;
	         	#$cache = json_encode(array("error"=>"User not found"));
	        }
						
	        $this->disconnectLDAP();
	      }
	    }
	  }
		
	  return $cache;
	}

	public function findDepartment($deptnum)
	{
		// make sure deptnum has the right number of digits
		$deptnum = str_pad($deptnum, 5, "0", STR_PAD_LEFT);

		//ldapsearch -LLLxh qed-ldap.qualcomm.com -b "dc=qualcomm,dc=com" 'departmentNumber=20627*' departmentNumber qcBusinessUnit qcBusinessUnitDesc

		global $fluid;
		$data = array();
		$this->connect();
		if ($this->connected) {
			$result = ldap_search($this->connection, 'dc=qualcomm,dc=com', 'departmentNumber='.$deptnum.'*');
			if (isset($result)){
				$entries = ldap_get_entries($this->connection, $result);
				if (isset($entries[0])){
					$arrRemove = $this->getQuestionableParams();
					for ($i = 0; $i < sizeof($arrRemove); $i++)
						unset($entries[0][$arrRemove[$i]]);
					$data = $this->cleanUpArray($entries[0]);
					$data['matches'] = count($entries);
				}
			}
			$this->disconnectLDAP();
		}
		if( !count($data) )
			return null;

		$d = preg_split("/ - /",  $data['departmentnumber'][0], 2);
		$dept = $d[0];
		$deptName = $d[1];

		return array('department' => $dept,
								 'departmentdesc' => $deptName,
								 'qcbusinessunit' => $data['qcbusinessunit'],
								 'qcbusinessunitdesc' => $data['qcbusinessunitdesc'],
								 'employees' => $data['matches']
								);
	}
	private function cleanUpArray($array){
		$newArr = array();
		foreach ($array as $arr => $val){

			 if (!is_numeric($arr)){
			 	if (isset($val['count']) && $val['count'] > 0){
					unset($val['count']);
				}

				if (sizeof($val) > 1) $newArr[$arr] = $val;
				else if (is_array($val)) $newArr[$arr] =  $val[0];
				else $newArr[$arr] =  $val;
			}
		}
		return $newArr;
	}

	private function getGroupList($groupArr){
		$this->groupStr = "";
		unset($groupArr['count']);

		foreach ($groupArr as $index => $grpStr){
			if (!strstr($grpStr,"ou=channels")){
				$grpStr = $this->getGroupSQLStr($grpStr);
				$this->groupStr .= ", " . $grpStr;
			}
		}
		$this->groupStr = substr($this->groupStr,1);
	}

	private function getGroupArray($groupArr){
		$arrGroups = array();
		foreach ($groupArr as $index => $grpStr){
			if (!strstr($grpStr,"ou=channels")){
				array_push($arrGroups,$this->getGroupValue($grpStr));
			}
		}
		return $arrGroups;
	}

	private function getGroupValue($string){
		$string = preg_replace("/(cn=)([A-Za-z\.=_0-9-+\s]*)(,ou=)([A-Za-z\.=,]+)/", "$2", $string);
		return $string;
	}

	private function getGroupSQLStr($string){
		$string = preg_replace("/(cn=)([A-Za-z\.=_0-9-+\s]*)(,ou=)([A-Za-z\.=,]+)/", "'$2'", $string);
		return $string;
	}

	private function disconnectLDAP(){
		ldap_unbind($this->connection);
	}

	public function getGroupMembers($value){
		global $fluid;
		$data = array();
		$this->connect();
		if ($this->connected) {
			$result = ldap_search($this->connection, 'dc=qualcomm,dc=com', "cn=".$value);
			if (isset($result)){
				$entries = ldap_get_entries($this->connection, $result);
				
				if (isset($entries[0])){

					if(!isset($entries[0]['member'])) return false;
					$members = $entries[0]["member"];
					for($i = 0; $i < $members['count']; $i++){
						$members[$i] = str_replace("uid=","",$members[$i]);
						$members[$i] = str_replace(",ou=people,dc=qualcomm,dc=com","",$members[$i]);
					}
					unset($members['count']);
					$this->disconnectLDAP();
					
					return $members;
				}
			}
			$this->disconnectLDAP();
			return array();
		}
		return array();
	}

	public function validateUser($user){
		global $fluid;
		$this->connect();
		if($this->connected){
			$result = ldap_search($this->connection, $this->peopleDN, "uid=".$user, array("uid"));
			if(isset($result)){
	        	$entries = ldap_get_entries($this->connection, $result);
	        	if(isset($entries[0]['uid'])) return true;
	        }
		}
		$this->disconnectLDAP();
		return false;
	}

	public function getUsers($value){
		global $fluid;
		$data = array();
		$this->connect();
		if ($this->connected) {
			$result = ldap_search($this->connection, $this->peopleDN, "uid=".$value."*", array("uid"),10);
			if (isset($result)){
				$entries = ldap_get_entries($this->connection, $result);
				
				return $entries;

				if (isset($entries[0])){

				

					if(!isset($entries[0]['member'])) return false;
					$members = $entries[0]["member"];
					for($i = 0; $i < $members['count']; $i++){
						$members[$i] = str_replace("uid=","",$members[$i]);
						$members[$i] = str_replace(",ou=people,dc=qualcomm,dc=com","",$members[$i]);
					}
					unset($members['count']);
					$this->disconnectLDAP();
					
					return $members;
				}
			}
			$this->disconnectLDAP();
			return array();
		}
		return array();
	}
}
