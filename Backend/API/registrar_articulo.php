<?php
// permitir conexion del frontend y definir respuesta como json
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// llamar a la conexion (subiendo un nivel de carpeta)
require '../config/conexion.php';

try{
    //capturar los datos enviados por el frontend
    $data = json_decode(file_get_contents("php://input"));

    // Validar que los campos criticos no estén vacios
    if (!empty($data->nombre) && isset($data->cantidad) && !empty($data->id_categoria)){

        // Preparar la consulta SQL (evitando inyecciones SQL)
        $sql = "INSERT INTO activo (nombre, estado_fisico, disponibilidad, cantidad, id_categoria) 
        VALUES (:nombre, :estado_fisico, :disponibilidad, :cantidad, :id_categoria)";

        $stmt = $pdo->prepare($sql);

        //ejecutar inyectando las variables
        $stmt->execute([
            ':nombre' => $data->nombre,
            ':estado_fisico' => $data->estado_fisico,
            ':disponibilidad' => $data->disponibilidad ?? 'Disponible', // Valor por defecto si no se proporciona
            ':cantidad' => $data->cantidad,
            ':id_categoria' => $data->id_categoria
        ]);

        // mensaje de exito
        http_response_code(201);
        echo json_encode(['message' => 'Artículo registrado exitosamente.']);
    } else {
        // mensaje de error si faltan campos
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos obligatorios.']);
    }

} catch (PDOException $e) {
    // mensaje de error si falla la consulta
    http_response_code(500);
    echo json_encode(['error' => '[Error base de datos] Error al registrar el artículo: ' . $e->getMessage()]);
}