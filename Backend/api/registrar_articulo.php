<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// permitir conexion del frontend y definir respuesta como json
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// llamar a la conexion (subiendo un nivel de carpeta)
require '../config/conexion.php';



try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->nombre) && isset($data->cantidad) && !empty($data->id_categoria)) {
        
        // Determinar la cantidad según la categoría
        if ($data->id_categoria === 1) {
            // Equipos Médicos: Múltiples registros de 1 unidad
            $sql = "INSERT INTO activo (nombre, estado_fisico, disponibilidad, cantidad, descripcion, id_categoria) 
                    VALUES (:nombre, :estado_fisico, :disponibilidad, 1, :descripcion, :id_categoria)";
            $stmt = $pdo->prepare($sql);
            
            for ($i = 0; $i < $data->cantidad; $i++) {
                $stmt->execute([
                    ':nombre' => $data->nombre,
                    ':estado_fisico' => $data->estado_fisico,
                    ':disponibilidad' => $data->disponibilidad ?? 'Disponible',
                    ':descripcion' => $data->descripcion ?? null,
                    ':id_categoria' => $data->id_categoria
                ]);
            }
        } else {
            // Insumos y Campañas: Un solo registro con la cantidad total
            $sql = "INSERT INTO activo (nombre, estado_fisico, disponibilidad, cantidad, descripcion, id_categoria) 
                    VALUES (:nombre, :estado_fisico, :disponibilidad, :cantidad, :descripcion, :id_categoria)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $data->nombre,
                ':estado_fisico' => $data->estado_fisico,
                ':disponibilidad' => $data->disponibilidad ?? 'Disponible',
                ':cantidad' => $data->cantidad,
                ':descripcion' => $data->descripcion ?? null,
                ':id_categoria' => $data->id_categoria
            ]);
        }

        http_response_code(201);
        echo json_encode(['message' => 'Artículo registrado exitosamente.']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos obligatorios.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '[Error base de datos] Error al registrar el artículo: ' . $e->getMessage()]);
}