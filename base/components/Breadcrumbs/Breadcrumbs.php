<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Breadcrumbs{
  static  $COMPONENT_PATH;
  private $requiredComponents = array('DB','replaceTags');
  private $db;                                    //&$fluid->components['DB']
  private $menuTree;                              //$menu->flatList()
  public $bc;
  public $show = true;

  //set fluid variables
  //get $breadcrumb from session
  function __construct(){
    global $fluid;
		// check to see that required components haver already loaded
		foreach($this->requiredComponents as $component)
			if( !isset($fluid->components[$component]) )
				$fluid->loadErrorPage(500,"The $component component has not been loaded");

    $this->db = $fluid->components['DB'];
    $m = $fluid->components['Menu'];
    $m->load();
    $this->menuTree = $m->flatList();

    if(!isset($_SESSION["breadcrumbs"])) $_SESSION['breadcrumbs'] = array();
    $this->bc = &$_SESSION['breadcrumbs'];
    
  }

  private function getParams($url){
    return parse_str( parse_url($url,PHP_URL_QUERY), $params); 
  }

  //$breadcrumbs = array( array("url"=>url, "title"=>title), array("url"=>url, "title"=>title) )
  public function set($breadcrumbs=array()){
    global $fluid;

    $url   = $fluid->site->REQUEST_URI;
    #$url   = $fluid->site->url;
    $title = $fluid->page->title;
    $tmp     = explode("?",$url);
    $baseURL = $tmp[0];
    $params  = isset($tmp[1]) ? $this->getParams($url) : "";

    //check if override ($breadcrumbs) exists- if so, set breadcrumbs to this
    if(count($breadcrumbs) > 0) {
    	$this->bc = $breadcrumbs;
    }//else check if in breadcrumbs array
    else{
      //if in breadcrumbs array, remove everything after it
      $inBC = "false";
      $count = count($this->bc);

      for ($i = 0; $i < $count; $i++){
        if(!isset($this->bc[$i]['baseURL'])){
          //$this->bc[$i]['baseURL'] = $this->getBaseURL($this->bc[$i]);
          $this->bc[$i]['baseURL'] = $this->splitURL($i,$this->bc[$i]['url']); //set base and params
        }
        if($this->bc[$i]['baseURL'] == $baseURL  ){
          $inBC = "true";
          $tmp = array_slice($this->bc,0,$i+1,true);
         
          $this->bc = $tmp;
          break;
        }
      }

      //if not, check if the page link is in menu ($this->getMenuIndex($fluid->page->url))
      if($inBC === "false"){

        $menuIndex = $this->getMenuIndex($url,$baseURL);

        if($menuIndex != ""){
            //reset breadcrumbs array
            $this->bc = array();

            $this->getParentPaths($menuIndex);

           /* if($this->getParentPaths($menuIndex) == false){
            	
            	return;
            } */           
        }
       	
        //add current url and title to the array
        $basicID = isset($fluid->page->basic['id']) ? $fluid->page->basic['id'] : "";
        array_push($this->bc,array("url"=>$url, "baseURL"=>$baseURL, "title"=>$title, "id"=>$basicID, "params"=>$params));
        

      }
    }
  }

public function updateTitleID($title=null,$id=null){
  global $fluid;
  if($title == null) $title = $fluid->page->title;
  if($id == null){
    $id = isset($fluid->page->basic['id']) ? $fluid->page->basic['id'] : "";
  }

  $lastIndex = count($this->bc) - 1;
  if($lastIndex >= 0)
  $this->bc[$lastIndex]['title'] = $title;
  $this->bc[$lastIndex]['id'] = $id;

  if(count($this->bc) == 1 ){ //get home
    if($this->bc[0]['url'] != "/index.php"){
        $this->addHomeItem();
    }
  }
}

private function splitURL($i,$url){
  $tmp     = explode("?",$url);
  $this->bc[$i]['baseURL'] = $tmp[0];
  $this->bc[$i]['params']  = isset($tmp[1]) ? $tmp[1] : "";
}

private function addHomeItem(){
  global $fluid;
  $arrParams = array("site_id"=>$fluid->site->info->id);

  $result = $this->db->select("select m.label, m.url, m.id, b.id as basic_id from menu m, list_basic b where m.site_id = :site_id and b.menu_id = m.id and url = '/index.php';", $arrParams);

  if(count($result)){
    $arr = array("url"=>$result[0]['url'], "title"=>$result[0]['label'], "id"=>$result[0]['basic_id']);
    array_unshift($this->bc,$arr);
  }

}

 public function getMenuIndex($url){
    //iterate through flatlist until find menu.
    foreach ($this->menuTree as $m => $item){
      if(isset($item['url'])){
        //return array index name (label);
        
        if ($item['url'] == $url || $item['url'] == $url) return $item['id']; //id is the same as the index name

      }
    }
    //if not found, return "";
    return "";
  }

  public function getInfo($url){
    for($i = 0; $i < count($this->bc); $i++){
      if($this->bc[$i]['url'] == $url)
            return $this->bc[$i];
    }
  }

  public function getParentPaths($menuIndex){

    if($this->menuTree[$menuIndex]['parent'] != "menu_0"){
      $parentID = $this->menuTree[$menuIndex]['parent'];

      if(!isset($this->menuTree[$parentID])){
        return false;
      }

      $url   = $this->menuTree[$parentID]['url'];
      $title = $this->menuTree[$parentID]['label'];
      $basicID = $this->menuTree[$parentID]['basic_id'];
      $tmp     = explode("?",$url);
      $baseURL = $tmp[0];
      $params  = isset($tmp[1]) ? $this->getParams($url) : "";


      if($basicID != null)
        array_unshift($this->bc,array("url"=>$url,"baseURL"=> $baseURL, "title"=>$title,"id"=>$basicID, "params"=>$params));
      else
        array_unshift($this->bc,array("url"=>$url,"baseURL"=> $baseURL,"title"=>$title,"id"=>$parentID, "params"=>$params));
      //array_unshift($this->bc,array("url"=>$url,"title"=>$title));
      
      $this->getParentPaths($parentID);
    }
  }


public function display(){
    global $fluid;

    if($this->show == true){
      //$url   = $fluid->site->REQUEST_URI;
      $url = $fluid->site->url;
      $title = $fluid->page->title;
			$result = '';
      $tmp     = explode("?",$url);
      $baseURL = $tmp[0];
      
      //print out breadcrumb, last one (current) will not be link
      $result .= '<ol class="breadcrumb">';
      $result .= '<li><a href="/">Home</a></li>';


      for($i = 0; $i < count($this->bc)-1; $i++){
        if($this->bc[$i]['title'] == "No Title") continue;
        else if($this->bc[$i]['url'] == "" || $this->bc[$i]['url'] == "#")
          $result .= '<li class="active">'.$this->bc[$i]['title'].'</li>';
        else if($this->bc[$i]['title'] != "Home") //TODO: update this quick fix to something more robust
          $result .= '<li><a href="'.$this->bc[$i]['url'].'">'.$this->bc[$i]['title'].'</a></li>';
      }


      //if($url != "index.php")
      if($title != "Home" )
        $result .= '<li class="active">'.$title.'</li>';

      $result .= '</ol>';

			
			echo $fluid->components['replaceTags']->fromText( $result );
    }
  }

  public function getPrevPage(){
    //get second to last item of breadcrumbs array
    return $this->bc[count($this->bc)-2];
  }
}
