<?php
// backend/api/usuarios/verificar.php
require_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();

// Comprobamos que el token venga por la URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    
    // Limpiamos el token por seguridad
    $token = htmlspecialchars(strip_tags($_GET['token']));

    // 1. Buscamos si existe un usuario con este token exacto
    // (Nota: Asegurate de que la columna se llame exactamente 'token_verificacion' en tu base de datos)
    $query = "SELECT id FROM usuarios WHERE token_verificacion = :token LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":token", $token);
    $stmt->execute();

    // Si el token existe y es válido
    if ($stmt->rowCount() > 0) {
        
        // 2. Actualizamos el estado del usuario a validado y borramos el token para que no se re-use
        $updateQuery = "UPDATE usuarios SET cuenta_validada = 1, token_verificacion = NULL WHERE token_verificacion = :token";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(":token", $token);
        
        if ($updateStmt->execute()) {
            // 3. ¡Éxito! Redirigimos al usuario a la pantalla de Login del frontend (Vue)
            // Le pasamos un parámetro por la URL para poder mostrarle un mensaje verde si queremos
            header("Location: http://localhost:5173/login?verificado=true");
            exit();
        } else {
            echo "<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Hubo un error al actualizar la cuenta en la base de datos.</h2>";
        }

    } else {
        // El token no existe o ya fue usado
        echo "<h2 style='text-align:center; margin-top:50px; font-family:sans-serif; color:#e74c3c;'>El enlace de validación es inválido o ya ha sido utilizado.</h2>";
    }

} else {
    // Entraron a verificar.php sin pasar un token en la URL
    echo "<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>No se proporcionó ningún token de verificación.</h2>";
}
?>