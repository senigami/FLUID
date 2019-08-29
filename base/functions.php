<?php
	//function value(&$value,$default=''){
	//	return isset($value)?$value:$default;
	//}

	/*
		define('VERSION', '0.01');
		define('DRUPAL_MINIMUM_PHP', '5.2.4');
		 requirements
	 
	*/
	
	/**
	 * Starts the timer with the specified name.
	 *
	 * If you start and stop the same timer multiple times, the measured intervals
	 * will be accumulated.
	 *
	 * @param $name
	 *   The name of the timer.
	 */
	function timer_start($name) {
		global $timers;
	
		$timers[$name]['start'] = microtime(TRUE);
		$timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
	}
	
	/**
	 * Reads the current timer value without stopping the timer.
	 *
	 * @param $name
	 *   The name of the timer.
	 *
	 * @return
	 *   The current timer value in ms.
	 */
	function timer_read($name) {
		global $timers;
	
		if (isset($timers[$name]['start'])) {
			$stop = microtime(TRUE);
			$diff = round(($stop - $timers[$name]['start']) * 1000, 2);
	
			if (isset($timers[$name]['time'])) {
				$diff += $timers[$name]['time'];
			}
			return $diff;
		}
		return $timers[$name]['time'];
	}
	
	/**
	 * Stops the timer with the specified name.
	 *
	 * @param $name
	 *   The name of the timer.
	 *
	 * @return
	 *   A timer array. The array contains the number of times the timer has been
	 *   started and stopped (count) and the accumulated timer value in ms (time).
	 */
	function timer_stop($name) {
		global $timers;
	
		if (isset($timers[$name]['start'])) {
			$stop = microtime(TRUE);
			$diff = round(($stop - $timers[$name]['start']) * 1000, 2);
			if (isset($timers[$name]['time'])) {
				$timers[$name]['time'] += $diff;
			}
			else {
				$timers[$name]['time'] = $diff;
			}
			unset($timers[$name]['start']);
		}
	
		return $timers[$name];
	}

	/*
function fluid_page_header() {
  //$headers_sent = &drupal_static(__FUNCTION__, FALSE);
  if ($headers_sent) {
    return TRUE;
  }
  $headers_sent = TRUE;

  $default_headers = array(
    'Expires' => 'Sun, 19 Nov 1978 05:00:00 GMT',
    'Cache-Control' => 'no-cache, must-revalidate',
    // Prevent browsers from sniffing a response and picking a MIME type
    // different from the declared content-type, since that can lead to
    // XSS and other vulnerabilities.
    'X-Content-Type-Options' => 'nosniff',
  );
  fluid_send_headers($default_headers);
}
function fluid_send_headers($default_headers = array(), $only_default = FALSE) {
  //$headers_sent = &drupal_static(__FUNCTION__, FALSE);
  //$headers = drupal_get_http_header();
  if ($only_default && $headers_sent) {
    $headers = array();
  }
  $headers_sent = TRUE;

  $header_names = _drupal_set_preferred_header_name();
  foreach ($default_headers as $name => $value) {
    $name_lower = strtolower($name);
    if (!isset($headers[$name_lower])) {
      $headers[$name_lower] = $value;
      $header_names[$name_lower] = $name;
    }
  }
  foreach ($headers as $name_lower => $value) {
    if ($name_lower == 'status') {
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $value);
    }
    // Skip headers that have been unset.
    elseif ($value !== FALSE) {
      header($header_names[$name_lower] . ': ' . $value);
    }
  }
}
*/

/**
 * Encodes special characters in a plain-text string for display as HTML.
 *
 * Also validates strings as UTF-8 to prevent cross site scripting attacks on
 * Internet Explorer 6.
 *
 * @param string $text
 *   The text to be checked or processed.
 *
 * @return string
 *   An HTML safe version of $text. If $text is not valid UTF-8, an empty string
 *   is returned and, on PHP < 5.4, a warning may be issued depending on server
 *   configuration (see @link https://bugs.php.net/bug.php?id=47494 @endlink).
 *
 * @see drupal_validate_utf8()
 * @ingroup sanitization
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}


function request_uri() {
  if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
  }
  else {
    if (isset($_SERVER['argv'])) {
      $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
    }
    elseif (isset($_SERVER['QUERY_STRING'])) {
      $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
    }
    else {
      $uri = $_SERVER['SCRIPT_NAME'];
    }
  }
  // Prevent multiple slashes to avoid cross site requests via the Form API.
  $uri = '/' . ltrim($uri, '/');

  return $uri;
}


function fluid_base64_encode($string) {
  $data = base64_encode($string);
  // Modify the output so it's safe to use in URLs.
  return strtr($data, array('+' => '-', '/' => '_', '=' => ''));
}

/**
 * Calculates a base-64 encoded, URL-safe sha-256 hash.
 *
 * @param $data
 *   String to be hashed.
 *
 * @return
 *   A base-64 encoded sha-256 hash, with + replaced with -, / with _ and
 *   any = padding characters removed.
 */
function fluid_hash_base64($data) {
  $hash = base64_encode(hash('sha256', $data, TRUE));
  // Modify the hash so it's safe to use in URLs.
  return strtr($hash, array('+' => '-', '/' => '_', '=' => ''));
}

//function drupal_anonymous_user() {
//  $user = variable_get('drupal_anonymous_user_object', new stdClass);
//  $user->uid = 0;
//  $user->hostname = ip_address();
//  $user->roles = array();
//  $user->roles[DRUPAL_ANONYMOUS_RID] = 'anonymous user';
//  $user->cache = 0;
//  return $user;
//}


/**
 * Returns the time zone of the current user.
 */
function drupal_get_user_timezone() {
  global $user;
  if (variable_get('configurable_timezones', 1) && $user->uid && $user->timezone) {
    return $user->timezone;
  }
  else {
    // Ignore PHP strict notice if time zone has not yet been set in the php.ini
    // configuration.
    return variable_get('date_default_timezone', @date_default_timezone_get());
  }
}


/**
 * Gets a salt useful for hardening against SQL injection.
 *
 * @return
 *   A salt based on information in settings.php, not in the database.
 */
function drupal_get_hash_salt() {
  global $drupal_hash_salt, $databases;
  // If the $drupal_hash_salt variable is empty, a hash of the serialized
  // database credentials is used as a fallback salt.
  return empty($drupal_hash_salt) ? hash('sha256', serialize($databases)) : $drupal_hash_salt;
}
