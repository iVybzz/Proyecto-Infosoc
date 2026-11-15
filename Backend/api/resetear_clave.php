<?php
require '../config/conexion.php';
header('Content-Type: application/json; charset=UTF-8');

// Validar sesión
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_usuario)) {
        // Encriptamos la clave temporal genérica: 123456
        $hash = password_hash("123456", PASSWORD_DEFAULT);

        $sql = "UPDATE personal SET contrasena = :hash WHERE id_usuario = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':hash' => $hash, ':id' => $data->id_usuario]);
        
        http_response_code(200);
        echo json_encode(["message" => "Clave reseteada a 123456 exitosamente."]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Falta el ID del usuario."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error BD: " . $e->getMessage()]);
}
?>