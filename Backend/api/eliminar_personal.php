<?php
require '../config/conexion.php';
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_usuario)) {
        // Bloqueo de seguridad: No puedes borrarte a ti mismo
        if ($data->id_usuario == $_SESSION['id_usuario']) {
            http_response_code(400);
            echo json_encode(["error" => "No puedes eliminar tu propia cuenta en uso."]);
            exit;
        }

        $sql = "DELETE FROM personal WHERE id_usuario = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $data->id_usuario]);
        
        http_response_code(200);
        echo json_encode(["message" => "Usuario eliminado correctamente."]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Falta el ID del usuario."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error BD: " . $e->getMessage()]);
}
?>