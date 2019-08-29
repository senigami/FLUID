<?php
class blockText{
  static $COMPONENT_PATH;
  private $requiredComponents = array("Breadcrumbs");
  private $db;
  public $data;

  function __construct(){
    global $fluid;

    $this->db = $fluid->components['DB'];
    // check to see that required components haver already loaded
    foreach($this->requiredComponents as $component)
      if( !isset($fluid->components[$component]) )
        $fluid->loadErrorPage(500,"The $component component has not been loaded");
  }

  private function validateData(){
    //validate saved data
    if($this->data['title'] == "") return "No title entered";
    return true;
    //return error or success message ("")
  }

  public function save($data,$user){
    global $fluid;
    $bc = $fluid->components['Breadcrumbs'];

    if($data['id'] == "") unset($data['id']);
    $data['modified_by'] = $user;

    if(!isset($data['show_title'])) $data['show_title'] = 0;
    else $data['show_title'] = 1;

    $arrUpdate = array_keys($data);
    if(isset($data['id'])){
      if(($key = array_search('id', $arrUpdate)) !== false) {
        unset($arrUpdate[$key]);
      }
    }

    $this->data = $data;
    $message = $this->validateData();

    if($message === true){
      $this->db->upsert("block_text",$data,$arrUpdate);
      $_SESSION['block'] = $data['title'] . ' has been saved';

      if(count($bc->bc) < 2) $url = "index.php";
      else $url = $bc->bc[count($bc->bc)-2]['url'];
    }
    else{
      $_SESSION['block'] = $message;
      $url = $bc->bc[count($bc->bc)-1]['url'];
    }

    return $url;
  }

  public function add(){
    global $fluid;
    $this->getOutput("_edit");
    $fluid->loadPathFile("layouts/blocks/text_edit.php");
  }

  public function edit($titleID){
    global $fluid;
    $this->data = $this->load($titleID);
    $this->getOutput("_edit");
	  $fluid->loadPathFile("layouts/blocks/text_edit.php");
  }

  public function show($titleID){
    global $fluid;
    $this->data = $this->load($titleID);
    $this->getOutput();
  }

  public function load($titleID){

    //get data from db, save as $this->data
    $result = $this->db->select("select * from block_text where id=:title_id or title = :title_id",array(":title_id"=>$titleID));
   // $result[0];
    
    return $result[0];
  }

  public function exists($titleID){
    //get data from db, save as $this->data

    $result = $this->db->select("select count(id) as count from block_text where id=:title_id or title = :title_id",array(":title_id"=>$titleID));
  
    return $result[0]['count'] * 1 > 0 ? true : false;
  }

  /*not sure if this is needed, since we would be querying multiple types of blocks in the future?
  function getList(){
    //get all block items for current site
    //$this->data = $result
  }*/

  public function delete($titleID,$title){
    global $fluid;
    $bc = $fluid->components['Breadcrumbs'];
    if( $titleID ){
      $arrParams = array(":title_id"=>$titleID);
      $this->db->delete("block_text",array("id"=>$titleID));

      $_SESSION['block'] = "The block <i>".$title."</i> has been deleted.";
      if(count($bc) < 2) echo "block/list";
      else echo  $bc->bc[count($bc->bc)-2]['url'];
    }
    else{
      $_SESSION['block'] = "There was an error in deleting your block.";
      echo $bc->bc[count($bc->bc)-1]['url'];
    }

  }

  private function getOutput($type="item"){
    global $fluid;
    $fluid->page->vars['block'] = $this->data;
    //load template file (blocks/text_$type.php) $fluid->getPageOutput($this->data) function
    //else return null
    if($fluid->loadPathFile("layouts/blocks/text_$type.php") == false) return null;
  }

}
