<?php
/*
 * Plugin Name:       ~popcorn server (Ridgway)
 * Plugin URI:        http://popcorn.wishray.com/
 * Description:       A server for the popcorn pretty chat client
 * Version:           0.1 (alpha)
 * Author:            muragami
 * Author URI:        https://muragami.wishray.com
 * Text Domain:       popcorn-server
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Domain Path:       /languages
 */

// only work if we are called from wordpress, no shenanigans!
if (!defined('WPINC')) { die; }

// *****************************************************************
// config

function pc_getCfg() {
  static $pc = array(
    "pc_release" => "Ridgway",
    "pc_version" => "0.1",
    "ver_major" => 0,
    "ver_minor" => 1,
    "pc_runs" => "php7",
    "pc_replies" => "json",
    "msg_cap" => 600 );
    return $pc;
}

// *****************************************************************
// our functions!

function pc_hello(WP_REST_Request $request) {
  $opts = get_option( 'popcorn_server_option_name' ); // get cfg options
  // internal config data
  $ret = pc_getCfg();
  // return information about this popcorn server!
  $ret['charset'] = DB_CHARSET;
  $ret['owner'] = $opts['server_owner'];
  // setup the database for use!
  $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  if ($conn->connect_error) { return new WP_Error('pc_init() db connect', $conn->connect_error, array('status' => 400)); }
  // are we initialized?
  if ($conn->query("SELECT data FROM ps01_class WHERE id=1") === TRUE) {
    $ret['initialized'] = true;
  } else { $ret['initialized'] = false; }
  return $ret;
}

// init the database structure
function pc_init(WP_REST_Request $request) {
  $ret = "";
  // setup the database for use!
  $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  if ($conn->connect_error) { return new WP_Error('pc_init() db connect', $conn->connect_error, array('status' => 400)); }

  $sql = "CREATE TABLE ps01_thing (
  id BIGINT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  data JSON NOT NULL,
  class BIGINT(8) UNSIGNED NOT NULL,
  born TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )";

  if ($conn->query($sql) === TRUE) { $ret .= "ok: ps01_thing table created; "; }
    else { $ret .= "err: " . $conn->error . "; ";  }

  $sql = "CREATE TABLE ps01_class (
  id BIGINT(8) UNSIGNED PRIMARY KEY,
  data JSON NOT NULL,
  parent BIGINT(8) UNSIGNED
  )";

  if ($conn->query($sql) === TRUE) { $ret .= "ok: ps01_class table created; "; }
    else { $ret .= "err: " . $conn->error . "; ";  }

  $sql = "CREATE TABLE ps01_group (
  id BIGINT(8) UNSIGNED PRIMARY KEY,
  data JSON NOT NULL,
  parent BIGINT(8) UNSIGNED
  )";

  if ($conn->query($sql) === TRUE) { $ret .= "ok: ps01_group table created; "; }
    else { $ret .= "err: " . $conn->error . "; ";  }

  $conn->close();
  return $ret;
}

// boot with our default data, only if we have the proper code!
function pc_boot(WP_REST_Request $request) {
  $opts = get_option( 'popcorn_server_option_name' ); // get cfg options
  $ret = "";
  // setup the database for use!
  $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  if ($conn->connect_error) { return new WP_Error('pc_init() db connect', $conn->connect_error, array('status' => 400)); }
  // make sure we are the owner
  if ($request['code'] != $opts['unique_code']) {
    return new WP_Error('pc_boot() auth', "bad code token", array('status' => 400)); }
  // clear and reset tables
  $sql = "TRUNCATE TABLE ps01_thing;TRUNCATE TABLE ps01_class;TRUNCATE TABLE ps01_group;";
  if ($conn->multi_query($sql) === TRUE) { $ret .= "ok: all tables reset; "; }
    else { $ret .= "err: " . $conn->error . "; ";  }
  while($conn->next_result());
  // insert minimal data needed
  // first classes
  $sql = "";
  $i=0;
  $cls = array();
  $lines=explode("\n",file_get_contents(WP_PLUGIN_DIR."/popcorn-server/class.json"));
  foreach($lines as $line) {
    $line = trim($line);
    if (substr($line, -1) == ',') { $line = substr($line,0,-1); }
    if ($line != "[" && $line != "]" && $line != "") {
      $sql .= "INSERT INTO ps01_thing (data,class) VALUES ('$line','0'); ";
      $cls[$i] = $line;
      $i += 1;
    }
  }
  if ($conn->multi_query($sql) === TRUE) { $ret .= "ok: classes loaded as things; "; }
    else { $ret .= "err: " . $conn->error . "; ";  }
  while($conn->next_result());
  $i = 0;
  $sql = "";
  while ($i < count($cls)) {
    $id = $i + 1;
    $line = $cls[$i];
    $sql .= "INSERT INTO ps01_class (id,data) VALUES ($id,'$line'); ";
    $i += 1;
  }
  if ($conn->multi_query($sql) === TRUE) { $ret .= "ok: classes registered as classes; "; }
    else { $ret .= "err: " . $conn->error . "; ";  }
  while($conn->next_result());
  $conn->close();
  //$ret .= $sql;
  return $ret;
}

// *****************************************************************
// add routes to WP's REST API!

add_action( 'rest_api_init', function () {
  register_rest_route( 'popcorn-server/v1', '/hello', array(
    'methods' => 'GET',
    'callback' => 'pc_hello',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route( 'popcorn-server/v1', '/init', array(
    'methods' => 'GET',
    'callback' => 'pc_init',
    'permission_callback' => '__return_true',
  ) );
  register_rest_route( 'popcorn-server/v1', '/boot', array(
    'methods' => 'GET',
    'callback' => 'pc_boot',
    'permission_callback' => '__return_true',
  ) );
} );

// *****************************************************************
// supply a basic configuration page in the admin area

/**
 * Generated by the WordPress Option Page generator
 * at http://jeremyhixon.com/wp-tools/option-page/
 */

class PopcornServer {
	private $popcorn_server_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'popcorn_server_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'popcorn_server_page_init' ) );
	}

	public function popcorn_server_add_plugin_page() {
		add_menu_page(
			'Popcorn Server', // page_title
			'Popcorn Server', // menu_title
			'manage_options', // capability
			'popcorn-server', // menu_slug
			array( $this, 'popcorn_server_create_admin_page' ), // function
			'dashicons-admin-generic', // icon_url
			2 // position
		);
	}

	public function popcorn_server_create_admin_page() {
		$this->popcorn_server_options = get_option( 'popcorn_server_option_name' ); ?>

		<div class="wrap">
			<h2>Popcorn Server</h2>
			<p>Configuration for ~popcorn-server.</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'popcorn_server_option_group' );
					do_settings_sections( 'popcorn-server-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function popcorn_server_page_init() {
		register_setting(
			'popcorn_server_option_group', // option_group
			'popcorn_server_option_name', // option_name
			array( $this, 'popcorn_server_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'popcorn_server_setting_section', // id
			'Settings', // title
			array( $this, 'popcorn_server_section_info' ), // callback
			'popcorn-server-admin' // page
		);

		add_settings_field(
			'unique_code', // id
			'Unique Code', // title
			array( $this, 'unique_code_callback' ), // callback
			'popcorn-server-admin', // page
			'popcorn_server_setting_section' // section
		);

    add_settings_field(
			'server_owner', // id
			'Server Owner', // title
			array( $this, 'server_owner_callback' ), // callback
			'popcorn-server-admin', // page
			'popcorn_server_setting_section' // section
		);
	}

	public function popcorn_server_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['unique_code'] ) ) {
			$sanitary_values['unique_code'] = sanitize_text_field( $input['unique_code'] );
		}
    if ( isset( $input['server_owner'] ) ) {
			$sanitary_values['server_owner'] = sanitize_text_field( $input['server_owner'] );
		}
		return $sanitary_values;
	}

	public function popcorn_server_section_info() {

	}

	public function unique_code_callback() {
		printf(
			'<input class="regular-text" type="text" name="popcorn_server_option_name[unique_code]" id="unique_code" value="%s">',
			isset( $this->popcorn_server_options['unique_code'] ) ? esc_attr( $this->popcorn_server_options['unique_code']) : ''
		);
	}

  public function server_owner_callback() {
		printf(
			'<input class="regular-text" type="text" name="popcorn_server_option_name[server_owner]" id="server_owner" value="%s">',
			isset( $this->popcorn_server_options['server_owner'] ) ? esc_attr( $this->popcorn_server_options['server_owner']) : ''
		);
	}
}
if ( is_admin() )
	$popcorn_server = new PopcornServer();

/*
 * Retrieve this value with:
 * $popcorn_server_options = get_option( 'popcorn_server_option_name' ); // Array of All Options
 * $unique_code_0 = $popcorn_server_options['unique_code_0']; // Unique Code
 */

?>
