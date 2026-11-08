<?php

require_once '../config/conexion.php';
header('Content-Type: application/json');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

// aceptamos POST o PUT, ya que estamos actualizando datos existentes
if ($metodo_peticion === 'POST' || $metodo_peticion === 'PUT') {
    
    // Capturar el JSON que envíe el Frontend (React/JS)
    $datos_recibidos = json_decode(file_get_contents("php://input"), true);
    $id_prestamo = isset($datos_recibidos['id_prestamo']) ? $datos_recibidos['id_prestamo'] : null;

    if ($id_prestamo != null && $id_prestamo != "") {
        
        try {
            // iniciar la transaccion
            $conexion->beginTransaction();

            //primera consulta, actualizar la fecha_real_devolucion a la fecha de hoy
            $sql_prestamo = "UPDATE Prestamo SET fecha_real_devolucion = CURDATE() WHERE id_prestamo = :id_prestamo";
            $stmt_prestamo = $conexion->prepare($sql_prestamo);
            $stmt_prestamo->bindParam(':id_prestamo', $id_prestamo, PDO::PARAM_INT);
            $stmt_prestamo->execute();

            // segunda consulta, liberar el activo cambiándolo a disponible
            // subconsulta para encontrar que activo corresponde a este prestamo especifico
            $sql_activo = "UPDATE Activo 
                           SET estado = 'Disponible' 
                           WHERE id_activo = (SELECT id_activo FROM Prestamo WHERE id_prestamo = :id_prestamo LIMIT 1)";
            $stmt_activo = $conexion->prepare($sql_activo);
            $stmt_activo->bindParam(':id_prestamo', $id_prestamo, PDO::PARAM_INT);
            $stmt_activo->execute();

            // confirmar y guardar los cambios
            $conexion->commit();

            echo json_encode([
                "estado" => "ok",
                "mensaje" => "La devolución se registró con éxito y el equipo vuelve a estar disponible."
            ]);

        } catch (PDOException $e) {
            // si cualquier consulta falla, cancelamos todo
            $conexion->rollBack();
            
            echo json_encode([
                "estado" => "error",
                "mensaje" => "Error al registrar la devolución: " . $e->getMessage()
            ]);
        }

    } else {
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Falta el parámetro 'id_prestamo' en el JSON enviado."
        ]);
    }

} else {
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Método HTTP no permitido. Utilice POST o PUT."
    ]);
}
?>