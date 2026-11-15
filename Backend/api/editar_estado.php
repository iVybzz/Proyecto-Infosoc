<?php
require '../config/conexion.php';
header('Content-Type: application/json');

// Proteger con sesión
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id_activo) && !empty($data->estado_fisico)) {
    try {
        $sql = "UPDATE activo SET estado_fisico = :estado WHERE id_activo = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':estado' => $data->estado_fisico,
            ':id' => $data->id_activo
        ]);
        
        http_response_code(200);
        echo json_encode(["message" => "Estado actualizado correctamente."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos obligatorios."]);
}
?>