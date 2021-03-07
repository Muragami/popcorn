<?php

$dat = json_decode(file_get_contents('class.json'));
$i = 1;
foreach ($dat as $ln) {
  echo $i, ": ", json_encode($ln), "\r\n";
  $i += 1;
}
?>
