<?php
// only work if we are called from wordpress, no shenanigans!
if (!defined('WPINC')) { die; }
$opts = get_option( 'popcorn_server_option_name' ); // get cfg options
$ret = array();
// setup the database for use!
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { return new WP_Error('pc_init() db connect', $conn->connect_error, array('status' => 400)); }

// make sure we are the owner
if ($request['code'] != $opts['unique_code']) {
  return new WP_Error('pc_init() auth', "bad code token", array('status' => 400)); }

// drop tables
$sql = "DROP TABLE IF EXISTS ps01_thing; DROP TABLE IF EXISTS ps01_class;
  DROP TABLE IF EXISTS ps01_group;  DROP TABLE IF EXISTS ps01_data;
  DROP TABLE IF EXISTS ps01_bank;";
if ($conn->multi_query($sql) === TRUE) { $ret[] = "ok : tables removed"; }
  else { $ret[] = "err: " . $conn->error;  }
while($conn->next_result());

// make tables
$sql = "CREATE TABLE ps01_thing (
id BIGINT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
data JSON NOT NULL,
class BIGINT(8) UNSIGNED NOT NULL,
name VARCHAR(255) UNIQUE,
within BIGINT(8),
born TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
KEY(name), KEY(within)
)";

if ($conn->query($sql) === TRUE) { $ret[] = "ok : ps01_thing table created"; }
  else { $ret[] = "err: " . $conn->error;  }

$sql = "CREATE TABLE ps01_class (
id BIGINT(8) UNSIGNED PRIMARY KEY,
data JSON NOT NULL,
parent BIGINT(8) UNSIGNED
)";

if ($conn->query($sql) === TRUE) { $ret[] = "ok : ps01_class table created"; }
  else { $ret[] = "err: " . $conn->error;  }

$sql = "CREATE TABLE ps01_group (
id BIGINT(8) UNSIGNED PRIMARY KEY,
data JSON NOT NULL,
parent BIGINT(8) UNSIGNED
)";

if ($conn->query($sql) === TRUE) { $ret[] = "ok : ps01_group table created"; }
  else { $ret[] = "err: " . $conn->error;  }

$sql = "CREATE TABLE ps01_data (
name VARCHAR(255) PRIMARY KEY, bundle VARCHAR(255),
data JSON NOT NULL, KEY(bundle))";

if ($conn->query($sql) === TRUE) { $ret[] = "ok : ps01_data table created"; }
  else { $ret[] = "err: " . $conn->error;  }

$sql = "CREATE TABLE ps01_bank (id BIGINT(8) UNSIGNED PRIMARY KEY, coins BIGINT(8) UNSIGNED NOT NULL)";

if ($conn->query($sql) === TRUE) { $ret[] = "ok : ps01_bank table created"; }
  else { $ret[] = "err: " . $conn->error;  }

$conn->close();
?>
