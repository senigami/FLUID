<?php
class replaceTags {
	static $COMPONENT_PATH;
	private $requiredComponents = array();

	function __construct(){
		global $fluid;
		// check to see that required components haver already loaded
		foreach($this->requiredComponents as $component)
			if( !isset($fluid->components[$component]) )
				$fluid->loadErrorPage(500,"The $component component has not been loaded");
	}

	

	public function fromText($text){
		$count = preg_match_all("/\[\^(\w+)\s?(.*?)\^\]/", $text, $matches);



		#if(!$count) return $text;

		// $matches[0][$i] contains the entire matched string
		// $matches[1][$i] contains the first portion (ex: user)
		// $matches[2][$i] contains the second portion (ex: Cobb)
		for($i = 0; $i < $count; $i++){
			$keyword = $matches[1][$i];
			$params = count($matches)>1?trim($matches[2][$i]):'';
			$replacement = $this->process($keyword,$params);
			$text = str_replace($matches[0][$i], $replacement, $text);		
		}
		return $text;	
	}

	public function process($keyword,$params)
	{
		/*
			tags must be in the format [^KEY PARAMS^]
			example [^BLOCK Calendar^]
			example [^FILE test.php^]
		*/
		global $fluid;
	
		switch($keyword)
		{
			case 'BLOCK':
				$replacement = $this->tag_BLOCK($params);
				break;

			case 'FILE':
				$replacement = $this->tag_FILE($params);
				break;
			
			case 'ICON':
				$replacement = $this->tag_ICON($params);
				break;

			case 'BREADCRUMB_BLOCK':
			case 'BC_BLOCK':
				$replacement = $this->tag_BC_BLOCK();
				break;

			case 'FUNCT':
				$replacement = $this->tag_FUNCT($params);
				break;
			
			default:
				return 'Unknown replacement tag type: '.$keyword;
		}			
				
		return $replacement;
	}

	private function tag_ICON($params){
		// [^ICON fa cogs^]
		//<span class="glyphicon glyphicon-asterisk"></span>
		//<span class="fa fa-cogs"></span>

		global $fluid;
		$result = "";
		if( empty($params) )
			return 'ERROR: No valid paramaters';
		
		$params = preg_split("/ /", $params, 2);
		$type	= $params[0];
		$icon	= $params[1];
		return "<span class=\"$type $type-$icon\"></span>";
	}


	private function tag_BLOCK($params){ /* BLOCK tags should specify the file name with no extension */
		global $fluid;
		$result = "";
		if( empty($params) )
			return 'ERROR: No valid paramaters';
		
		$params = preg_split("/ /", $params, 2);
		$file	= $params[0];
		$fluid->page->vars['block_parms'] = count($params)>1?$params[1]:'';
	
		$fileToLoad = $fluid->getPathFilePath('layouts/blocks/'.$file.'.php');

		if( file_exists($fileToLoad) ) {
			OB_START();
			include($fileToLoad);
			$result = OB_GET_CLEAN();
		}			
		return $result;
	}


	private function tag_FILE($params){ /* FILE tags can process php commands */
		global $fluid;
		$result = "*****ERROR! CAN'T FIND FILE: $params*****";
		
		$fileToLoad = $fluid->getPathFilePath($params);
		
		if( file_exists($fileToLoad) ) {
			OB_START();
			include($fileToLoad);
			$result = OB_GET_CLEAN();
		}			
		return $result;
	}

	private function tag_BC_BLOCK(){ /* SIDEBAR tags will load block called "sidebar_[previous breadcrumb id]" */
		global $fluid;
		
		$bc = $fluid->components['Breadcrumbs']->bc;

		$limit = empty($params) ? count($bc) : $params * 1;
		$blockText = $fluid->components['blockText'];
		$bcExists = false;
		$startIndex = count($bc) - 1;
		$basicID = isset($fluid->page->basic['id']) ? $fluid->page->basic['id'] : "";

	
		//Loop through breadcrumb stack (most recent will be first)
		//for($i =0; $i < count($bc); $i++){
		if($startIndex < $limit){
			for($i = $startIndex; $i >= 0; $i--){

				//make sure bc url is not current page (when refreshing, etc.)
				if($bc[$i]['id'] != $basicID){
					//Get the id of iterated breadcrumb page

					$sidebarTitle = "sidebar_".$bc[$i]['id'];
					
					
					//Check if sidebar block for breadcrumb page exists
					if($blockText->exists($sidebarTitle)){
						
						/*$content = "Return to <a href='".$bc[$i]['url']."'>" . $bc[$i]['title'] . '</a><br /><br />' . $this->fromText($result['content']);*/

						if(!isset($bc[$i]['params'])) $bc[$i]['params'] = array();
						
						$fluid->page->vars['BC_BLOCK'] = array("url"=>$bc[$i]['url'],"title"=>$bc[$i]['title'],"params"=>$bc[$i]['params']);

						$result = $blockText->load($sidebarTitle);
						$content = $this->fromText($result['content']);

						return $content;
					}					
				}
			}
		}
		
		return "";		
	}

	private function tag_FUNCT($params){
		global $fluid;
		$params = preg_split("/ /", $params,3);
		if(count($params) < 2) return false;

		$component	= $params[0];
		$function	= $params[1];

		// make sure the component exists
		if( !isset($fluid->components[$component]) )
			$fluid->loadComponent($component);
		
		if( !isset($fluid->components[$component]) )
			return "ERROR: Failed to Load $component"; // loading failed

		$c = $fluid->components[$component];
		$func = array($component,$function);
		
		try{
			if(isset($params[2]))
				return $c->$function($params[2]);

			else return $c->$function();
		}catch(Exception $e) {
			return "Error: with $func call to $component: ".$e->getMessage();
		}
	}
}
