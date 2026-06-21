<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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

// Validamos que venga el ID y el nombre (que es obligatorio)
if (!empty($data->id) && !empty($data->nombre)) {
    
    $query = "UPDATE trucos 
              SET nombre = :nombre, descripcion = :descripcion, categoria = :categoria, duracion_minutos = :duracion_minutos 
              WHERE id = :id";
              
    $stmt = $db->prepare($query);

    $id = htmlspecialchars(strip_tags($data->id));
    $nombre = htmlspecialchars(strip_tags($data->nombre));
    $descripcion = htmlspecialchars(strip_tags($data->descripcion ?? ''));
    $categoria = htmlspecialchars(strip_tags($data->categoria ?? ''));
    $duracion_minutos = !empty($data->duracion_minutos) ? (int)$data->duracion_minutos : null;

    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":descripcion", $descripcion);
    $stmt->bindParam(":categoria", $categoria);
    $stmt->bindParam(":duracion_minutos", $duracion_minutos);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["mensaje" => "Ilusión actualizada correctamente."]);
    } else {
        http_response_code(503);
        echo json_encode(["mensaje" => "No se pudo actualizar la ilusión."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "Faltan datos obligatorios (ID o Nombre)."]);
}
?>