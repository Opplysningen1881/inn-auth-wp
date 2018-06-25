<?php

$parse_uri = explode( "wp-content", __FILE__ );
require_once($parse_uri[0] . "wp-load.php");
require_once("inn-Log.php");
require_once("inn-ApplicationSession.php");
require_once("inn-ApplicationToken.php");
require_once("inn-UserToken.php");

class inn_authenticate {

	public function __construct() {
		$this->options = get_option("inn-auth_options");
		$this->appsession = new inn_ApplicationSession();
		$this->apptoken = new inn_ApplicationToken(); // We need an apptoken to build the the $consenturl
		$this->utoken = new inn_UserToken();
		$this->log = new inn_Log();
		$this->log->info("class inn_authenticate instantiated");
	}

	function authenticate($userticket, $redirectURI) {
		$this->appsession->verifyAppSession();

		$usertoken = $this->utoken->getUserToken($userticket);
		$this->log->info("authenticate, got usertoken: " . $usertoken);

		if (strlen($usertoken) == 0) {
			$this->log->warn("authenticate: No usertoken.");
			die ("authenticate: No usertoken.");
		}

		if ( strlen($this->utoken->getUserTokenId($usertoken)) > 0 && $this->checkApplicationHasRole($usertoken) == 1 ) {

			$wp_user_id = username_exists($this->utoken->getUserName($usertoken));

			if(!$wp_user_id and email_exists($this->utoken->getEmail($usertoken)) == false) {
				$wp_user_id = $this->wpRegisterUser($usertoken);
			}

			$authenticated = $this->wpSignonUser($wp_user_id, $usertoken);
		} else {
			$consenturl = $this->getConsentURL($userticket, $redirectURI);

			$this->log->info("User has no role for this application. Will ask for consent.");
			echo "<p>We need your consent: <a href=\"" . $consenturl . "\">" . $consenturl . "</a></p>";
			wp_redirect($consenturl, 302);
		}

		## debug info
		$this->log->info("authenticate, wp_user_id for " . $this->utoken->getEmail($usertoken) . ": " . $wp_user_id);
		$this->log->info("authenticate, ApplicationHasRole: " . $this->checkApplicationHasRole($usertoken));
		$this->log->info("authenticate, authenticated: " . (string)$authenticated);

		return $authenticated;
	}

	function wpRegisterUser($usertoken) {
		// register new user in WP

		if (strlen($usertoken) == 0) {
			$this->log->warn("wpRegisterUser: No usertoken.");
			die ("wpRegisterUser: No usertoken.");
		}

		$user_name = $this->utoken->getUserName($usertoken);
		$user_email = $this->utoken->getEmail($usertoken);

		$password = $this->createPassword($usertoken);

		$this->log->warn(sprintf("wpRegisterUser: Registering a new Wordpress user. User_name=%s, password=HIDDEN, user_email=%s", $user_name, $user_email));
		$user_id = wp_create_user( $user_name, $password, $user_email );

		if ( is_wp_error( $user_id ) )
			$this->log->info("wpRegisterUser. wp_create_user error: " . $user_id->get_error_message());

		$userdata = array(
			"ID" => $user_id,
			"user_nicename" => $this->utoken->getFirstName($usertoken) . " " . $this->utoken->getLastName($usertoken),
			"display_name" => $this->utoken->getFirstName($usertoken) . " " . $this->utoken->getLastName($usertoken),
			"first_name" => $this->utoken->getFirstName($usertoken),
			"last_name" => $this->utoken->getLastName($usertoken)
		);
		$user_id = wp_update_user($userdata);

		update_user_meta( $user_id, 'adresse', (string)$this->utoken->getAddress($usertoken) );
		update_user_meta( $user_id, 'telefon', (string)$this->utoken->getPhone($usertoken) );

		$this->log->info("wpRegisterUser. Registered a new Wordpress user. user_id: " . $user_id);

		return $user_id;
	}

	function wpSignonUser($user_id, $usertoken) {
		$user = get_user_by("ID", $user_id);

		wp_set_auth_cookie( $user_id, true );
		$uum = update_user_meta( $user_id, "inn_usertokenid", $this->utoken->getUserTokenId($usertoken) );
		$this->log->info("wpSignonUser(), update_user_meta: " . $uum);

		return true;
	}

	function wpSignoutUser($usertoken) {
		wp_logout();
		$this->log->info("wpSignoutUser(), user was signed out of Wordpress");

		return true;
	}

	function createPassword($usertoken) {
		$password = password_hash($usertoken, PASSWORD_DEFAULT);

		return $password;
	}

	function checkApplicationHasRole($usertoken) {
		$ut = simplexml_load_string($usertoken);
		$appHasRole = false;

		if($ut->application["ID"] == $this->options["app_id"])
			$appHasRole = true;

		$this->log->info("checkApplicationHasRole() usertoken: " . $usertoken);
		$this->log->info("checkApplicationHasRole() appHasRole: " . $appHasRole);

		return $appHasRole;
	}

	// Checks if the session id is still active
	function checkSessionId() {
		$result = false;
		$wpuserid = get_current_user_id();

		$apptokenstatus = $this->appsession->checkAppSession($this->apptoken->getAppToken());

		$this->log->info("checkSessionId() wpuserid: " . $wpuserid);

		if($wpuserid > 0) {
			$wpusertokenid = get_user_meta($wpuserid, "inn_usertokenid", true);
			$this->log->info("checkSessionId(), wpusertokenid: " . $wpusertokenid);

			if(strlen($wpusertokenid) > 0) {
				$usertoken = $this->utoken->getUserTokenById($wpusertokenid);
				$this->log->info("checkSessionId(), usertoken: " . $usertoken);

				if(strlen($this->utoken->getUserTokenId($usertoken)) > 0) {
					$result = true;
					$this->log->info("checkSessionId(), getUserTokenId: " . $this->utoken->getUserTokenId($usertoken));
				}
			}
		}

		$this->log->info("checkSessionId() result: " . $result);

		return $result;
	}

	function getConsentURL($userticket, $redirectURI) {

		$consenturl = sprintf("%s/%s?userticket=%s&redirectURI=%s",
			$this->options["consent_url"],
			$this->apptoken->getAppTokenID($this->apptoken->getAppToken()),
			$userticket,
			$redirectURI
		);

		return $consenturl;
	}
}
?>
