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
  return new WP_Error('pc_boot() auth', "bad code token", array('status' => 400)); }
// clear and reset tables
$sql = "TRUNCATE TABLE ps01_thing;TRUNCATE TABLE ps01_class;TRUNCATE TABLE ps01_group;TRUNCATE TABLE ps01_data;";
if ($conn->multi_query($sql) === TRUE) { $ret[] = "ok : all tables reset"; }
  else { $ret[] = "err: " . $conn->error;  }
while($conn->next_result());
// insert minimal data needed
// first classes
$sql = "";
$dat = json_decode(file_get_contents(WP_PLUGIN_DIR."/popcorn-server/class.json"),true);
foreach ($dat as $ln) {
  $line = json_encode($ln);
  $n = $ln['name'];
  if (array_key_exists('within',$ln)) { $w = hexdec($ln['within']);
  } else { $w = 0; }
  $sql .= "INSERT INTO ps01_thing (data,class,name,within) VALUES ('$line','0','$n','$w'); ";
}

if ($conn->multi_query($sql) === TRUE) { $ret[] = "ok : classes loaded as things"; }
  else { $ret[] = "err: " . $conn->error;  }
while($conn->next_result());
$sql = "";
$id = 1;
foreach ($dat as $ln) {
  $line = json_encode($ln);
  $p = hexdec($ln['parent']);
  $sql .= "INSERT INTO ps01_class (id,data,parent) VALUES ($id,'$line',$p); ";
  $id += 1;
}
if ($conn->multi_query($sql) === TRUE) { $ret[] = "ok: classes registered as classes"; }
  else { $ret[] = "err: " . $conn->error;  }
while($conn->next_result());
// insert basic data into data
$sql = "INSERT INTO ps01_data (bundle,name,data) VALUES ";
$data = json_decode(file_get_contents(WP_PLUGIN_DIR.'/popcorn-server/data.json'));
foreach ($data as $entry) {
  $ename = $entry[0] . '.' . $entry[1];
  $sql .= " ('" . $entry[0] . "','" . $ename . "','" . $entry[2] . "'),";
}
$sql = substr($sql,0,-1) . ';'; // trim the trailing ,
if ($conn->multi_query($sql) === TRUE) { $ret[] = "ok: data added"; }
  else { $ret[] = "err: " . $conn->error;  }
while($conn->next_result());
$conn->close();
//$ret .= $sql;
?>
