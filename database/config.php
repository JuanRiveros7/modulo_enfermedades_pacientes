<?php
$host = "localhost";    
$user = "root";           
$pass = "";    
$db   = "enfermedades_pacientes";

$con = new mysqli($host, $user, $pass, $db);

if ($con->connect_error) {
    die("Conexión fallida: " . $con->connect_error);
}

$con->set_charset("utf8");
?>