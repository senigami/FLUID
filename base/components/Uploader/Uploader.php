<?php
class Uploader{
  static $COMPONENT_PATH;
  public $fileObj2 = array('uploader'=>array(),'default'=>array());
  public $path;
  public $arrExtensions;

  //initialize uploader
  function __construct($path=""){
    global $fluid;
  }

  //set path if different from default
  public function setPath($path=""){
    if($path == "") $this->path = "sites/".$fluid->site->name."/files/";
    else $this->path = $path;
  }

  //make new directory if it doesn't exist
  public function makeNewPath($mkpath){
    mkdir($mkpath);
  }

  //default file upload function, validates path and calls $this->uploadFile
  public function updateFiles($name,$tmpName,$path=""){
    if($path == "") $path = $this->path;
    if($this->validatePath($path) == true) $this->path = $path;
    else return false;

    $this->fileObj2['default'][$name] = array("name"=>$name,"tmp_name"=>$tmpName);
    return $this->uploadFile($name);
  }

  //initialize uploader object, if files uploaded with Uploader
  public function setUploaderObj($prefixes,$path=""){
    if($path != "") $this->path = $path;

    for($i = 0; $i < count($prefixes); $i++)
      $this->fileObj2['uploader'][$prefixes[$i]] = array();
  }

  //get valid extensions for file uploads
  public function getExtensions($ext){
    $arrExtensions = array();
    $tmp = explode(",",$ext);
    for($i = 0; $i < count($tmp); $i++){
      array_push($arrExtensions,$tmp[$i]);
    }
    return $arrExtensions;
  }

  //consolidate uploader files
  public function consolidateUploaderFiles($fileIndex,$file,$prefix,$ext=""){
    $arrExtensions = $this->getExtensions($ext);
    $uploader = &$this->fileObj2['uploader'][$prefix];

    if($file['name'][0] != ""){

  		for ($i = 0; $i < count($file['name']); $i++){

        if($ext != ""){
          if(in_array($file['type'][$i],$arrExtensions)){
      			$arrTmp = array("name"=>$file['name'][$i],"tmp_name"=>$file['tmp_name'][$i]);
      			array_push($uploader,$arrTmp);
    		  }

        }
        else{
          $arrTmp = array("name"=>$file['name'][$i],"tmp_name"=>$file['tmp_name'][$i]);
          array_push($uploader,$arrTmp);
        }
      }
    }
  }

  //this needs to be rewritten.  using uploadUploaderFile for uploader files.
  //upload file. if path directory does not exist, create it in the path
  public function uploadFile($fileIndex,$type="default",$prefix="",$replace=false){
    global $fluid;

    if($type == "default"){
      $name    = $fileIndex;
      $tmpName = $this->fileObj2['default'][$fileIndex]['tmp_name'];
      $path    = $this->path;
    }
    else{

      $name 	 = $this->fileObj2['uploader'][$prefix][$fileIndex]['name'];
      $tmpName = $this->fileObj2['uploader'][$prefix][$fileIndex]['tmp_name'];
      $path = $this->path.$prefix."/";
    }

    if($replace == false) $filePath = $this->updatePath($name,$path);
    else                  $filePath = $path . $name;


    move_uploaded_file($tmpName, $filePath);
    return $filePath;
  }

  public function uploadUploaderFile($upFile,$fileIndex,$prefix,$replace=false){
    $name    = $upFile['name'][$fileIndex];

    $tmpName = $upFile['tmp_name'][$fileIndex];
    $path   = $this->path.$prefix."/";

    if($replace == false) $filePath = $this->updatePath($name,$path);
    else                  $filePath = $path . $name;


    move_uploaded_file($tmpName, $filePath);
    return $filePath;
  }

  //validate path- make sure it's not empty and it is in site directory
  private function validatePath($path){
    global $fluid;
    $siteDir = "sites/".$fluid->site->name."/";

    if(empty($path)) return false;
    if(!strstr($path,$siteDir)) return false;

    return true;
  }

  //delete file from path
  public function deleteFile($path){
    if($this->validatePath($path) == true)
      unlink($path);
  }

  //make sure path is in site directory
  public function deleteDir($path) {
    if($this->validatePath($path) == true){
      return is_file($path) ?
          @unlink($path) :
          array_map(array($this,'deleteDir'), glob($path.'/*')) == @rmdir($path);
    }
    else{
      echo "error";
      return false;
    }
  }

  //update path
  public function updatePath($name,$path){
    $file = $name;
    $lastDot = strrpos($file, '.');
    $base = substr($file, 0, $lastDot);
    $ext = substr($file, $lastDot);

    $filePath = $path . $file;

    for( $seq = 0; file_exists($filePath); $seq++ ) {
      if( file_exists($filePath))	$filePath = $path.$base . '_'. $seq. $ext;
      $name = $base . '_'. $seq. $ext;
    }

    return $filePath;
  }

}
