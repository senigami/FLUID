<?php
	// last updated by Steven Dunn on 11-10-2008
	
	// safe function to always return data from a request without doing the isset check first
	//  bool fluid_request( string )
	function fluid_request($theVar, $trim=true, $defaultValue='')
	{
		if( $trim )
			return isset($_REQUEST[$theVar])?trim($_REQUEST[$theVar]):$defaultValue;
		else
			return isset($_REQUEST[$theVar])?$_REQUEST[$theVar]:$defaultValue;
	}
	
	// safe fluid_val to always return data from an object without doing the isset check first
	function fluid_val( $theObject, $theVar, $trim=false)
	{
		switch( gettype($theObject) )
		{
			case 'object':
				if( $trim )
					return isset($theObject->$theVar)?trim($theObject->$theVar):'';
				else
					return isset($theObject->$theVar)?$theObject->$theVar:'';

			case 'array':
				if( $trim )
					return isset($theObject[$theVar])?trim($theObject[$theVar]):'';
				else
					return isset($theObject[$theVar])?$theObject[$theVar]:'';
		}
		return '';
	}
	


	function fluid_validHash( &$theObject, $items = NULL, $trim=false )
	{
		switch( gettype($theObject) )
		{
			case 'object':
				foreach( $items as $name )
					$theObject->$name = fluid_val($theObject,$name,$trim);
			return;

			case 'array':
				foreach( $items as $name )
					$theObject[$name] = fluid_val($theObject,$name,$trim);
			return;
		}
	}



	// load all variables into the object if they exist or fill with blanks if flagged
	//  void loadContent(   object,     array,  bool )
	function loadContent( &$theObject, $items, $allowBlanks=false, $trim=false )
	{
		switch( gettype($theObject) )
		{
			case 'object':
				foreach( $items as $name )
				{
					if( content($name) != '' )
						$theObject->$name = fluid_request($name,$trim);
					elseif( $allowBlanks || !isset($theObject->$name) )
						$theObject->$name = '';
				}
			return;

			case 'array':
				foreach( $items as $name )
				{
					if( content($name) != '' )
						$theObject[$name] = fluid_request($name,$trim);
					elseif( $allowBlanks || !isset($theObject[$name]) )
						$theObject[$name] = '';
				}
			return;
		}
	}

	// clear fields
	//  void fluid_clearHash(   object,     array,  bool )
	function fluid_clearHash( &$theObject, $items, $blankOnly=true )
	{
		switch( gettype($theObject) )
		{
			case 'object':
				foreach( $items as $name )
					if( $blankOnly )
						$theObject->$name = '';
					else
						unset($theObject->$name);
			return;

			case 'array':
				foreach( $items as $name )
					if( $blankOnly )
						$theObject[$name] = '';
					else
						unset($theObject[$name]);
			return;
		}
	}
	
	function buildURL($url, $params, $override=false)
	{
		$parsedURL = parse_url($url);
		parse_str( value($parsedURL,'query'), $parsedQuery );
		if( $override )
			$query = array_merge($parsedQuery, $params);
		else
			$query = array_merge($params, $parsedQuery);
		$parsedURL['query'] = http_build_query  ( $query );
		return $parsedURL['path'].'?'.$parsedURL['query'];
	}
