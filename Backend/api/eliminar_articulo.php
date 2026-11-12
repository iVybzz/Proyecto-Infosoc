<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: DELETE');
require '../config/conexion.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id_activo)){
        
        // 1. Preguntamos si este artículo tiene historial
        $sqlCheck = "SELECT COUNT(*) FROM transaccion WHERE id_activo = :id_activo";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':id_activo' => $data->id_activo]);
        
        $tieneHistorial = $stmtCheck->fetchColumn() > 0;
        
        if ($tieneHistorial) {
            // 2A. TIENE HISTORIAL: Lo ocultamos
            $sql = "UPDATE activo SET disponibilidad = 'Eliminado' WHERE id_activo = :id_activo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_activo' => $data->id_activo]);
            $mensaje = "Artículo retirado (Oculto para conservar su historial).";
        } else {
            // 2B. NO TIENE HISTORIAL: Lo borramos de verdad
            $sql = "DELETE FROM activo WHERE id_activo = :id_activo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_activo' => $data->id_activo]);
            $mensaje = "Artículo eliminado definitivamente (No tenía historial).";
        }
        
        http_response_code(200);
        echo json_encode(["message" => $mensaje]);
        
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Falta el ID del artículo a eliminar."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "[Error Base de Datos] Error al eliminar: " . $e->getMessage()]);
}
?>