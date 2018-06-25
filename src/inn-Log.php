<?php

$parse_uri = explode( "wp-content", __FILE__ );
require_once( $parse_uri[0] . "wp-load.php" );

class inn_Log {
	public $debugModeOn = false;
	private $debugMode;

	function __construct() {
		$options = get_option("inn-auth_options");

		$this->debugMode = $options["debugmode"];
		$this->output(array("log_start", "debugMode: " . $this->debugMode));
	}

	function info($str) {
//		echo "<p class=\"log_info\">" . $str . "</p>";
		$this->output(array("info", $str));
	}

	function error($str) {
//		echo "<p class=\"log_error\">" . $str . "</p>";
		$this->output(array("error", $str));
	}

	function warn($str) {
//		echo "<p class=\"log_warn\">" . $str . "</p>";
		$this->output(array("warning", $str));
	}


	function output($data) {

		switch ($this->debugMode) {
			case "html":
				$this->output2HTML($data);
				break;
			case "console":
				$this->output2Console($data);
				break;
			case "all":
				$this->output2HTML($data);
				$this->output2Console($data);
				break;
//			default:
//				echo "\n<script>console.log('inn-auth: Please enter debug mode: possible values are \"console\", \"html\" or \"all\".');</script>";
		}

	}

	function output2HTML($data) {
		if ( is_array( $data ) ) {
			$cat = array_shift($data);
			$output = "\n<p><span class=\"log log_" . $cat . "\">" . $cat . "</span>" . implode( ', ', $data) . "</p>\n";
		} else {
			$output = "\n<p class=\"log\">inn-auth: " . $data . "</p>\n";
		}

		echo $output;
	}

	function output2Console($data) {
		if ( is_array( $data ) )
			$output = "\n<script>console.log( 'inn-auth: " . implode( ', ', $data) . "' );</script>";
		else
			$output = "\n<script>console.log( 'inn-auth: " . $data . "' );</script>";

		echo $output;
	}
}

?>
