<?php
// CORS: permitir el frontend desplegado y Live Server local.
$origenes_permitidos = [
    'https://proyecto-infosoc.vercel.app',
    'http://127.0.0.1:5500',
    'http://localhost:5500'
];

$origen = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origen, $origenes_permitidos, true)) {
    header("Access-Control-Allow-Origin: $origen");
    header('Vary: Origin');
}

// ESTA LÍNEA ES NUEVA Y CRÍTICA PARA EL LOGIN
header('Access-Control-Allow-Credentials: true'); 

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

// CONFIGURACIÓN DE SESIONES SEGURAS
session_set_cookie_params([
    'lifetime' => 86400, // 24 horas
    'path' => '/',
    'secure' => true,      // Obligatorio para enviar cookies entre distintos dominios
    'httponly' => true,    // Evita que el Javascript malicioso robe la cookie
    'samesite' => 'None'   // Permite que la cookie viaje de Vercel a AlwaysData
]);
session_start();

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
