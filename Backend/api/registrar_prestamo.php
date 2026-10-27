<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require '../config/conexion.php';

try{
    $data = json_decode(file_get_contents("php://input"));
    // validar que los campos obligatorios del préstamo no estén vacíos
    if (!empty($data->id_activo) && !empty($data->id_beneficiario) && !empty($data->id_usuario) && isset($data->cantidad_entregada) && !empty($data->fecha_devolucion_esperada)) {
        $sqlStock = "SELECT cantidad FROM Activo WHERE id_activo = :id_activo";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute([':id_activo' => $data->id_activo]);
        $activo = $stmtStock->fetch(PDO::FETCH_ASSOC);

        // validar que exista el equipo y que el stock sea suficiente
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
        $pdo->beginTransaction();
        // preparar la consulta SQL para el registro de la transaccion
        $sqlInsert = "INSERT INTO Transaccion (fecha_salida, fecha_devolucion_esperada, cantidad_entregada, id_activo, id_beneficiario, id_usuario) 
                      VALUES (CURRENT_DATE, :fecha_devolucion, :cantidad, :id_activo, :id_beneficiario, :id_usuario)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':fecha_devolucion' => $data->fecha_devolucion_esperada,
            ':cantidad'         => $data->cantidad_entregada,
            ':id_activo'        => $data->id_activo,
            ':id_beneficiario'  => $data->id_beneficiario,
            ':id_usuario'       => $data->id_usuario
        ]);
        // preparar la consulta SQL para descontar el stock
        $sqlUpdate = "UPDATE Activo 
                      SET cantidad = cantidad - :cantidad_restar, 
                          disponibilidad = CASE WHEN (cantidad - :cantidad_check) <= 0 THEN 'Prestado' ELSE disponibilidad END
                      WHERE id_activo = :id_activo_upd";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':cantidad_restar' => $data->cantidad_entregada,
            ':cantidad_check'  => $data->cantidad_entregada,
            ':id_activo_upd'   => $data->id_activo
        ]);
        // confirmar los cambios si ambas consultas fueron exitosas
        $pdo->commit();
        http_response_code(201);
        echo json_encode(['message' => 'Préstamo registrado exitosamente y stock actualizado.']);

    } else{
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos obligatorios para registrar el préstamo.']);
    }

} catch (PDOException $e) {
    // si hay un error, deshacer cualquier cambio parcial en la base de datos
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => '[Error base de datos] Error al registrar el préstamo: ' . $e->getMessage()]);
}