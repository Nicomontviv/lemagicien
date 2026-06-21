<?php
// backend/api/usuarios/registro.php
require_once '../../PHPMailer/Exception.php';
require_once '../../PHPMailer/PHPMailer.php';
require_once '../../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Cabeceras para permitir que Vue.js se comunique sin bloqueos de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Agregamos OPTIONS acá
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// 2. Incluir la conexión a la base de datos
include_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();

// 3. Obtener los datos enviados en formato JSON
$data = json_decode(file_get_contents("php://input"));

// ==========================================
// ESCENARIO 3: Datos incompletos
// ==========================================
if (empty($data->nombre) || empty($data->apellido) || empty($data->email) || empty($data->password)) {
    http_response_code(400); // Código 400: Bad Request
    echo json_encode(array("mensaje" => "Faltan completar campos obligatorios."));
    exit();
}

// Limpieza básica de los datos de entrada por seguridad
$nombre = htmlspecialchars(strip_tags($data->nombre));
$apellido = htmlspecialchars(strip_tags($data->apellido));
$email = htmlspecialchars(strip_tags($data->email));
$password = $data->password;

// ==========================================
// ESCENARIO 2: Email ya registrado
// ==========================================
$queryCheck = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
$stmtCheck = $db->prepare($queryCheck);
$stmtCheck->bindParam(":email", $email);
$stmtCheck->execute();

if ($stmtCheck->rowCount() > 0) {
    http_response_code(409); // Código 409: Conflict
    echo json_encode(array("mensaje" => "El correo electrónico ya se encuentra registrado."));
    exit();
}

// ==========================================
// ESCENARIO 1: Registro exitoso
// ==========================================

// Usamos password_hash(), el estándar de seguridad nativo de PHP
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Generamos un token único de 64 caracteres
$token_verificacion = bin2hex(random_bytes(32));

// Preparamos la consulta de inserción (Ajustado a token_verificacion)
$queryInsert = "INSERT INTO usuarios (nombre, apellido, email, password_hash, cuenta_validada, token_verificacion) 
                VALUES (:nombre, :apellido, :email, :password_hash, 0, :token_verificacion)";

$stmtInsert = $db->prepare($queryInsert);
$stmtInsert->bindParam(":nombre", $nombre);
$stmtInsert->bindParam(":apellido", $apellido);
$stmtInsert->bindParam(":email", $email);
$stmtInsert->bindParam(":password_hash", $password_hash);
$stmtInsert->bindParam(":token_verificacion", $token_verificacion);

if ($stmtInsert->execute()) {
    
    // Configuración del correo electrónico usando PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // 👇 REEMPLAZAR CON TUS DATOS 👇
        $mail->Username   = 'nicomontviv@gmail.com'; 
        $mail->Password   = 'wplyuvbwdvlrzdou'; 
        // 👆 REEMPLAZAR CON TUS DATOS 👆
        //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        //$mail->Port       = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('TU_CORREO@gmail.com', 'Le Magicien');
        $mail->addAddress($email, $nombre . ' ' . $apellido);
        
        // Asignamos el charset para que las tildes y ñ funcionen bien
        $mail->CharSet = 'UTF-8';

        // Contenido
        $enlaceConfirmacion = "http://localhost:8000/api/usuarios/verificar.php?token=" . $token_verificacion;
        
        $mail->isHTML(true);
        $mail->Subject = 'Activá tu cuenta en Le Magicien 🎩';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #2c3e50; text-align: center;'>¡Bienvenido a Le Magicien, $nombre!</h2>
                <p style='color: #555; font-size: 16px;'>Para poder ingresar a tu panel de gestión y administrar tus ilusiones, necesitamos validar tu correo electrónico.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$enlaceConfirmacion}' style='background-color: #42b983; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>Activar mi cuenta</a>
                </div>
                <p style='color: #888; font-size: 14px;'>Si el botón no funciona, copiá y pegá este enlace en tu navegador:</p>
                <p style='color: #3498db; font-size: 14px; word-break: break-all;'>{$enlaceConfirmacion}</p>
            </div>
        ";

        $mail->send();

        http_response_code(201);
        echo json_encode(array(
            "mensaje" => "Registro exitoso. Te enviamos un correo electrónico para verificar tu cuenta."
        ));

    } catch (Exception $e) {
        // Si el usuario se guardó pero falló el correo
        http_response_code(201); 
        echo json_encode(array(
            "mensaje" => "Registro exitoso en la base de datos, pero hubo un error al enviar el correo de verificación. Error: {$mail->ErrorInfo}"
        ));
    }

} else {
    http_response_code(500); 
    echo json_encode(array("mensaje" => "Ocurrió un error al intentar registrar el usuario en la base de datos."));
}
?>