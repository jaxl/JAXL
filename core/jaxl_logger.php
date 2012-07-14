<?php

define('JAXL_ERROR', 1);
define('JAXL_WARNING', 2);
define('JAXL_NOTICE', 3);
define('JAXL_INFO', 4);
define('JAXL_DEBUG', 5);

function _error($msg) { JAXLLogger::log($msg, JAXL_ERROR); }
function _warning($msg) { JAXLLogger::log($msg, JAXL_WARNING); }
function _notice($msg) { JAXLLogger::log($msg, JAXL_NOTICE); }
function _info($msg) { JAXLLogger::log($msg, JAXL_INFO); }
function _debug($msg) { JAXLLogger::log($msg, JAXL_DEBUG); }

class JAXLLogger {
	
	public static $level = JAXL_ERROR;
	public static $path = null;
	
	public static $color = array(
		1 => 31,	// error: red
		2 => 34,	// warning: blue
		3 => 33,	// notice: yellow
		4 => 32,	// info: green
		5 => 37		// debug: white
	);
	
	public static function log($msg, $verbosity=1) {
		if($verbosity <= self::$level) {
			$bt = debug_backtrace(); array_shift($bt); $callee = array_shift($bt);
			$msg = basename($callee['file'], '.php').":".$callee['line']." - ".@date('Y-m-d H:i:s')." - ".$msg;
			
			// if no path is set via jaxl config, default log to env stderr
			if(isset(self::$path)) {
				error_log($msg . PHP_EOL, 3, self::$path);
			}
			else {
				error_log(self::colorize($msg, $verbosity));
			}
		}
	}
	
	public static function colorize($msg, $verbosity) {
		return "\033[".self::$color[$verbosity]."m".$msg."\033[0m";
	}
	
}

?>
