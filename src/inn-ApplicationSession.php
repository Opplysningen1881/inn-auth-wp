<?php
$parse_uri = explode( "wp-content", __FILE__ );
require_once( $parse_uri[0] . "wp-load.php" );
require_once( "inn-ApplicationToken.php" );
require_once( "inn-Log.php" );

class inn_ApplicationSession {

	function __construct() {
		$this->applicationToken = new inn_ApplicationToken();
		$this->log = new inn_Log();
		$this->options = get_option("inn-auth_options");
	}

	function initializeAppSession() {
		$ch = curl_init();
		$xmlstring = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
						<applicationcredential>
							<params>
								<applicationID>" . $this->options["app_id"] . "</applicationID>
								<applicationName>" . $this->options["app_name"] . "</applicationName>
								<applicationSecret>" . $this->options["app_secret"] . "</applicationSecret>
								<applicationurl>" . $this->options["app_url"] . "</applicationurl>
								<minimumsecuritylevel>" . $this->options["app_defcon"] . "</minimumsecuritylevel>
							</params>
						</applicationcredential>";

		$this->log->info("initializeAppSession(), xmlstring: \n" . $xmlstring);

		curl_setopt($ch, CURLOPT_URL, $this->options["sts_url"] . "/tokenservice/logon");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "applicationcredential=" . $xmlstring);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec($ch);

		if(curl_errno($ch))
		{
			$this->log->error("initializeAppSession() error: " . curl_error($ch));
		}
		curl_close ($ch);

		$this->applicationToken->setAppToken($server_output);
	}

	function renewAppSession($apptoken) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, sprintf("%s/tokenservice/%s/renew_applicationtoken", $this->options["sts_url"],  $this->applicationToken->getAppTokenID($apptoken)));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec($ch);
		$this->log->info("renewAppSession() ApptokenId: \n" . $this->applicationToken->getApptokenId($server_output));

		if(curl_errno($ch))
		{
			$this->log->error("renewAppSession() error: " . curl_error($ch));
		}
		curl_close ($ch);

		$this->applicationToken->setAppToken($server_output);
		return $this->applicationToken->getAppToken();
	}

	function checkAppSession($apptoken) {
		$this->log->info("checkAppSessionExpired() system timestamp: " . time());
		$this->log->info("checkAppSessionExpired() apptoken expires: " . $this->applicationToken->ts2dt($this->applicationToken->getAppTokenExpires($apptoken)));

		$status = NULL;

		if($this->applicationToken->getApplicationID($apptoken) == $this->options["app_id"]) {
			if($this->applicationToken->getAppTokenExpiresSec($apptoken) > time()) {
				$status = "ok";
				$this->log->info(sprintf("checkAppSessionExpired() App session has not expired. System timestamp=%s, apptoken expires=%s", time(), $this->applicationToken->ts2dt($this->applicationToken->getAppTokenExpires($apptoken))));
			} else {
				$status = "expired";
			}
		}

		return $status;
	}
}

?>
