<?php
class cURL {
	static $COMPONENT_PATH;
	private $curlSession;
	
	function __construct(){
		global $fluid;
		// check to see that DB component has already loaded
		if( !isset($fluid->components["DB"]) )
			$fluid->loadErrorPage(500,"The Database component has not been loaded");
	}

	public function get($url) {
		$this->curlSession = curl_init();
		$cS = $this->curlSession;

		$this->setOptions($url);
		$response = curl_exec( $cS );
		curl_close($cS);

		return $response;
	}
	public function post($url,$postData) {
		$this->curlSession = curl_init();
		$cS = $this->curlSession;

		$this->setOptions($url);
		curl_setopt($cS, CURLOPT_POST, 1); // true
		curl_setopt($cS, CURLOPT_POSTFIELDS, $postData);
			//usually in format: "postvar1=value1&postvar2=value2&postvar3=value3"
			//but can also be an array(postvar1 => value1, postvar2 => value2, postvar3 => value3)
			// send a file with ['file'] = '@/path/to/file'; note the @ sign
			
		$response = curl_exec( $cS );
		curl_close($cS);

		return $response;
	}
	
	public function postJSON($url,$jsonObj) {
		$data_string = json_encode($data);
		
		$this->curlSession = curl_init();
		$cS = $this->curlSession;

		$this->setOptions($url);
		
		curl_setopt($cS, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($cS, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($cS, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($cS, CURLOPT_HTTPHEADER, array(                                                                          
				'Content-Type: application/json',                                                                                
				'Content-Length: ' . strlen($data_string))                                                                       
		);

		$response = curl_exec( $cS );
		curl_close($cS);

		return $response;
	}

	
	private function setOptions($url){
		set_time_limit(60);
		$cS = $this->curlSession;
		
		curl_setopt( $cS, CURLOPT_URL, $url);
		curl_setopt( $cS, CURLOPT_REFERER, $url);
		if( preg_match('/https/',$url ) )
			curl_setopt($cS, CURLOPT_SSL_VERIFYPEER, false);

		// enable to accept encoded/compressed data
		//curl_setopt($cS, CURLOPT_ENCODING, "gzip"); // the page encoding
			
		curl_setopt( $cS, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $cS, CURLOPT_HEADER, 0);
		curl_setopt( $cS, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $cS, CURLOPT_CONNECTTIMEOUT ,60);
		curl_setopt( $cS, CURLOPT_TIMEOUT, 60); //timeout in seconds
		curl_setopt( $cS, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0");
	}
		
}


