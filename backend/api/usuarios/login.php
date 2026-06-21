<?php
// backend/api/usuarios/login.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();

$data = json_decode(file_get_contents("php://input"));

// Validamos que envíen los datos
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Faltan credenciales."));
    exit();
}

$email = htmlspecialchars(strip_tags($data->email));
$password = $data->password;

// Buscamos al usuario por su email
$query = "SELECT id, nombre, apellido, password_hash, cuenta_validada FROM usuarios WHERE email = :email LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":email", $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Escenario 3: Cuenta sin validar
    if ($row['cuenta_validada'] == 0) {
        http_response_code(403); // 403 Forbidden
        echo json_encode(array("mensaje" => "Debes validar tu cuenta confirmando tu correo electrónico antes de continuar."));
        exit();
    }

    // Escenario 1 y 2: Validar contraseña
    // password_verify compara el texto plano con el hash encriptado de la base de datos
    if (password_verify($password, $row['password_hash'])) {
        http_response_code(200);
        
        // En una app real, aquí generaríamos un Token JWT (JSON Web Token).
        // Por ahora devolvemos los datos básicos del usuario para confirmar el acceso.
        echo json_encode(array(
            "mensaje" => "Inicio de sesión exitoso.",
            "usuario" => array(
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "apellido" => $row['apellido']
            )
        ));
    } else {
        http_response_code(401); // 401 Unauthorized
        echo json_encode(array("mensaje" => "Credenciales inválidas."));
    }
} else {
    http_response_code(401);
    echo json_encode(array("mensaje" => "Credenciales inválidas."));
}
?>