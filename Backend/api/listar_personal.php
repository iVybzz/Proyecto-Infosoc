<?php
require '../config/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

try {
    // No traemos la contraseña, solo los datos públicos para la lista
    $sql = "SELECT id_usuario, rut, nombre_completo FROM personal ORDER BY nombre_completo ASC";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll();
    
    echo json_encode($usuarios);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error BD: " . $e->getMessage()]);
}
?>