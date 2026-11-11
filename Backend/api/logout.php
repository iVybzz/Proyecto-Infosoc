<?php
require '../config/conexion.php';
header('Content-Type: application/json');

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión físicamente
session_destroy();

echo json_encode(["status" => "ok", "message" => "Sesión cerrada correctamente"]);
?>