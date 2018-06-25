<?php
$parse_uri = explode( "wp-content", __FILE__ );
require_once( $parse_uri[0] . "wp-load.php" );
require_once( "inn-ApplicationToken.php" );
require_once( "inn-Log.php" );

class inn_ApplicationSession {

	function __construct() {
		$this->apptoken = new inn_ApplicationToken();
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
			return FALSE;
		}
		curl_close ($ch);

		$this->apptoken->setAppToken($server_output);
	}

	function renewAppSession() {
		$apptoken = $this->apptoken->getAppToken();
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, sprintf("%s/tokenservice/%s/renew_applicationtoken", $this->options["sts_url"],  $this->apptoken->getAppTokenID($apptoken)));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec($ch);
		$this->log->info("renewAppSession() ApptokenId: \n" . $this->apptoken->getApptokenId($server_output));

		if(curl_errno($ch))
		{
			$this->log->error("renewAppSession() error: " . curl_error($ch));
			return FALSE;
		}
		curl_close ($ch);

		$this->apptoken->setAppToken($server_output);
		return $this->apptoken->getAppToken();
	}

	function verifyAppSession() {
		$apptoken = $this->apptoken->getAppToken();
		$this->log->info("verifyAppSession apptoken: " . $apptoken);
		$this->log->info(var_dump($apptoken));

		if($this->apptoken->getAppTokenExpires($apptoken) > time()) {
			$result = $this->renewAppSession();
		} else {
			$result = $this->initializeAppSession();
		}
	}

	function checkAppSession() {
		$status = NULL;
		$apptoken = $this->apptoken->getAppToken();

		if($this->apptoken->isValidXML($apptoken)) {
			if($this->apptoken->getApplicationID($apptoken) == $this->options["app_id"]) {
				$this->log->info("checkAppSession() system timestamp: " . time());
				$this->log->info("checkAppSession() apptoken expires: " . $this->apptoken->getAppTokenExpires($apptoken));
				$this->log->info("checkAppSession() apptoken expires sec: " . $this->apptoken->getAppTokenExpiresSec($apptoken));

				if($this->apptoken->getAppTokenExpires($apptoken) > time()) {
					$status = "ok";
					$this->log->info(sprintf("checkAppSessionExpired() App session has not expired. System timestamp=%s, apptoken expires=%s", time(), $this->apptoken->ts2dt($this->apptoken->getAppTokenExpires($apptoken))));
				} else {
					$status = "expired";
				}
			}
		}
		return $status;
	}
}

?>
