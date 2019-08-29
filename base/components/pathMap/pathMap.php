<?php
class pathMap {
	static $COMPONENT_PATH;

	function __construct(){
		global $fluid;
		// check to see that DB component has already loaded
		if( !isset($fluid->components["DB"]) )
			$fluid->loadErrorPage(500,"The Database component has not been loaded");
	}

	public function load() {
		global $fluid;
		// check if we are looking for an explicit file path with extension
			// look for that file off the root
			// look for that file off site/base
			// check db for file entry
			// check db for folder path entry
			// check db for root folder path entry
		// if directory do db checks only


		$url = $fluid->site->url;

		if( isset($fluid->page->pathinfo['extension'])
			 && !empty($fluid->page->pathinfo['extension']) ) {
			// explicit path was requested
			// file does not exist in current path or we wouldn't be here

			// check to see if the file exists in site or base
			if( $fluid->loadPathFile($url, true) )
				return;
		}

		// file did not exist in site or base folder, check for alias
		// check the db to see if we have an alias for this path
			$queryURL = ltrim($url,"/"); // remove initial slash
			$queryURL = rtrim($queryURL,"/"); // remove trailing slash from directories
			$dir = $rootDir = '';

		if( isset($fluid->page->pathinfo['dirname'])
			 && !empty($fluid->page->pathinfo['dirname'])
			 && ($fluid->page->pathinfo['dirname']!='.') ) {
			// extract sub directories
				$dir = $fluid->page->pathinfo['dirname'];

			// extract root directory
				$dirs = explode('/',$dir);
				$rootDir = count($dirs)?$dirs[0]:'';
		}

		// query the DB
		$query = "select url,target from fluid.pathMap where site in ('{$fluid->site->name}','[GLOBAL]') and url in ('$queryURL','$dir','$rootDir') order by url desc, site desc limit 1";
		
		$result = $fluid->components['DB']->select($query);

		if( count($result) ) {
			if( $fluid->loadPathFile($result[0]['target'], true) )
				exit;
		}
		if( $fluid->loadPathFile("layouts/basic/basic.php") ) //empty($fluid->site->url) &&
			return;

			
		// check if this is a directory path with no file name
		if( isset($fluid->page->pathinfo['dirname'])
			 && !isset($fluid->page->pathinfo['extension']) ) {

			if( substr($fluid->site->url, -1) != '/' ) {
				$url .= '/';
				if( !empty($fluid->site->QUERY_STRING) )
					$url .= '?' . $fluid->site->QUERY_STRING;
				header('Location: ' . $url, true, 302); // 301 is permenant
				die();
			}
			$url .= 'index.php';
			if( $fluid->loadPathFile($url, true) )
				return;
		}


		$fluid->load404Page();
	}

}
