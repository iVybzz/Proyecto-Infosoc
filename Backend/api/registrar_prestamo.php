<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../config/conexion.php';
// CORS ya está manejado por conexion.php
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents("php://input"));

    // 1. Validar que vengan los datos que envía el frontend
    if (!empty($data->id_activo) && !empty($data->rut_beneficiario) && !empty($data->nombre_beneficiario) && !empty($data->id_usuario) && isset($data->cantidad_entregada) && !empty($data->fecha_devolucion_esperada)) {

        $pdo->beginTransaction();

        // 2. Validar stock del activo
        $sqlStock = "SELECT cantidad FROM activo WHERE id_activo = :id_activo";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute([':id_activo' => $data->id_activo]);
        $activo = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if (!$activo) {
            http_response_code(404);
            echo json_encode(['error' => 'El artículo solicitado no existe.']);
            exit;
        }
        if ($activo['cantidad'] < $data->cantidad_entregada) {
            http_response_code(400);
            echo json_encode(['error' => 'Stock insuficiente. Solo quedan ' . $activo['cantidad'] . ' unidades disponibles.']);
            exit;
        }

        // 3. Gestionar Beneficiario (Buscar si existe por RUT, si no, crearlo)
        $sqlBuscaBen = "SELECT id_beneficiario FROM beneficiario WHERE rut = :rut";
        $stmtBuscaBen = $pdo->prepare($sqlBuscaBen);
        $stmtBuscaBen->execute([':rut' => $data->rut_beneficiario]);
        $beneficiario = $stmtBuscaBen->fetch(PDO::FETCH_ASSOC);

        if ($beneficiario) {
            $id_beneficiario = $beneficiario['id_beneficiario'];
        } else {
            $sqlInsertBen = "INSERT INTO beneficiario (rut, nombre_completo, telefono, direccion, fecha_registro) VALUES (:rut, :nombre, :telefono, :direccion, CURDATE())";
            $stmtInsertBen = $pdo->prepare($sqlInsertBen);
            $stmtInsertBen->execute([
                ':rut' => $data->rut_beneficiario,
                ':nombre' => $data->nombre_beneficiario,
                ':telefono' => $data->telefono_beneficiario ?? null,
                ':direccion' => $data->direccion_beneficiario ?? ''
            ]);
            $id_beneficiario = $pdo->lastInsertId();
        }

        // 4. Insertar la Transacción usando el ID del beneficiario y guardando la nota
        $sqlInsert = "INSERT INTO transaccion (fecha_salida, fecha_devolucion_esperada, cantidad_entregada, observaciones, id_activo, id_beneficiario, id_usuario)
                       VALUES (CURDATE(), :fecha_devolucion, :cantidad, :observaciones, :id_activo, :id_beneficiario, :id_usuario)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':fecha_devolucion' => $data->fecha_devolucion_esperada,
            ':cantidad'         => $data->cantidad_entregada,
            ':observaciones'    => $data->observaciones ?? null,
            ':id_activo'        => $data->id_activo,
            ':id_beneficiario'  => $id_beneficiario,
            ':id_usuario'       => $data->id_usuario
        ]);

        // 5. Actualizar el stock del Activo
        $sqlUpdate = "UPDATE activo
                       SET cantidad = cantidad - :cantidad_restar,
                           disponibilidad = CASE WHEN (cantidad - :cantidad_check) <= 0 THEN 'Prestado' ELSE disponibilidad END
                       WHERE id_activo = :id_activo_upd";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':cantidad_restar' => $data->cantidad_entregada,
            ':cantidad_check'  => $data->cantidad_entregada,
            ':id_activo_upd'   => $data->id_activo
        ]);

        $pdo->commit();
        http_response_code(201);
        echo json_encode(['message' => 'Préstamo registrado exitosamente.']);

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos obligatorios.']);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
}
?>
