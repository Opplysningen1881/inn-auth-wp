<?php
require_once( "inn-Log.php" );

class inn_ApplicationToken {
	private $log;

	function __construct() {
		$this->log = new inn_Log();
	}

	function setAppToken($apptoken) {
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
		return get_option("inn_apptoken");
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

		return $expires;
	}

	function getAppTokenExpiresSec($apptoken) {
		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$expires = $apptokenSimpleXml->params->expires;
		$expiresSec = round($expires/1000);

		return $expiresSec;
	}

	function ts2dt($timestamp) {
		$dt = date("Y-m-d H:i:s");

		return $dt;
	}
}

?>
