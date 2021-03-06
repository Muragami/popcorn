<?php
// only work if we are called from wordpress, no shenanigans!
if (!defined('WPINC')) { die; }
$opts = get_option( 'popcorn_server_option_name' ); // get cfg options
// internal config data
$ret = array();
// return information about this popcorn server!
$ret['charset'] = DB_CHARSET;
$ret['owner'] = $opts['server_owner'];
// setup the database for use!
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { return new WP_Error('pc_init() db connect', $conn->connect_error, array('status' => 400)); }
// are we initialized?
if (!$conn->query("SELECT data FROM ps01_class WHERE id=1")) {
  $ret['initialized'] = false;
} else { $ret['initialized'] = true;  while($conn->next_result()); }
// let's make sure we have a valid cfg.php here
if (!file_exists(WP_PLUGIN_DIR.'/popcorn-server/cfg.php')) {
  // store config data we need for later exec.php calls
  $cfg = "<?php define('DB_NAME','" . constant('DB_NAME') . "');";
  $cfg .= " define('DB_HOST','" . constant('DB_HOST') . "');";
  $cfg .= " define('DB_USER','" . constant('DB_USER') . "');";
  $cfg .= " define('DB_PASSWORD','" . constant('DB_PASSWORD') . "');";
  $cfg .= " define('DB_CHARSET','" . constant('DB_CHARSET') . "');";
  $cfg .= " define('PS_UCODE','" . $opts['unique_code'] . "');";
  $cfg .= " define('PS_OWNER','" . $opts['server_owner'] . "');";
  $cfg .= " ?>";
  file_put_contents(WP_PLUGIN_DIR.'/popcorn-server/cfg.php',$cfg);
}
// read the config data if we are initialized
if ($ret['initialized']) {
  require 'pop.php';
  psGetBundle($conn,'cfg',$ret);
}
?>
