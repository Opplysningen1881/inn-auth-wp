<?php
/**
 * @package inn-auth
 * @version 1.1
 */
/*
Plugin Name: INN Auth
Plugin URI:
Description: Authorize and register users with Opplysningen INN
Author: Opplysningen 1881 AS
Author URI: http://inn.opplysningen.no
Version: 1.1
*/

defined( 'ABSPATH' ) or die( '1 No script kiddies please!' );

$parse_uri = explode( 'wp-content', __FILE__ );
require_once( $parse_uri[0] . 'wp-load.php' );

require_once "inn-authenticate.php";
require_once "inn-options.php";
require_once "inn-UserToken.php";
require_once "inn-Log.php";

define("INN_AUTH_PLUGIN_DIR", trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) ) );

$options = get_option("inn-auth_options");

wp_register_style('inn_styles', plugins_url('style.css',__FILE__ ));
wp_register_style('bootstrap_styles', plugins_url('vendor/bootstrap-4.0.0/css/bootstrap.min.css',__FILE__ ));
wp_register_script('bootstrap_scripts', plugins_url('vendor/bootstrap-4.0.0/js/bootstrap.min.js',__FILE__ ));

function inn_styles() {
	wp_enqueue_style('inn_styles');
	wp_enqueue_style('bootstrap_styles');
	wp_enqueue_script('bootstrap_scripts');
}

add_action( 'wp_enqueue_scripts','inn_styles');

function inn_authenticate() {
	global $options;

	if (isset($_GET["userticket"])) {
		$userticket = $_GET["userticket"];

		$auth = new inn_authenticate();
		$redirectURI = $options["app_url"] . strtok($_SERVER["REQUEST_URI"], "?");

		// die("inn_authenticate redirectURI: " . $redirectURI);

		$res = $auth->authenticate($userticket, $redirectURI);
	}
}

add_action("wp", "inn_authenticate");


function inn_loginButton($atts) {
	global $options;
	$auth = new inn_authenticate();

	$params = array(
		"UserCheckout" => "false",
		// "redirectURI" => INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . strtok($_SERVER["REQUEST_URI"], "?"),
		"redirectURI" => $options["app_url"] . strtok($_SERVER["REQUEST_URI"], "?"),
	);

	$a = shortcode_atts(array(
		"text" => "Logg INN",
		"href" => sprintf("%s/login?%s", $options["sso_url"],  http_build_query($params)),
	), $atts);

	if ( $auth->checkSessionId() ) {
		$button = sprintf("<a href=\"%s\" class=\"%s\">%s</a>", wp_logout_url(home_url()), $options["button_style"], "Logg ut");
	} else {
		$button = sprintf("<a href=\"%s\" class=\"%s\">%s</a>", $a["href"], $options["button_style"], $a["text"]);
	}

	return $button;
}

add_shortcode("inn-login", "inn_loginButton");
add_action("login_form", "inn_loginButton");

function inn_checkoutButton($atts) {
	global $options;

	$params = array(
		"UserCheckout" => "true",
		// "redirectURI" => INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . strtok($_SERVER["REQUEST_URI"], "?")
		"redirectURI" => $options["app_url"] . strtok($_SERVER["REQUEST_URI"], "?"),
	);

	$a = shortcode_atts(array(
		"text" => "Velg adresse med INN",
		"href" => sprintf("%s/login?%s", $options["sso_url"],  http_build_query($params)),
	), $atts);

	$button = sprintf("<a href=\"%s\" class=\"%s\">%s</a>", $a["href"], $options["button_style"], $a["text"]);

	return $button;
}

add_shortcode("inn-checkout", "inn_checkoutButton");


function inn_checkSession($atts) {
	global $options;

	$params = array(
		"SessionCheck" => "true",
		// "redirectURI" => INN_AUTH_PLUGIN_DIR . "/login.php?wpsourceurl=" . strtok($_SERVER["REQUEST_URI"], "?")
		"redirectURI" => $options["app_url"] . strtok($_SERVER["REQUEST_URI"], "?"),
	);

	$a = shortcode_atts(array(
		"text" => "INN SessionCheck",
		"href" => sprintf("%s/login?%s", $options["sso_url"],  http_build_query($params)),
	), $atts);

	$button = sprintf("<a href=\"%s\" class=\"%s\">%s</a>", $a["href"], $options["button_style"], $a["text"]);

	return $button;
}

add_shortcode("inn-checkSessionButton", "inn_checkSession");

function inn_userMetaFirstName(){
	$firstname = get_user_meta(get_current_user_id(), "first_name", true);
	return $firstname;
}

add_shortcode("inn-firstname", "inn_userMetaFirstName");


function inn_userMetaLastName(){
	$lastname = get_user_meta(get_current_user_id(), "last_name", true);
	return $lastname;
}

add_shortcode("inn-lastname", "inn_userMetaLastName");


function inn_userMetaFullName(){
	$fullname = get_user_meta(get_current_user_id(), "first_name", true) . " " . get_user_meta(get_current_user_id(), "last_name", true);
	return $fullname;
}

add_shortcode("inn-fullname", "inn_userMetaFullName");


function inn_userMetaAddress(){
	$utoken = new inn_UserToken();

	$addressJSON = get_user_meta(get_current_user_id(), "adresse", true);
	$formattedaddress = $utoken->formatDeliveryaddress($addressJSON);

	return $formattedaddress;
}

add_shortcode("inn-address", "inn_userMetaAddress");


function inn_userMetaPhone(){
	$phone = get_user_meta(get_current_user_id(), "telefon", true);
	return $phone;
}

add_shortcode("inn-phone", "inn_userMetaPhone");

function inn_printMyToken(){
	$auth = new inn_authenticate();
	$utoken = new inn_UserToken();

	if($auth->checkSessionId()) {
		$tokenstring = $utoken->printMyTokenFormatted();
	} else {
		$tokenstring = "<p>Fant ingen aktiv INN-sesjon for denne WP-brukeren.</p>";
	}

	echo $tokenstring;

	return;
}

add_shortcode("inn-printmytoken", "inn_printMyToken");



function add_inn_user_profile_fields( $user ) {
?>
	<div id="innfields">
	<h3><?php _e('INN Profildata', 'inn-auth'); ?></h3>
		<p>
		<label for="inn_username"><?php _e('Brukernavn', 'inn-auth'); ?>
			<input type="text" name="inn_username" id="inn_username" value="<?php echo esc_attr( get_the_author_meta( 'inn_username', $user->ID ) ); ?>" class="regular-text" />
		</label>
	</p>
	</div>
<?php }

add_action( 'show_user_profile', 'add_inn_user_profile_fields' );
add_action( 'edit_user_profile', 'add_inn_user_profile_fields' );
add_action( 'register_form', 'add_inn_user_profile_fields' );


function save_inn_user_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return FALSE;

	update_user_meta( $user_id, 'inn_username', trim( $_POST['inn_username'] ) );
}

add_action( 'personal_options_update', 'save_inn_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_inn_user_profile_fields' );
add_action( 'register_post', 'save_inn_user_profile_fields' );

// Scheduling app sessions

function appsession_renewal( $schedules ) {
    $schedules['twentyfive_minutes'] = array(
        'interval' => 1500,
        'display'  => esc_html__( 'Every Twenty Five Minutes' ),
    );

    return $schedules;
}

add_filter( 'inn_cron_schedules', 'appsession_renewal' );

function inn_appsession_cron_exec() {
	$log = new inn_Log();
	$appsession = new inn_ApplicationSession();

	if($appsession->checkAppSession() == "expired") {
		$log->info("inn_appsession_cron_exec: AppSession has expired. Initializing new session.");
		$appsession->initializeAppSession();
	} else {
		$log->info("inn_appsession_cron_exec: AppSession has not expired. Renewing the session.");
		$appsession->renewAppSession();
	}

	if( !wp_next_scheduled( 'inn_appsession_cron_hook' ) ) {
		$sch = wp_schedule_event( time(), 'twentyfive_minutes', 'inn_appsession_cron_hook' );
		if($sch) {
			$log->info("inn_appsession_cron_exec: Schedule started: twentyfive_minutes, inn_appsession_cron_hook");
			$success = true;
		}
	}
}

add_action( 'inn_appsession_cron_hook', 'inn_appsession_cron_exec' );

?>
