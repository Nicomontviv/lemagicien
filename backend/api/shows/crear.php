<?php
// backend/api/shows/crear.php
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

if (!empty($data->nombre) && !empty($data->usuario_id)) {
    
    $query = "INSERT INTO shows (usuario_id, nombre, descripcion) VALUES (:usuario_id, :nombre, :descripcion)";
    $stmt = $db->prepare($query);

    $usuario_id = htmlspecialchars(strip_tags($data->usuario_id));
    $nombre = htmlspecialchars(strip_tags($data->nombre));
    $descripcion = htmlspecialchars(strip_tags($data->descripcion ?? ''));

    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":descripcion", $descripcion);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["mensaje" => "Espectáculo creado exitosamente."]);
    } else {
        http_response_code(503);
        echo json_encode(["mensaje" => "No se pudo guardar el espectáculo."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "El nombre del show y el usuario son obligatorios."]);
}
?>