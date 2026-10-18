<?php

$host = "localhost";
$dbname = "inventario???"; // cambiar al nombre de la base
$username = "root";         
$password = "";         

try {
    //dsn
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
        PDO::ATTR_EMULATE_PREPARES   => false,             
    ];

    $conexion = new PDO($dsn, $username, $password, $opciones);
    
} catch (PDOException $e) {
    // por si falla
    die("Error crítico de conexión: " . $e->getMessage());
}
?>