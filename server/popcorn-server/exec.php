<?php
require 'pop.php';
$ret = array();
if (!isset($_GET['do'])) {
  $ret[] = "err: missing do";
} else {
  $d = json_decode($_GET["do"],true);
  if ($d == NULL) { $ret[] = "err: improper JSON in do"; echo json_encode($ret); }
  if (!isset($d['cmd'])) {
    $ret[] = "err: no cmd field provided";
  } else {
    $fn = '__' . $d['cmd'] . '.php';
    if (!file_exists($fn)) {
      $ret[] = "err: bad cmd: " . $d['cmd'];
    } else {
      include($fn);
    }
  }
}
echo json_encode($ret);
?>
