<?php
require '../config/conexion.php';
header('Content-Type: application/json');

// SEGURO: Solo usuarios logueados pueden registrar a otros
if (!isset($_SESSION['id_usuario'])) { 
    http_response_code(401); 
    echo json_encode(["error" => "No autorizado para realizar esta acción."]);
    exit; 
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->rut) && !empty($data->nombre_completo) && !empty($data->password)) {
    try {
        // Encriptar la contraseña (HASH)
        $hash = password_hash($data->password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO personal (rut, nombre_completo, contrasena) VALUES (:rut, :nombre, :hash)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rut' => $data->rut, 
            ':nombre' => $data->nombre_completo, 
            ':hash' => $hash
        ]);
        
        http_response_code(201);
        echo json_encode(["message" => "Usuario registrado correctamente."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos obligatorios."]);
}
?>