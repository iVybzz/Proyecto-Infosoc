<?php
//CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

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
    echo json_encode(["error" => "Error crítico de conexión: " . $e->getMessage()]);
    exit();
}
?>
