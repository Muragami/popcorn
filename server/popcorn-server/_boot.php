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
if ($conn->multi_query($sql) === TRUE) { $ret[] = "ok : classes loaded as things"; }
  else { $ret[] = "err: " . $conn->error;  }
while($conn->next_result());
$i = 0;
$sql = "";
while ($i < count($cls)) {
  $id = $i + 1;
  $line = $cls[$i];
  $sql .= "INSERT INTO ps01_class (id,data) VALUES ($id,'$line'); ";
  $i += 1;
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
