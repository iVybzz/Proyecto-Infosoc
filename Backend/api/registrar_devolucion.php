<?php

require_once '../config/conexion.php';
// Mantenemos la cabecera CORS correcta
header('Content-Type: application/json');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

if ($metodo_peticion === 'POST' || $metodo_peticion === 'PUT') {
    
    $datos_recibidos = json_decode(file_get_contents("php://input"), true);
    // Ahora leemos el ID de la transacción
    $id_transaccion = isset($datos_recibidos['id_transaccion']) ? $datos_recibidos['id_transaccion'] : null;

    if ($id_transaccion != null && $id_transaccion != "") {
        
        try {
            $pdo->beginTransaction();

            // 1. Actualizar la fecha_devolucion_real en la tabla transaccion
            $sql_transaccion = "UPDATE transaccion 
                                SET fecha_devolucion_real = CURDATE() 
                                WHERE id_transaccion = :id_transaccion";
            $stmt_transaccion = $pdo->prepare($sql_transaccion);
            $stmt_transaccion->bindParam(':id_transaccion', $id_transaccion, PDO::PARAM_INT);
            $stmt_transaccion->execute();

            // 2. Liberar el activo en la tabla activo
            $sql_activo = "UPDATE activo 
                           SET disponibilidad = 'Disponible' 
                           WHERE id_activo = (SELECT id_activo FROM transaccion WHERE id_transaccion = :id_transaccion LIMIT 1)";
            $stmt_activo = $pdo->prepare($sql_activo);
            $stmt_activo->bindParam(':id_transaccion', $id_transaccion, PDO::PARAM_INT);
            $stmt_activo->execute();

            $pdo->commit();

            echo json_encode([
                "estado" => "ok",
                "mensaje" => "La devolución se registró con éxito y el equipo vuelve a estar disponible."
            ]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode([
                "estado" => "error",
                "mensaje" => "Error al registrar la devolución: " . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Falta el parámetro 'id_transaccion' en el JSON enviado."
        ]);
    }
} else {
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Método HTTP no permitido. Utilice POST o PUT."
    ]);
}
?>
