<?php
// utility functions and logic for popcorn server
// setup the database for use!
if ($conn === NULL) {
  require 'cfg.php';
  $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
}

// get a bundle of data from storage merged into an array
function psGetBundle($conn,$name,&$arr,$tab = 'ps01_data') {
  if ($result = $conn->query("SELECT name, data FROM $tab WHERE bundle='$name';"))
  {
    while ($row = $result->fetch_array(MYSQLI_BOTH)) {
      $arr[$row['name']] = json_decode($row['data']);
    }
    return "ok :";
  } else { return "err: " . $conn->error;  }
}

// put a bundle of data back into storage
function psPutBundle($conn,$name,&$arr,$tab = 'ps01_data') {
  $sql = "INSERT INTO $tab (bundle,name,data) VALUES ";
  foreach ($arr as $k => $v) {
    $sql .= "('" . $name . "','" . $k . "','" . json_encode($v) . "'), ";
  }
  $sql = substr($sql,0,-1); // trim the trailing ,
  $sql .= " ON DUPLICATE KEY UPDATE;";
  if ($conn->query($sql)) { return "ok :"; } else { return "err: " . $conn->error;  }
}


?>
