<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "aruc_bd";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8"); 
?>
