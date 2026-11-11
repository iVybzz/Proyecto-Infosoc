<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');
require '../config/conexion.php';

try {
    if (isset($_GET['disponibilidad']) && !empty($_GET['disponibilidad'])) {
        $sql = "SELECT * FROM activo WHERE disponibilidad = :disponibilidad AND disponibilidad != 'Eliminado'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':disponibilidad' => $_GET['disponibilidad']]);
    } else {
        // Traemos todos los artículos EXCEPTO los eliminados
        $sql = "SELECT * FROM activo WHERE disponibilidad != 'Eliminado'";
        $stmt = $pdo->query($sql); 
    }
    
    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode($articulos);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "[Error Base de Datos] Error al listar los artículos: " . $e->getMessage()]);
}
?>
