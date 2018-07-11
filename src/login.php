<?php
require_once("inn-authenticate.php");
require_once("inn-Log.php");
require_once("inn-UserToken.php");

define("INN_AUTH_PLUGIN_DIR", trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) ) );

$auth = new inn_authenticate();
$log = new inn_Log();
$options = get_option("inn-auth_options");

$log->info("SSO URL: " . $options["sso_url"]);
$log->info("STS URL: " . $options["sts_url"]);
$log->info("INN_AUTH_PLUGIN_DIR: " . INN_AUTH_PLUGIN_DIR);

// redirectURI: The wordpress destination in which to return to after successful SSO
global $wp;
$redirectURI = home_url($wp->request);

// redirecturl: The INN SSO LOGIN url to redirect to. Needs to include the redirectURI to return to
$redirecturl = sprintf("%s/login?%s",
	$options["sso_url"],
	http_build_query(array(
		"UserCheckout" => $_GET["UserCheckout"],
		"redirectURI" => $redirectURI
	))
);

$log->info("Login redirecturl: " . $redirecturl);

if (!isset($_GET["userticket"])) {
	echo "<p>Start redirect: <a href=\"" . $redirecturl . "\">" . $redirecturl . "</a></p>";

	wp_redirect($redirecturl, 302);
}
else {
	$userticket = $_GET["userticket"];
	$log->info("Login: userticket=" . $userticket);

	$res = $auth->authenticate($userticket, $redirectURI);

	if($res) {
		echo "<p style=\"color:green;\">Autentisert!</p>";
		echo "<p>G&aring; videre til <a href=\"" . $redirectURI . "\">" . $redirectURI . "</p>";
		$log->warn("Login: Authenticated! redirecting user to " . $redirectURI);
		wp_redirect($redirectURI);
	} else {
		$log->warn("Login: Not authenticated!");
		echo "<p><span style=\"color:red;\">Ikke autentisert.</span> " . $res . "</p>";
	}
}
?>
