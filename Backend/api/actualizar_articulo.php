<?php
// Mensajes en consola en caso de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
// Cambiamos el metodo a PUT
header('Access-Control-Allow-Methods: PUT');

require '../config/conexion.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    // ID obligatorio para identificar el artículo a actualizar
    if (!empty($data->id_activo) && !empty($data->nombre) && isset($data->cantidad)) {

        // Consulta UPDATE reemplaza los valores de la fila que coincide con el ID
        $sql = "UPDATE activo
                SET nombre = :nombre, estado_fisico = :estado_fisico,
                disponibilidad = :disponibilidad, cantidad = :cantidad,
                id_categoria = :id_categoria
                WHERE id_activo = :id_activo";
        
        $stmt = $pdo->prepare($sql);

        // Inyectamos las variables, incluyendo el ID del artículo a actualizar
        $stmt->execute([
            ':nombre' => $data->nombre,
            ':estado_fisico' => $data->estado_fisico,
            ':disponibilidad' => $data->disponibilidad,
            ':cantidad' => $data->cantidad,
            ':id_categoria' => $data->id_categoria,
            ':id_activo' => $data->id_activo
        ]);

        // 200 OK
        http_response_code(200);
        echo json_encode(["message" => "Artículo actualizado correctamente."]);

    } else {
        // 400 Bad Request
        http_response_code(400);
        echo json_encode(["message" => "Datos incompletos."]);
    }
}
catch (PDOException $e) {
    // 500 Internal Server Error
    http_response_code(500);
    echo json_encode(["message" => "[Error Base de Datos] Error al actualizar el artículo: " . $e->getMessage()]);
}
?>