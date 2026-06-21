<?php
// backend/api/trucos/crear.php
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

if (!empty($data->nombre)) {
    
    $query = "INSERT INTO trucos (nombre, descripcion, categoria, duracion_minutos) 
              VALUES (:nombre, :descripcion, :categoria, :duracion_minutos)";
    $stmt = $db->prepare($query);

    $nombre = htmlspecialchars(strip_tags($data->nombre));
    $descripcion = htmlspecialchars(strip_tags($data->descripcion ?? ''));
    $categoria = htmlspecialchars(strip_tags($data->categoria ?? ''));
    $duracion_minutos = !empty($data->duracion_minutos) ? (int)$data->duracion_minutos : null;

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":descripcion", $descripcion);
    $stmt->bindParam(":categoria", $categoria);
    $stmt->bindParam(":duracion_minutos", $duracion_minutos);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["mensaje" => "Truco agregado al repertorio exitosamente."]);
    } else {
        http_response_code(503);
        echo json_encode(["mensaje" => "No se pudo guardar el truco."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "El nombre del truco es obligatorio."]);
}
?>