<?php

require_once '../config/conexion.php';

header('Content-Type: application/json');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

if ($metodo_peticion === 'GET') {
    
    try {
        // Se asume que un préstamo pendiente es aquel que no tiene "fecha_real_devolucion"
        // y se compara la 'fecha_esperada_devolucion' con la fecha actual para saber si está atrasado.
        $consulta_sql_prestamos = "
            SELECT 
                p.id_prestamo,
                b.nombre AS nombre_beneficiario,
                b.apellido AS apellido_beneficiario,
                a.nombre_equipo AS articulo_prestado,
                p.fecha_entrega,
                p.fecha_esperada_devolucion,
                p.cantidad_entregada,
                CASE 
                    WHEN p.fecha_esperada_devolucion < CURDATE() THEN 'Atrasado'
                    ELSE 'Pendiente'
                END AS estado_prestamo
            FROM 
                Prestamo p
            INNER JOIN 
                Beneficiario b ON p.id_beneficiario = b.id_beneficiario
            INNER JOIN 
                Activo a ON p.id_activo = a.id_activo
            WHERE 
                p.fecha_real_devolucion IS NULL
            ORDER BY 
                p.fecha_esperada_devolucion ASC
        ";
        
        $sentencia_preparada = $conexion->prepare($consulta_sql_prestamos);
        
        // ejecutar la consulta
        $sentencia_preparada->execute();
        
        //obtener los resultados como un arreglo asociativo
        $resultados_prestamos = $sentencia_preparada->fetchAll(PDO::FETCH_ASSOC);
        
        //armar y enviar la respuesta json
        $respuesta_json_exito = array();
        $respuesta_json_exito["estado"] = "ok";
        $respuesta_json_exito["cantidad_registros"] = count($resultados_prestamos);
        $respuesta_json_exito["datos"] = $resultados_prestamos;
        
        echo json_encode($respuesta_json_exito);

    } catch (PDOException $excepcion_bd) {
        $respuesta_json_excepcion = array();
        $respuesta_json_excepcion["estado"] = "error";
        $respuesta_json_excepcion["mensaje"] = "Error al intentar listar los préstamos: " . $excepcion_bd->getMessage();
        
        echo json_encode($respuesta_json_excepcion);
    }

} else {
    $respuesta_json_metodo = array();
    $respuesta_json_metodo["estado"] = "error";
    $respuesta_json_metodo["mensaje"] = "Método HTTP no permitido. Debe utilizar GET.";
    
    echo json_encode($respuesta_json_metodo);
}
?>