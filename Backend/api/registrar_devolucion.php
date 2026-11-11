<?php

require_once '../config/conexion.php';
// Mantenemos la cabecera CORS correcta
header('Content-Type: application/json');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

if ($metodo_peticion === 'POST' || $metodo_peticion === 'PUT') {
    
    $datos_recibidos = json_decode(file_get_contents("php://input"), true);
    $id_transaccion = filter_var(
        $datos_recibidos['id_transaccion'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($id_transaccion !== false && $id_transaccion > 0) {
        
        try {
            $pdo->beginTransaction();

            $sql_transaccion = "SELECT id_activo, cantidad_entregada, fecha_devolucion_real
                                FROM transaccion
                                WHERE id_transaccion = :id_transaccion
                                FOR UPDATE";
            $stmt_transaccion = $pdo->prepare($sql_transaccion);
            $stmt_transaccion->execute([':id_transaccion' => $id_transaccion]);
            $transaccion = $stmt_transaccion->fetch(PDO::FETCH_ASSOC);

            if (!$transaccion) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "No se encontró el préstamo solicitado."
                ]);
                exit;
            }

            if ($transaccion['fecha_devolucion_real'] !== null) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "Este préstamo ya fue devuelto."
                ]);
                exit;
            }

            $stmt_transaccion = $pdo->prepare(
                "UPDATE transaccion
                 SET fecha_devolucion_real = CURDATE()
                 WHERE id_transaccion = :id_transaccion"
            );
            $stmt_transaccion->execute([':id_transaccion' => $id_transaccion]);

            // Reponer todas las unidades del préstamo y actualizar su disponibilidad.
            $sql_activo = "UPDATE activo
                           SET cantidad = cantidad + :cantidad_devuelta,
                               disponibilidad = 'Disponible'
                           WHERE id_activo = :id_activo";
            $stmt_activo = $pdo->prepare($sql_activo);
            $stmt_activo->execute([
                ':cantidad_devuelta' => $transaccion['cantidad_entregada'],
                ':id_activo' => $transaccion['id_activo']
            ]);

            $pdo->commit();

            echo json_encode([
                "estado" => "ok",
                "mensaje" => "La devolución se registró y las unidades volvieron al inventario."
            ]);

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                "estado" => "error",
                "mensaje" => "Error al registrar la devolución: " . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Debes enviar un 'id_transaccion' válido en el JSON."
        ]);
    }
} else {
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Método HTTP no permitido. Utilice POST o PUT."
    ]);
}
?>
