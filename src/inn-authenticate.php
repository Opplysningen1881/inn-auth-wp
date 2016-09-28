<?php
// Debug mode
define('DEBUG', false);

$parse_uri = explode( 'wp-content', __FILE__ );
require_once( $parse_uri[0] . 'wp-load.php' );

class inn_authenticate {
	public $options;
//	$apptoken;
	
	public function __construct() {
		$this->options = get_option("inn-auth_options");
	}

	//		print_r(curl_getinfo($ch));
//		echo "<p>getAppToken auth_options: " . $this->options["app_id"] . " - " . $this->options["app_secret"] . "</p>";
//		echo "<p>getAppToken server_output: " . $server_output . "</p>";
	
	
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
		
		$this->debug("getAppToken()", "xmlstring: \n" . $xmlstring);
		
		curl_setopt($ch, CURLOPT_URL, $this->options["sso_url"] . "/tokenservice/logon");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "applicationcredential=" . $xmlstring);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec($ch);
		$this->debug("getAppToken()", "server_output: \n" . $server_output);
		
		if(curl_errno($ch))
		{
			echo 'get apptoken error:' . curl_error($ch);
		}
		curl_close ($ch);
		
		//$this->apptoken = $server_output;
		
		global $apptoken;
		$apptoken = $server_output;
	}
	
	function checkAppSessionExpired() {
		$this->debug("checkAppSessionExpired()", "system timestamp: " . time());
		$this->debug("checkAppSessionExpired()", "apptoken expires: " . $this->getAppTokenExpires($this->apptoken));
		
		$expired = true;
		
		if($this->getAppTokenExpires($this->apptoken) > time()) {
			$expired = false;
			$this->debug("checkAppSessionExpired()", "App session not expired. System timestamp=" . time() . ", apptoken expired=" . $this->getAppTokenExpires($this->apptoken));
		}
		
		return $expired;
	}
	
	function getAppTokenExpires($apptoken) {
		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$expires = $apptokenSimpleXml->params->expires;
		$expiresSec = round($expires/1000);
		
		return $expiresSec;
	}
	
	function getAppToken() {
//		if($this->apptoken == null || strlen($this->apptoken) < 1 || $this->checkAppSessionExpired()) {
	if($apptoken == null || strlen($apptoken) < 1 || $this->checkAppSessionExpired()) {
			$this->initializeAppSession();
		}
		return $apptoken;
	}
	
	function getUserToken($userTicket) {
		$apptoken = $this->getAppToken();

		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$apptokenID = $apptokenSimpleXml -> params -> applicationtokenID;
		
		$ch = curl_init();

		curl_setopt ( $ch, CURLOPT_URL, $this->options["sso_url"] . "/tokenservice/user/" .$apptokenID . "/get_usertoken_by_userticket/");
		curl_setopt ( $ch, CURLOPT_POST, 1);
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query(array("apptoken" => $apptoken , "userticket" => $userTicket)));
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true);

		$this->debug("getUserToken()", "apptoken: \n" . $apptoken);
		$this->debug("getUserToken()", "userticket: \n" . $userTicket);
		$this->debug("getUserToken()", "curl: \n" . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		$this->debug("getUserToken()", "curl http code: \n" . curl_getinfo($ch, CURLINFO_HTTP_CODE));
		
		$usertoken = curl_exec($ch);
		
		$this->debug("getUserToken()", "usertoken: \n" . $usertoken);
		
		if(curl_errno($ch))
		{
			echo 'get usertoken error:' . curl_error($ch);
		}
		
		curl_close ($ch);
		
		return $usertoken;
	}
	
	function getUserTokenById($userTokenId) {

		$apptoken = $this->getAppToken();

		$apptokenSimpleXml = simplexml_load_string($apptoken);
		$apptokenID = $apptokenSimpleXml -> params -> applicationtokenID;
		
		$ch = curl_init();

		curl_setopt ( $ch, CURLOPT_URL, $this->options["sso_url"] . "/tokenservice/user/" .$apptokenID . "/get_usertoken_by_usertokenid/");
		curl_setopt ( $ch, CURLOPT_POST, 1);
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query(array("apptoken" => $apptoken , "usertokenid" => $userTokenId)));
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true);
		$usertoken = curl_exec($ch);
		
		if(curl_errno($ch))
		{
			echo 'get usertokenbyid error:' . curl_error($ch);
		}
		
		curl_close ($ch);
		
		return $usertoken;
	}
	
	function authenticate($usertoken) {
		
		if (strlen($usertoken) == 0) die ("authenticate(): No usertoken.");
		
		$wp_user_id = username_exists($this->getUserName($usertoken));
		
		if(!$wp_user_id and email_exists($this->getEmail($usertoken)) == false) {
			$wp_user_id = $this->wpRegisterUser($usertoken);
		}
		
		if ( strlen($this->getUserTokenId($usertoken)) > 0 && $this->checkApplicationHasRole($usertoken) == 1 ) {
			$authenticated = $this->wpSignonUser($wp_user_id, $usertoken);
		} else {
			$authenticated = $this->wpSignoutUser($usertoken);
		}

		## debug info
		$this->debug("authenticate()", "wp_user_id for " . $this->getEmail($usertoken) . ": " . $wp_user_id);
		$this->debug("authenticate()", "usertoken: " . $usertoken);
		$this->debug("authenticate()", "ApplicationHasRole: " . $this->checkApplicationHasRole($usertoken));
		$this->debug("authenticate()", "authenticated: " . (string)$authenticated);
		
		return $authenticated;
	}
	
	function wpRegisterUser($usertoken) {
		// register new user in WP
		
		$user_name = $this->getUserName($usertoken);
		$user_email = $this->getEmail($usertoken);
		
		$password = $this->createPassword($usertoken);
		$user_id = wp_create_user( $user_name, $password, $user_email );
		
		if ( is_wp_error( $user_id ) )
			echo "<p>wpRegisterUser() wp_create_user error: " . $user_id->get_error_message() . "</p>";

		
		$userdata = array(
			"ID" => $user_id,
			"user_nicename" => $this->getFirstName($usertoken) . " " . $this->getLastName($usertoken),
			"display_name" => $this->getFirstName($usertoken) . " " . $this->getLastName($usertoken),
			"first_name" => $this->getFirstName($usertoken),
			"last_name" => $this->getLastName($usertoken)
		);
		$user_id = wp_update_user($userdata);
		
		update_user_meta( $user_id, 'adresse', (string)$this->getAddress($usertoken) );
		update_user_meta( $user_id, 'telefon', (string)$this->getPhone($usertoken) );
		
		echo "<p>Registrerte bruker. user_id: " . $user_id . "</p>";
		
		return $user_id;
	}

	function wpSignonUser($user_id, $usertoken) {
		$user = get_user_by("ID", $user_id);
		
		wp_set_auth_cookie( $user_id, true );
		$uum = update_user_meta( $user_id, "inn_usertokenid", $this->getUserTokenId($usertoken) );
		$this->debug("authenticate()", "update_user_meta: " . $uum);
		
		return true;
	}
	
	function wpSignoutUser($usertoken) {
		wp_logout();
		
		return true;
	}
	
	function createPassword($usertoken) {
		$password = password_hash($usertoken, PASSWORD_DEFAULT);
		
		return $password;
	}

	
	function getUserTokenId($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$usertokenid = "";
		
		if(isset($ut["id"]))
			$usertokenid = (string) $ut["id"];
		
		return $usertokenid;
	}

	function checkApplicationHasRole($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$appHasRole = false;
		
		if($ut->application["ID"] == $this->options["app_id"])
			$appHasRole = true;
		
		return $appHasRole;
	}
	
	// Checks if the session id is still active
	function checkSessionId() {
		$result = false;
		$wpuserid = get_current_user_id();
		$this->debug("checkSessionId()", "wpuserid: " . $wpuserid);
		
		if($wpuserid > 0) {
			$wpusertokenid = get_user_meta($wpuserid, "inn_usertokenid", true);
			$this->debug("checkSessionId()", "wpusertokenid: " . $wpusertokenid);
			
			if(strlen($wpusertokenid) > 0) {
				$usertoken = $this->getUserTokenById($wpusertokenid);
				$this->debug("checkSessionId()", "usertoken: " . $usertoken);
				
				if(strlen($this->getUserTokenId($usertoken)) > 0) {
					$result = true;
					$this->debug("checkSessionId()", "getUserTokenId: " . $this->getUserTokenId($usertoken));
				}
			}
		}
		
		$this->debug("checkSessionId()", "result: " . $result);
		
		return $result;
	}
	
	function getUserName($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$username = $ut->username;
		
		return $username;
	}
	
	function getFirstName($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$firstname = $ut->firstname;
		
		return $firstname;
	}
	
	function getLastName($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$lastname = $ut->lastname;
		
		return $lastname;
	}

	function getPhone($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$phone = $ut->cellphone;
		
		return $phone;
	}
	
	function getEmail($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$email = $ut->email;
		
		return $email;
	}
	
	function getAddress($usertoken) {
		$address = "";
		
		if(strlen($usertoken) > 0) {
			$ut = simplexml_load_string($usertoken) or die ("ERROR in getAddress(): Couldnt load usertoken: " . strlen($usertoken));
			foreach($ut->application as $app) {
				if ($app["ID"] == $this->options["app_id"]) {
					if($app->role["name"] == "INNDATA") {
						$address = $app->role["value"];
					}
				}
			}
		}
		
		return $address;
	}
	
	function printMyTokenFormatted(){
		$wpuserid = get_current_user_id();
		$wpusertokenid = get_user_meta($wpuserid, "inn_usertokenid", true);
		
		$output = "";
		
		if(strlen($wpusertokenid) > 0) {
			$usertoken = $this->getUserTokenById($wpusertokenid);

			echo "<div style=\"display:none;\">printMyTokenFormatted usertoken: <pre>" . $usertoken . "</pre></div>";
			
			$ut = simplexml_load_string($usertoken) or die("Error in  printMyTokenFormatted(): Cannot create object");
			
			$output = "\n<div>\n<p></p>\n<ul>";
			$output .= "\n\t<li>Brukernavn: " . $ut->username . "</li>";
			$output .= "\n\t<li>Fornavn: " . $ut->firstname . "</li>";
			$output .= "\n\t<li>Etternavn: " . $ut->lastname . "</li>";
			$output .= "\n\t<li>E-post: " . $ut->email . "</li>";
			$output .= "\n\t<li>Leveringsadresse: " . $this->formatDeliveryaddress($this->getAddress($usertoken)) . "</li>";
			$output .= "\n\t<li>INN-sesjonen startet: " . $this->getDateTimeFromTimestamp($ut->timestamp) . "</li>";
			$output .= "\n\t<li>INN-sesjonen utløper: " . $this->getDateTimeFromTimestamp($ut->timestamp + $ut->lifespan) . "</li>";
			$output .= "\n</div>\n</ul>";

		} else {
			$output = "<p>Oops! Ikke en INN-bruker." . $wpusertokenid . "</p>";
		}
		
		return $output;
	}

	function formatDeliveryaddress($deliveryAddressJSON) {
		//$deliveryAddress = $deliveryAddressJSON;
		$deliveryAddress = json_decode($deliveryAddressJSON, true);
		
		$addressString = "\n<ul>";
		
		foreach($deliveryAddress as $address) {
			$addressString .= "\n\t<li>" . $address["name"];
			$addressString .= ", " . $address["addressLine1"];
			$addressString .= ", " . $address["postalcode"] . " " . $address["postalcity"];
			$addressString .= ", " . $address["country"] . "</li>";

			$addressString .= "\n\t<li>Kontakt: " . $address["contact"]["name"] 
							. ", " . $address["contact"]["phoneNumber"] . " <span title=\"Bekreftet?\">(" . ( $phoneNumberConfirmed = $address["contact"]["phoneNumberConfirmed"] == "true" ? "Bekreftet" : "Ikke bekreftet") . ")</span>"
							. ", " . $address["contact"]["email"] . " <span title=\"Bekreftet?\">(" . ( $emailConfirmed = $address["contact"]["emailConfirmed"] == "true" ? "Bekreftet" : "Ikke bekreftet") . ")</span></li>";
							
			$addressString .= "\n\t<li>Leveringsinformasjon: " 
							. ( $pickupPoint = strlen($address["deliveryinformation"]["pickupPoint"]) > 0 ? $address["deliveryinformation"]["pickupPoint"] : "" )
							. ( $additionalAddressInfo = strlen($address["deliveryinformation"]["additionalAddressInfo"]) > 0 ? ", " . $address["deliveryinformation"]["additionalAddressInfo"] : "" )
							. ( $deliveryTime = strlen($address["deliveryinformation"]["Deliverytime"]) > 0 ? ", " . $address["deliveryinformation"]["Deliverytime"] : "" )
							. "</li>";
							
			strlen($address["tags"]) > 0 ? $addressString .= "<li>Tags: " . $address["tags"] . "</li>" : $addressString = $addressString;
		}
		
		$addressString .= "</ul>";
		
		return $addressString;
	}
	
	function getDateTimeFromTimestamp($timestamp) {
//		echo round($timestamp/1000);
		return gmdate("Y-m-d H:i:s", round($timestamp/1000));
	}
	
	function debug($fn, $info) {
		if(DEBUG) {
			echo "<p><span class=\"debugfn\">" . $fn . ":</span> " . $info . "</p>";
			error_log($info);
		}
	}
}
?>