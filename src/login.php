<?php
require_once "inn-authenticate.php";
$wpsourceurl = "";

if (isset($_GET["wpsourceurl"])) {
	$wpsourceurl = $_GET["wpsourceurl"];
}

if (!isset($_GET["userticket"])) {
	$options = get_option("inn-auth_options");
//	$redirecturl = $options["sso_url"] . "/login?redirectURI=" . INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . $wpsourceurl;
//		$redirecturl = "https://inn-prod-sso.capra.cc/oidsso/login?UserCheckout=" . $_GET["UserCheckout"] . "&redirectURI=" . INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . $wpsourceurl;

	$params = array("redirectURI" => INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . $wpsourceurl,
		"UserCheckout" => $_GET["UserCheckout"]);

//	$redirecturl = "https://inn-prod-sso.capra.cc/oidsso/login?" . http_build_query($params);
	$redirecturl = "https://sso.opplysningen.no/oidsso/login?" . http_build_query($params);
	
	
	echo "<p>Start redirect: <a href=\"" . $redirecturl . "\">" . $redirecturl . "</a></p>";
	
	wp_redirect($redirecturl, 302);
}
else {
	$userticket = $_GET["userticket"];
	
	$auth = new inn_authenticate();
	
	$usertoken = $auth->getUserToken($userticket);
	if (strlen($usertoken) == 0) die ("No usertoken.");
	
	$res = $auth->authenticate($usertoken);
	
//	print_r($res);
	
	if($res) {
		echo "<p style=\"color:green;\">Autentisert!</p>";
		echo "<p>G&aring; videre til <a href=\"" . get_bloginfo('wpurl') . $wpsourceurl . "\">" . get_bloginfo('wpurl') . $wpsourceurl . "</p>";
		wp_redirect(get_bloginfo('wpurl') . $wpsourceurl);
		
		exit;
	} else {
		echo "<p><span style=\"color:red;\">Ikke autentisert.</span> " . $res . "</p>";
	}
}
?>