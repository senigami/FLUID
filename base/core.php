<?php
class FLUID_CORE {
	public $ROOT = '';
	public $SITENAME = '';
	public $BASE = '';

	public $site = null;
	public $components = array();
	public $page = null;
	public $userID = 'anon';
	public $cronOutput = "";

	public function __construct(){
		$this->ROOT = getcwd().'/'; // Root directory of FLUID installation.
		//$this->ROOT = $_SERVER["DOCUMENT_ROOT"]; //C:/xampp/htdocs/FLUID
		$this->BASE = $this->ROOT.'base/';

		if( !isset($_SERVER["REDIRECT_URL"]) )
			$_SERVER["REDIRECT_URL"] = '';
		//if( empty($_SERVER["REQUEST_URI"]) || ($_SERVER["REQUEST_URI"]=='/') )
		//	$_SERVER["REDIRECT_URL"] = '/index.php';

		$this->site = (object)array(
			'path' => '',
			'name' => '',
			'env' => '',
			'url' => '',
			//'domain' =>		$_SERVER["HTTP_HOST"], 		//itag-local.qualcomm.com
			'domain' =>		$_SERVER["SERVER_NAME"], 		//itag-local.qualcomm.com
			'REQUEST_METHOD' =>	$_SERVER["REQUEST_METHOD"], // GET
			'QUERY_STRING' =>		$_SERVER["QUERY_STRING"], 	// a=1&b=2&c=3
			'REQUEST_URI' =>		$_SERVER["REQUEST_URI"], 		// /asdf/qwer.php?a=1&b=2&c=3
			'REMOTE_ADDR' =>		$_SERVER["REMOTE_ADDR"] 		// 127.0.0.1
		);

		if( isset($_SERVER["REQUEST_URI"]) )
			$this->site->url = $_SERVER["REQUEST_URI"]; 	// /asdf/qwer.php | /index.php
		if( isset($_SERVER["REDIRECT_URL"]) )
			$this->site->url = $_SERVER["REDIRECT_URL"]; 	// /asdf/qwer.php | /index.php
		//$this->site->url = rtrim($this->site->url,"/"); // remove trailing slash from directories
		$this->site->url = ltrim($this->site->url,"/");
		if( empty($this->site->url) )
			$this->site->url = 'index.php';

		$this->page = (object)array(
			'title'=>'No Title',
			'header_top'=>'',
			'header_bottom'=>'',
			'footer_top'=>'',
			'footer_bottom'=>'',
			'loadSuccess'=>true,
			'content_type_sent' => false,
			'header_sent' => false,
			'content_sent' => false,
			'footer_sent' => false,
			'page_rendered' => false,
			'showBreadcrumbs' => true,
			'pathinfo' => pathinfo( $this->site->url ),
			'vars' =>array("blocks"=>array())
		 );
	}

	public function setContentType($type) {
		if( $this->page->content_type_sent )
			return;

		header('Content-Type: '.$type);
		$this->page->content_type_sent = true;
	}
	public function loadComponent($module) {
		if( isset($this->components[$module]) )
			return; // allready registered

		$componentPath = $this->getPathFilePath('components/'.$module.'/'.$module.'.php');
		if( !file_exists($componentPath) )
			return;

		require_once $componentPath;
		if( !class_exists($module) )
			return;

		// dynamically create instance of component class
		$module::$COMPONENT_PATH = $componentPath;
		$this->components[$module] = new $module();
	}

	public function loadComponents( $modulesArray = array() ) {
		// shorthand for loading multiple modules at once
		// note the order is important as some may require others to load first
		foreach( $modulesArray as $idx=>$module )
			$this->loadComponent($module);
	}

	public function PathFileExists($filePath) {
		if( file_exists($this->site->path.$filePath) )
			return true;
		else
			return ( file_exists($this->BASE.$filePath) );
	}

	public function getPathFilePath($filePath) {
		if( file_exists($this->site->path.$filePath) )
			return $this->site->path.$filePath;
		else
			if( file_exists($this->BASE.$filePath) )
				return $this->BASE.$filePath;
		return '';
	}

	public function getPathFileType($filePath) {
		if( file_exists($this->site->path.$filePath) )
			return mime_content_type($this->site->path.$filePath);
		else
			if( file_exists($this->BASE.$filePath) )
				return mime_content_type($this->BASE.$filePath);
		return '';
	}

	public function readPage($filePath) {
		// this is an alias to the loadPathFile function for easy use for users
		return $this->readPathFile($filePath);
	}
	public function readPathFile($filePath) {
		extract($GLOBALS, EXTR_REFS);
		$fileToLoad = '';
		$result = '';
		if( file_exists($this->site->path.$filePath) )
			$fileToLoad = $this->site->path.$filePath;
		elseif( file_exists($this->BASE.$filePath) )
			$fileToLoad = $this->BASE.$filePath;

		if( !empty($fileToLoad) ) {
			OB_START();
			$this->page->loadSuccess = true;
			include($fileToLoad);
			$result = OB_GET_CLEAN();
		}
		return $result;
	}
	
	public function loadPage($filePath, $setContentType=false) {
		// this is an alias to the loadPathFile function for easy use for users
		return $this->loadPathFile($filePath, $setContentType);
	}

	public function loadPathFile($filePath, $setContentType=false) {
		extract($GLOBALS, EXTR_REFS);
		$fileToLoad = '';
		if( file_exists($this->site->path.$filePath) )
			$fileToLoad = $this->site->path.$filePath;
		elseif( file_exists($this->BASE.$filePath) )
			$fileToLoad = $this->BASE.$filePath;

		

		if( !empty($fileToLoad) ) {
			if( $setContentType ) {
				$content_type = $this->mime_content_type($fileToLoad);
				$fluid->setContentType( $content_type );
				if( !preg_match('/text/',$content_type) )
					return readfile($fileToLoad);
			}
			OB_START();
			$this->page->loadSuccess = true;
			include($fileToLoad);
			$result = OB_GET_CLEAN();
			if( $this->page->loadSuccess && $result )
				print $result;
			return $this->page->loadSuccess;
		}
		return false;
	}

	public function loadHeader($titleObject=null, $headerBottom=''){
		/* this function can be called 2 ways:
			$fluid->loadHeader( array(
				'title'  => 'page title',
				'header_top'    => '<meta ... />',
				'header_bottom' =>	'<link... />'
			));

			or a shorthand version
			$fluid->loadHeader( 'page title', '<link... />' );
		*/

		if( gettype($titleObject) == 'array' )
			$this->page = (object)array_merge_recursive((array)$this->page,$titleObject);
		elseif( gettype($titleObject) == 'string' ) {
			$this->page->title = $titleObject;
			if( !empty($headerBottom) )
				$this->page->header_bottom = $headerBottom;
		}

		$this->loadPathFile("layouts/blocks/_header.php");
	}

	public function loadMenu($keywords=''){
		// do logic for getting menu data from database
		$this->loadPathFile("layouts/blocks/_menu.php");
	}

	public function loadFooter($topObject=''){
		/* this function can be called 2 ways:
			$fluid->loadFooter( array(
				'footer_top'    => 'html',
				'footer_bottom' =>	'<script ... />'
			));

			or a shorthand version
			$fluid->loadFooter( '<script... />' );
		*/

		if( gettype($topObject) == 'array' )
			$this->page = (object)array_merge_recursive((array)$this->page,$topObject);
		elseif( !empty($topObject) )
			$this->page->footer_bottom = $topObject;

		$this->loadPathFile("layouts/blocks/_footer.php");
	}

	private function parse_info_format($data) {
		$info = array();

		if (preg_match_all('
			@^\s*                           # Start at the beginning of a line, ignoring leading whitespace
			((?:
				[^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
				\[[^\[\]]*\]                  # unless they are balanced and not nested
			)+?)
			\s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
			(?:
				("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
				(\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
				([^\r\n]*?)                   # Non-quoted string
			)\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
			@msx', $data, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				// Fetch the key and value string.
				$i = 0;
				foreach (array('key', 'value1', 'value2', 'value3') as $var) {
					$$var = isset($match[++$i]) ? $match[$i] : '';
				}
				$value = stripslashes(substr($value1, 1, -1)) . stripslashes(substr($value2, 1, -1)) . $value3;

				// Parse array syntax.
				$keys = preg_split('/\]?\[/', rtrim($key, ']'));
				$last = array_pop($keys);
				$parent = &$info;

				// Create nested arrays.
				foreach ($keys as $key) {
					if ($key == '') {
						$key = count($parent);
					}
					if (!isset($parent[$key]) || !is_array($parent[$key])) {
						$parent[$key] = array();
					}
					$parent = &$parent[$key];
				}

				// Handle PHP constants.
				if (preg_match('/^\w+$/i', $value) && defined($value)) {
					$value = constant($value);
				}

				// Insert actual value.
				if ($last == '') {
					$last = count($parent);
				}
				$parent[$last] = $value;
			}
		}
		return $info;
	}

	private function mime_content_type($filename) {
		// mime_content_type does not always return proper content types
		// $mime_types array is a list of overrides to the mime_content_type function
		$mime_types = array(
			'css' => 'text/css',
			'js' => 'application/x-javascript',
			'json' => 'application/json',
			'jsonml' => 'application/jsonml+json',
			'php'	=> 'text/html'
		 );

		// first check for overrides
		$ext = pathinfo($filename, PATHINFO_EXTENSION );
		if( !empty($mime_types[$ext]) )
			return $mime_types[$ext];

		// otherwise send out the default content type
		return mime_content_type($filename);
	}

	public function loadErrorPage($statusCode=500, $message='There was an error processing your request') {
		header("HTTP/1.0 $statusCode");
		header('Content-Type: text/html; charset=utf-8');
		$error_html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head><title>Error: '.$statusCode.'</title></head>
			<body><h1>Error: '.$statusCode.'</h1>
				<h2>'.$message.'</h2>
			</body></html>';
		print $error_html;
		exit();
	}

	public function load404Page() {
		header("HTTP/1.0 404 Not Found");
		header('Content-Type: text/html; charset=utf-8');
		$fast_404_html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "'.$this->site->url.'" was not found on this server.</p></body></html>';
		print $fast_404_html;
		exit();
	}

	public function StringListToArray($list) {
		switch(gettype($list)) {
				case 'string':
					// if we got a comma seperated list instead of an array
					$list = str_replace(' ','',$list); // remove whitespace
					$list = trim($list, ','); // remove , from edges for the array store fields
					$list = explode(',',$list); // make it an array
					return array_filter($list);

				case 'array':
					return $list; // already an array, send it back

				default;
					break;
			}
			return array(); // not string or array so return blank array
	}

	public function arrayToQuotedString($array, $quote='"', $delim=','){
		$wrapArray = array();
		foreach($array as $item)
			array_push($wrapArray, $quote.$item.$quote);
		return implode($delim,$wrapArray);
	}

}
