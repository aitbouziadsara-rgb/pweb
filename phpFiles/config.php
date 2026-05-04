<?php
$host     = "localhost";
$dbname   = "hadj_tirage";
$username = "root";
$password = "slougui12";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("<b style='color:red;'>Connexion échouée : " . $conn->connect_error . "</b>");
}

$conn->set_charset("utf8");
?>