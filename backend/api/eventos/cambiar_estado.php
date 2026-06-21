<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->estado)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "El ID del evento y el nuevo estado son obligatorios."));
    exit();
}

$id = (int)$data->id;
$estado = ucfirst(strtolower(trim($data->estado)));
$estados_validos = ['Pendiente', 'Confirmado', 'Realizado', 'Cancelado'];

if (!in_array($estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Estado no válido."));
    exit();
}

$query = "UPDATE eventos SET estado = :estado WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":estado", $estado);
$stmt->bindParam(":id", $id);

if ($stmt->execute() && $stmt->rowCount() > 0) {
    http_response_code(200);
    echo json_encode(array("mensaje" => "Estado del evento actualizado a " . $estado . "."));
} else {
    http_response_code(404);
    echo json_encode(array("mensaje" => "Evento no encontrado o el estado es el mismo."));
}
?>