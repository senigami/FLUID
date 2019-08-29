<?php
class getURL {
	static $COMPONENT_PATH;

	function __construct(){
		global $fluid;
		// check to see that DB component has already loaded
		if( !isset($fluid->components["DB"]) )
			$fluid->loadErrorPage(500,"The Database component has not been loaded");
	}

	public function load($url) {
		$curlSession = curl_init();
		set_time_limit(60);

		curl_setopt( $curlSession, CURLOPT_URL, $url);
		curl_setopt( $curlSession, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $curlSession, CURLOPT_HEADER, 0);
		curl_setopt( $curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $curlSession, CURLOPT_CONNECTTIMEOUT ,60);
		curl_setopt( $curlSession, CURLOPT_TIMEOUT, 60); //timeout in seconds
		curl_setopt( $curlSession, CURLOPT_REFERER, $url);
		curl_setopt( $curlSession, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0");
		if( preg_match('/https/',$url ) )
			curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec( $curlSession );
		curl_close($curlSession);

		// if data is compressed on return
		// $response = $this->gzdecode( $response );
		return $response;
	}

	private function gzdecode($data){
		// if the server sends back compressed data then run it through this to fix it

		$g=tempnam('tmp','ff');
		//$g=tempnam(sys_get_temp_dir(),'ff');
		//echo "___".$g; exit;
		@file_put_contents($g,$data);
		ob_start();
		readgzfile($g);
		$d=ob_get_clean();
		unlink($g); // had issue with more than 65535 files in the temp dir
		return $d;
	}
}
