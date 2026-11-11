<?php
// api/login.php
require_once '../config/conexion.php';
header('Content-Type: application/json; charset=UTF-8');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

if ($metodo_peticion === 'POST') {
    // Capturar el JSON enviado desde tu fetch en script.js
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->rut) && !empty($data->password)) {
        try {
            // Buscar al usuario por su RUT en la tabla personal
            $sql = "SELECT id_usuario, nombre_completo, contrasena FROM personal WHERE rut = :rut";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':rut' => $data->rut]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar que el usuario exista y que la contraseña coincida con el hash
            if ($usuario && password_verify($data->password, $usuario['contrasena'])) {
                
                // INICIAMOS LA SESIÓN REAL AQUÍ
                $_SESSION['id_usuario'] = $usuario['id_usuario']; 
                
                echo json_encode([
                    "status" => "ok",
                    "message" => "Login exitoso",
                    "usuario" => [
                        "id_usuario" => $usuario['id_usuario'],
                        "nombre" => $usuario['nombre_completo']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "RUT o contraseña incorrectos."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error de base de datos: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Faltan credenciales."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método HTTP no permitido. Use POST."]);
}
?>