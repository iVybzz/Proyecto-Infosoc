<?php
require '../config/conexion.php';
header('Content-Type: application/json');

// Verificamos si existe la variable de sesión
if (isset($_SESSION['id_usuario'])) {
    echo json_encode(["status" => "ok", "message" => "Sesión activa"]);
} else {
    // 401 Unauthorized: Expulsamos a quien no tenga sesión
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No autorizado. Inicie sesión."]);
}
?>