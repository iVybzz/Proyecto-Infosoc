<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');

require '../config/conexion.php';

try {
    // En las peticiones GET, los datos no van en el Body, van en la URL ($_GET)
    if (isset($_GET['id_activo'])) {
        
        $sql = "SELECT * FROM activo WHERE id_activo = :id_activo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_activo' => $_GET['id_activo']]);
        
        // Usamos fetch() en vez de fetchAll() porque solo esperamos un registro
        $articulo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($articulo) {
            http_response_code(200);
            echo json_encode($articulo);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Artículo no encontrado."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Debes enviar el id_activo en la URL."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "[Error Base de Datos] Error al buscar el artículo: " . $e->getMessage()]);
}
