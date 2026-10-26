<?php
//CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

$config = require __DIR__ . '/config.php';
$host = $config['db_host'];
$dbname = $config['db_name'];
$username = $config['db_user'];
$password = $config['db_pass'];

try {
    //dsn
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
        PDO::ATTR_EMULATE_PREPARES   => false,             
    ];

    $pdo = new PDO($dsn, $username, $password, $opciones);
    
} catch (PDOException $e) {
    // por si falla
    error_log('[' . date('Y-m-d H:i:s') . '] Error BD: ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/error.log');

    //mensaje para el usuario
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error de conexión a la base de datos.']);
    exit();
}
?>
