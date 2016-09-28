<?php
require_once("inn-authenticate.php");
require_once("inn-Log.php");
require_once( "inn-UserToken.php" );

$wpsourceurl = "";

$log = new inn_Log();

if (isset($_GET["wpsourceurl"])) {
	$wpsourceurl = $_GET["wpsourceurl"];
}

if (!isset($_GET["userticket"])) {
	$options = get_option("inn-auth_options");
//	$redirecturl = $options["sso_url"] . "/login?redirectURI=" . INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . $wpsourceurl;
//		$redirecturl = "https://inn-prod-sso.capra.cc/oidsso/login?UserCheckout=" . $_GET["UserCheckout"] . "&redirectURI=" . INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . $wpsourceurl;

	$params = array("redirectURI" => INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . $wpsourceurl,
		"UserCheckout" => $_GET["UserCheckout"]);

	$redirecturl = "https://sso.opplysningen.no/oidsso/login?" . http_build_query($params);
	
	echo "<p>Start redirect: <a href=\"" . $redirecturl . "\">" . $redirecturl . "</a></p>";
	
	wp_redirect($redirecturl, 302);
}
else {
	$userticket = $_GET["userticket"];
	$log->info("Login: userticket=" . $userticket);
	
	$auth = new inn_authenticate();
	$utoken = new inn_UserToken();
	
	$usertoken = $utoken->getUserToken($userticket);
	
	if (strlen($usertoken) == 0) {
		$log->warn("Login: No usertoken");
		die ("No usertoken.");
	}
	
	$res = $auth->authenticate($usertoken);
	
	if($res) {
		echo "<p style=\"color:green;\">Autentisert!</p>";
		echo "<p>G&aring; videre til <a href=\"" . get_bloginfo('wpurl') . $wpsourceurl . "\">" . get_bloginfo('wpurl') . $wpsourceurl . "</p>";
		$log->warn("Login: Authenticated! redirecting user to " . get_bloginfo('wpurl') . $wpsourceurl);
		wp_redirect(get_bloginfo('wpurl') . $wpsourceurl);
	} else {
		$log->warn("Login: Not authenticated!");
		echo "<p><span style=\"color:red;\">Ikke autentisert.</span> " . $res . "</p>";
	}
}
?>