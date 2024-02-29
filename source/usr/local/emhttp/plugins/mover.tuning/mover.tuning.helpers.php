<?PHP
/*
 * Helper functions for the mover.tuning plugin
 */


$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

//require_once "$docroot/webGui/include/Helpers.php";

// Common constants
define('EMHTTP_PATH' ,              '/usr/local/emhttp');
define('CONFIG_PATH' ,              '/boot/config');
define('PLUGINS_PATH' ,             CONFIG_PATH . '/plugins');
define('MOVER_TUNING_PLUGIN_NAME',  'mover.tuning');
define('MOVER_TUNING_EMHTTP_PATH',  EMHTTP_PATH . '/plugins/' . MOVER_TUNING_PLUGIN);
define('MOVER_TUNING_PHP_FILE',     MOVER_TUNING_EMHTTP_PATH . '/' . MOVER_TUNING_PLUGIN_NAME . '.php');  
define('MOVER_TUNING_BOOT_PATH',    PLUGINS_PATH . '/' . MOVER_TUNING_PLUGIN);
define('MOVER_TUNING_FILE_PREFIX',  MOVER_TUNING_BOOT_PATH . '/' . MOVER_TUNING_PLUGIN_NAME . '.');
define('MOVER_TUNING_DEFAULTS_FILE',MOVER_TUNING_EMHTTP_PATH.'/'.MOVER_TUNING_PLUGIN.'.defaults');
define('MOVER_TUNING_CFG_FILE',     MOVER_TUNING_FILE_PREFIX . 'cfg');

define('PARITY_TUNING_DATE_FORMAT', 'Y M d H:i:s');
// Logging modes supported
define('PARITY_TUNING_LOGGING_BASIC' , '0');
define('PARITY_TUNING_LOGGING_DEBUG' , '1');
define('PARITY_TUNING_LOGGING_TESTING' ,'2');
// Targets for testing mode
define('PARITY_TUNING_LOGGING_SYSLOG' ,'0');
define('PARITY_TUNING_LOGGING_BOTH' ,'1');
define('PARITY_TUNING_LOGGING_TMP' ,'2');

// Load configuration information/settings
$moverTuningCfg = parse_ini_file(MOVER_TUNING_DEFAULTS_FILE);
 if (file_exists(MOVER_TUNING_CFG_FILE)) {
	$moverTuningCfg = array_replace($moverTuningCfg,parse_ini_file(MOVER_TUNING_CFG_FILE));
}

// Only want this line active while debugging to help clear up all PHP errors.
if ($moverTuningCfg['moverTuningLogging'] > 1) {
	// moverTuningLoggerTesting("Set PHP reporting level for TESTING mode");
	error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
}

function __($words) {
    //This function is a place holder for multi-language support in the future
    //Do nothing
    return $words;
}

?>