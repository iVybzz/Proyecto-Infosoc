<?php

// 1. Requerir la conexión a la base de datos (Esto ya trae las cabeceras CORS seguras para Vercel)
require_once '../config/conexion.php';

// 2. Establecer el tipo de contenido a JSON
header('Content-Type: application/json');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

// 3. Validar que la petición sea GET
if ($metodo_peticion === 'GET') {
    
    try {
        // 4. Preparar la instrucción SQL utilizando las tablas y columnas exactas del archivo bdd.sql
        $consulta_sql_prestamos = "
            SELECT 
                t.id_transaccion,
                b.nombre_completo AS nombre_beneficiario,
                b.telefono,
                a.nombre AS articulo_prestado,
                t.fecha_salida,
                t.fecha_devolucion_esperada,
                t.cantidad_entregada,
                t.observaciones,
                CASE 
                    WHEN t.fecha_devolucion_esperada < CURDATE() THEN 'Atrasado'
                    ELSE 'Pendiente'
                END AS estado_prestamo
            FROM 
                transaccion t
            INNER JOIN 
                beneficiario b ON t.id_beneficiario = b.id_beneficiario
            INNER JOIN 
                activo a ON t.id_activo = a.id_activo
            WHERE 
                t.fecha_devolucion_real IS NULL
            ORDER BY 
                t.fecha_devolucion_esperada ASC
        ";
        
        $sentencia_preparada = $conexion->prepare($consulta_sql_prestamos);
        
        $sentencia_preparada->execute();
        
        $resultados_prestamos = $sentencia_preparada->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "estado" => "ok",
            "cantidad_registros" => count($resultados_prestamos),
            "datos" => $resultados_prestamos
        ]);

    } catch (PDOException $excepcion_bd) {
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Error al intentar listar los préstamos: " . $excepcion_bd->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Método HTTP no permitido. Debe utilizar GET."
    ]);
}
?>
