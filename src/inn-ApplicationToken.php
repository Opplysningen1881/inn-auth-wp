<?php
require_once( "inn-Log.php" );

class inn_ApplicationToken {
	private $log;

	function __construct() {
		$this->log = new inn_Log();
	}

	function setAppToken($apptoken) {
		// if (!$this->isValidXML($apptoken)) {
		// 	die ("setAppToken: Not a valid Apptoken XML: " . $apptoken);
		// }

		$res = update_option("inn_apptoken", $apptoken);

		if($res) {
			$this->log->info("setAppToken() success: " . $apptoken);
			return $apptoken;
		}
		else
			{
				$this->log->error("setAppToken() failed: " . $apptoken);
			}
	}

	function getAppToken() {
		$apptoken = get_option("inn_apptoken");

		return $apptoken;
	}

	function getAppTokenID($apptoken) {
		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$apptokenID = $apptokenSimpleXml->params->applicationtokenID;

		return $apptokenID;
	}

	function getApplicationID($apptoken) {
		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$applicationID = $apptokenSimpleXml->params->applicationid;

		return $applicationID;
	}

	function getApplicationName($apptoken) {
		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$applicationName = $apptokenSimpleXml->params->applicationname;

		return $applicationName;
	}

	function getAppTokenExpires($apptoken) {
		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$expires = $apptokenSimpleXml->params->expires;

		if(!$this->isValidTimeStamp($expires)) {
			$expires = time() - (365 * 24 * 60 * 60);
		}

		return $expires;
	}

	function getAppTokenExpiresSec($apptoken) {
		$expires = $this->getAppTokenExpires($apptoken);
		$expiresSec = round($expires/1000);

		return $expiresSec;
	}

	function ts2dt($timestamp) {
		$dt = date("Y-m-d H:i:s");

		return $dt;
	}

	function isValidTimeStamp($timestamp)
	{
	    return ((string) (int) $timestamp === $timestamp)
	        && ($timestamp <= PHP_INT_MAX)
	        && ($timestamp >= ~PHP_INT_MAX);
	}

	function isValidXML($apptoken) {
		if(strlen($apptoken) > 0) {
			return FALSE;
		}

		$isXML = simplexml_load_string($apptoken);

		if(!$isXML) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
}

?>
