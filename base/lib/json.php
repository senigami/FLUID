<?php
/*
	New JSON Parser designed to be compatible with the javascript JSON object as the built in one has issues

	Author: Steven Dunn
	Contact: senigami@gmail.com
	Version: 1.2
	Last Revision: Dec 3 2008
	
	**************************************************************************************
	*** This file is not licenced for general use and is only to be used by permission ***
	**************************************************************************************


	// the require_once is similar to the ifndef for ensuring that a file gets included only once.  Put this on the page that will use json
	require_once('json.php');

	// ----- EXAMPLE FUNCTIONS -----
	$source = "{ 'someName':74.7, 'x x':true, my Object:{myArray:['u','i','o','p'], other:'value'} }";

	// convert a JSON object string into an actual object
	$j = importJSON( $source );

	// transcribe the object into text
	$output = exportJSON( $j );

	// simply using a name will create it as needed
	$j->a = "7.1x2";

	// use the ConvertToNum helper function for numbers that may not only contain numbers
	$j->b = ConvertToNum("$-2,000.15 USD");

	// an object variable name can also be expressed with curly braces and quotes.  this is usefull if the name has spaces
	$j->{'c'} = "8px";

	// even add arrays or objects on demand
	$j->newObject->newArray = array('a','b','c','d');

	// this could also be done by dynamically importing a json element
	$j->newObject = importJSON("{newArray:[a,b,c,d]}");
	//echo $o->{'x x'};

	// array's can be added to dynamicly
	$j->newObject->newArray[] = 'e';

	// or by number
	$j->newObject->newArray[5] = 'f';

	// user the helper function changeObjectName( $orig, $new, $object ) to alter the objects name
	changeObjectName( "newArray", "name switch", $j->newObject );

	// clear a value or complete structure with unset()
	unset( $j->{'my Object'}->myArray );

	// this will alter the content type.  Plain text is easier to read your output when you are not doing html(default)
	// important: must be done before anything is output to the screen
	header('Content-type: text/plain');

	// view your entire object in a viewable format for checking
	print_r($j);

	// you can convert any item into readable text with the makeReadable function including exporting json or a section of the object
	echo makeReadable( $j->{'x x'} );
*/

	function importJSON( $Text=NULL )
	{
		// String Text, String theName, cJSON* Owner
		$newObject;
		$isObject = ( stripEnds($Text) == '{' ); // array or object
		if( $isObject )
			$newObject = new stdClass();
		else
			$newObject = array();
		parseData($Text, $newObject);
		return $newObject;
	}

	function is_assoc($array) {
		return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
	}

	function exportJSON($theObject, $pretty=false, $indent='') // string
	{
	  // surround with {} or [] and add JSON formatted data
		$type = 'object';
		if( gettype($theObject) == 'array' && !is_assoc($theObject) )
			$type = 'array';
	
		switch( $type )
		{
			case 'object':
			{
				$i = 0;
				$Result = '{';
				foreach( $theObject as $k => $v )
				{
					$Result .= ($i++)?",":""; // is not first value add comma
					if( $pretty && empty($indent) ) $Result .= "\r\n".$indent;
					//if( $pretty ) $Result .= "\r\n\t".$indent;
					$Result .= Quoted($k) .":". makeReadable($v, $pretty, $indent);
				}
				//if( $pretty ) $Result .= "\r\n".$indent;
				return $Result.'}';
			}

			case 'array':
			{
				$i = 0;
				$Result = '[';
				foreach( $theObject as $idx => $v )
				{
					$Result .= $i++?",":""; // is not first value add comma
					if( $pretty ) $Result .= "\r\n\t".$indent;
					$Result .= makeReadable($v, $pretty, $indent);
				}
				if( $pretty ) $Result .= "\r\n".$indent;
				return $Result.']';
			}
		}
		return '';
	}


	function stripEnds( &$theValue) //string
	{
		$theValue = trim($theValue);
		$StartChar = substr($theValue, 0, 1);
		$theValue = substr($theValue, 1, strlen($theValue)-2);
		return $StartChar;
	}

	function isArray($a)
	{
		if( gettype($a) == "object" )
			return false;

		if( !count($a) || isset($a[0]) )
			return true;
		else
			return false;
	}
	function isObject($a)
	{
		return !isArray($a);
	}

	function addItem( $theName, $theValue, &$theObject )
	{
		$theName = stripQuotes($theName);
		if( isObject($theObject) )
			$theObject->$theName = $theValue;
		else
			$theObject[] = $theValue;
	}

    //same as empty except it does not treat 0, 0.00, and '0' and empty
    function my_empty($theValue)
    {
        if( !isset($theValue) )
            return true;

        if( ($theValue === 0) || ($theValue === 0.0) || ($theValue === '0') ) //zero values are not empty
            return false;

        return empty($theValue);
    }

	function PutPropertyValue($theName, $theValue, &$theObject) //void
	{
		if( !isObject($theObject) && !isArray($theObject) )
			return;
	  // check first character for [,{,",t,f
	  // [ = array, { = object, " = text(remove quotes), t or f (check for true false)
		if( my_empty($theValue) )
			$theValue = 'null';


		switch( $theValue[0] )
		{
			case '[':
			{
				if( $theValue[strlen($theValue)-1] != ']' ) // not a valid array
					break;
				$newObject = array();
				stripEnds($theValue);
				parseData($theValue, $newObject);
				return addItem( $theName, $newObject, $theObject );
			}

			case '{':
			{
				if( $theValue[strlen($theValue)-1] != '}' ) // not a valid object
					break;
				$newObject = new stdClass();
				stripEnds($theValue);
				parseData($theValue, $newObject);
				return addItem( $theName, $newObject, $theObject );
			}

			case 't':
				if( $theValue == "true" )
					return addItem( $theName, true, $theObject );
				break;

			case 'f':
				if( $theValue == "false" )
					return addItem( $theName, false, $theObject );
				break;

			case '\'':
			case '"':
				if( $theValue[strlen($theValue)-1] == $theValue[0] )
					stripEnds($theValue);
					UnEscapeChars($theValue);
					
			case '0': // padded numbers should be considered strings
				return addItem( $theName, $theValue, $theObject );
			
			case '':
			case 'n':
				return addItem( $theName, '', $theObject );
		}

		if( isNumber($theValue) )
			return addItem( $theName, ConvertToNum($theValue), $theObject );
		return addItem( $theName, $theValue, $theObject );
	}

	function splitString( &$Text, $End)
	{
	  // skip quotes
	  // get next valid character
	  $Result = "";
	  $LastChar = "";
	  $inDQ = false;
	  $inSQ = false;

	  // find the position of the break point
	  while( strlen($Text) )
	  {
		// Ignore Text in Quotes
		if( ($Text[0] == '"') && ($LastChar != '\\') )
		  $inDQ = $inDQ ^ 1;
		if( ($Text[0] == '\'') && ($LastChar != '\\') )
		  $inSQ = $inSQ ^ 1;

		if( $End == $Text[0] ) // might be at a split, make sure not in quotes
			if( !$inDQ && !$inSQ ) // not in quotes ok to end
				break;

		$Result .= $Text[0]; // add character to our result set
		$LastChar = $Text[0]; // used for escaped quotes
		$Text = substr($Text,1); // remove character from search area
	  }
	  return trim($Result);
	}

	function createStack($Text) // array
	{
		$Stack = array();
		$inSQ = false; // in single quotes
		$inDQ = false; // double quotes
		$inSB = 0; // square brace : levels deep
		$inCB = 0; // curly brace : levels deep
  		$inES = 0; // escapted quotes
		$Result = "";
		$LastChar = "";

		while( strlen($Text) )
		{
		    if( ($Text[0] == '\\') )
				$inES = $inES ^ 1;

			if( !$inES ) // not an escaped character
			{
				if( !$inSQ ) // these only count if not in single quotes
					if( ($Text[0] == '"') )
						$inDQ = $inDQ ^ 1;

				if( !$inDQ ) // these only count if not in double quotes
					if( ($Text[0] == "'") )
						$inSQ = $inSQ ^ 1;
	
				if( !$inSQ && !$inDQ ) // these only count if not in quotes
					switch( $Text[0] )
					{
						case '[': $inSB++; break;
						case ']': if( $inSB ) $inSB--; break;
						case '{': $inCB++; break;
						case '}': if( $inCB ) $inCB--; break;
					}
			}

			if( $Text[0] == ',' ) // might be at a split, make sure not in something
				if( !$inDQ && !$inSQ && !$inSB && !$inCB ) // we have a split!
				{
					$Stack[] = trim($Result);
					$LastChar = "";
					$Result = "";
					$Text = substr($Text,1); // remove character from search area
					continue;
				}

			$Result .= $Text[0]; // add character to our result set
			$Text = substr($Text,1); // remove character from search area
		}
		$Stack[] = trim($Result);
		return $Stack;
	}

	function parseData($Text, &$theObject) //void
	{
		$Text = trim($Text);
		if( my_empty($Text) )
			return;
		$Pairs = createStack($Text);

		foreach( $Pairs as $theValue )
		{
			$theName = "";
			if( my_empty($theValue) )
				continue;
			// get pair and check for null

			if( isObject($theObject) )
			{
				$theName = splitString($theValue,":");
				if( my_empty($theName) ) // null and unnamed not allowed in object type, only array
					continue;

				// remove ':' and whitespace
				$theValue = trim( substr( $theValue, 1, strlen($theValue) ) );
			}
			else
			{
				if( trim($theValue) == "null" )
					$theValue = "";
			}

			PutPropertyValue($theName, $theValue, $theObject);
		}

		unset($Pairs);
	}

	function makeReadable( $theValue, $pretty, $indent='')
	{
		$indent.='	';
		switch( gettype($theValue) )
		{
			case "NULL":	return "null";
			
			case "boolean":	return $theValue?"true":"false";
			
			case "integer":
			case "double":	return $theValue;
			
			case "string":	return '"' . EscapeChars($theValue) . '"';
			
			case "array":
			case "object":	return exportJSON($theValue, $pretty, $indent);
			
			default:		return "";
		}
	}

	function EscapeChars( &$theName ) //string
	{
		$BadChar = "\"\r\n\t\\"; // escape out quotes, line feeds, tabs, and the escape char
		for( $i=0; $i < strlen($theName); $i++ )
			if( strpos($BadChar, $theName[$i] ) ) //
				$theName = substr_replace($theName, '\\', $i++, 0);
		return $theName;
	}

	function UnEscapeChars( &$theName )
	{
	  for( $i=0; $i < strlen($theName); $i++ )
		if( ($theName[$i] == '\\') && ($theName[$i+1] == '\\') )
		  $theName = substr($theName,1); // remove character from search area
	  return $theName;
	}

	function Quoted($theName, $BadChar = "{}[]:\",'\\" )
	{
	  // only add quotes if there are special characters i.e. {}[]",' and space
	  EscapeChars($theName);
	  if( isString($theName) )
		return '"' . $theName . '"';
	  return $theName;
	}

	function isString($theName )
	{
	  // if there are characters other than those reserved for numbers.
		return (bool)preg_match('/[^\d.+-]+/',$theName);
	}

	function isNumber($theName )
	{
	  // if there are characters other than those reserved for numbers.
		return !(bool)preg_match('/[^\d\$.,+-]+/',$theName);
	}

	function stripQuotes($theValue)
	{
  		if( my_empty($theValue) )
			return $theValue;

		switch( $theValue[0] )
		{
			default:
				return $theValue;

			case '"':
			case '\'':
				if( $theValue[strlen($theValue)-1] != $theValue[0] )
					return $theValue;
		}
		stripEnds($theValue);
		return trim($theValue);
	}


	// HELPER FUNCTIONS
	function changeObjectName( $orig, $new, &$object )
	{
		if( !isset( $object->$orig ) )
			return;
		$object->$new = $object->$orig;
		unset($object->$orig );
	}

	function ConvertToNum($theText) // removes all non number associated characters for a valid conversion
	{
		return (float)preg_replace('/[^\d.+-]+/','',$theText);
	}
?>
