<?php
require '../config/conexion.php';
header('Content-Type: application/json');

// Validar que el usuario tenga sesión activa
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->nueva_clave)) {
    try {
        // Encriptamos la nueva contraseña
        $hash = password_hash($data->nueva_clave, PASSWORD_DEFAULT);
        
        $sql = "UPDATE personal SET contrasena = :hash WHERE id_usuario = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':hash' => $hash,
            ':id' => $_SESSION['id_usuario']
        ]);
        
        http_response_code(200);
        echo json_encode(["status" => "ok", "message" => "Contraseña actualizada correctamente."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error BD: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos obligatorios."]);
}
?>