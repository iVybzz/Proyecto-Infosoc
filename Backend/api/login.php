<?php
session_start();
require_once 'conexion.php'; // Asegúrate de que el nombre coincida con tu archivo real

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rut = $_POST['rut'] ?? '';
    $contrasena_ingresada = $_POST['contrasena'] ?? '';

    if (empty($rut) || empty($contrasena_ingresada)) {
        echo json_encode(["exito" => false, "mensaje" => "Faltan datos obligatorios"]);
        exit();
    }

    try {
        // 1. Buscar al usuario por su RUT
        $sql = "SELECT id_usuario, nombre_completo, contrasena FROM personal WHERE rut = :rut LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':rut', $rut);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Verificar que el usuario exista y la contraseña coincida con el hash
        if ($usuario && password_verify($contrasena_ingresada, $usuario['contrasena'])) {
            
            // 3. Iniciar sesión guardando los datos importantes
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];

            echo json_encode([
                "exito" => true, 
                "mensaje" => "Bienvenido " . $usuario['nombre_completo']
            ]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Credenciales incorrectas"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["exito" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
    }
}
?>