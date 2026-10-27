<?php

require_once '../config/conexion.php'; 

header('Content-Type: application/json');

$metodo_peticion = $_SERVER['REQUEST_METHOD'];

// validar que la petición sea del tipo delete
if ($metodo_peticion === 'DELETE' || $metodo_peticion === 'GET') {
    
    // capturar el id del beneficiario desde la url (ej: eliminar_beneficiario.php?id=5)
    $id_beneficiario_a_eliminar = isset($_GET['id']) ? $_GET['id'] : null;

    // verificamos que exista y que no este vacio
    if ($id_beneficiario_a_eliminar != null && $id_beneficiario_a_eliminar != "") {
        
        try {
            $consulta_sql_eliminar = "DELETE FROM Beneficiario WHERE id_beneficiario = :id_bd";
            
            $sentencia_preparada = $conexion->prepare($consulta_sql_eliminar);
            
            // vinculamos el parametro de forma explicita
            $sentencia_preparada->bindParam(':id_bd', $id_beneficiario_a_eliminar, PDO::PARAM_INT);
            
            // se ejecuta la instruccion en la bdd
            $sentencia_preparada->execute();

            $filas_afectadas = $sentencia_preparada->rowCount();
            
            if ($filas_afectadas > 0) {
                $respuesta_json_exito = array();
                $respuesta_json_exito["estado"] = "ok";
                $respuesta_json_exito["mensaje"] = "El beneficiario fue eliminado correctamente de la base de datos.";
                
                echo json_encode($respuesta_json_exito);
            } else {
                $respuesta_json_error_filas = array();
                $respuesta_json_error_filas["estado"] = "error";
                $respuesta_json_error_filas["mensaje"] = "No se encontró ningún beneficiario con ese ID en los registros.";
                
                echo json_encode($respuesta_json_error_filas);
            }

        } catch (PDOException $excepcion_bd) {
            // capturar errores especificos de la bdd
            $respuesta_json_excepcion = array();
            $respuesta_json_excepcion["estado"] = "error";
            $respuesta_json_excepcion["mensaje"] = "Error al intentar eliminar en la base de datos: " . $excepcion_bd->getMessage();
            
            echo json_encode($respuesta_json_excepcion);
        }

    } else {
        // erorr si faltan datos en la url
        $respuesta_json_error_id = array();
        $respuesta_json_error_id["estado"] = "error";
        $respuesta_json_error_id["mensaje"] = "Falta el ID del beneficiario. La URL debe incluir '?id=NUMERO'.";
        
        echo json_encode($respuesta_json_error_id);
    }

} else {
    // error si usan el metodo http equivocado
    $respuesta_json_metodo = array();
    $respuesta_json_metodo["estado"] = "error";
    $respuesta_json_metodo["mensaje"] = "Método HTTP no permitido. Debe utilizar DELETE.";
    
    echo json_encode($respuesta_json_metodo);
}
?>