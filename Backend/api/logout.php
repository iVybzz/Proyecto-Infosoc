<?php
session_start();   // 1. Recupera la sesión actual del usuario
session_unset();   // 2. Vacía todas las variables guardadas
session_destroy(); // 3. Elimina la sesión por completo de la memoria del servidor

// 4. Responde al frontend confirmando que se cerró la sesión
header('Content-Type: application/json');
echo json_encode(["exito" => true, "mensaje" => "Sesión cerrada correctamente"]);
?>