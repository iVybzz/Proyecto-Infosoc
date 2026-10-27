<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE');

require '../config/conexion.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->id_activo)){

        $sql = "DELETE FROM activo WHERE id_activo = :id_activo";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([':id_activo' => $data->id_activo]);

        http_response_code(200);
        echo json_encode(["message" => "Artículo eliminado correctamente."]);

    } else {
        http_response_code(400);
        echo json_encode(["message" => "Falta el ID del artículo a eliminar."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "[Error Base de Datos] Error al eliminar el artículo."]);
}