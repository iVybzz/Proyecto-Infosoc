<?php

$host = "sql200.infinityfree.com";
$dbname = "if0_43211338_infosoc"; 
$username = "if0_43211338";         
$password = "Infosoc10";         

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
