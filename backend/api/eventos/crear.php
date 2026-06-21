<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();
$data = json_decode(file_get_contents("php://input"));

// Validación de datos obligatorios
if (empty($data->cliente) || empty($data->fecha) || empty($data->hora) || !isset($data->monto)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Faltan datos obligatorios (cliente, fecha, hora, monto)."));
    exit();
}

if (empty($data->show_id)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Debe seleccionar un show previamente creado para registrar el evento."));
    exit();
}

if ($data->monto < 0) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "El monto acordado debe ser mayor o igual a cero."));
    exit();
}

$usuario_id = isset($data->usuario_id) ? (int)$data->usuario_id : 1; // Por ahora usamos el usuario 1 de prueba
$show_id = (int)$data->show_id;
$cliente = htmlspecialchars(strip_tags($data->cliente));
$fecha = htmlspecialchars(strip_tags($data->fecha));
$hora = htmlspecialchars(strip_tags($data->hora));
$direccion = isset($data->direccion) ? htmlspecialchars(strip_tags($data->direccion)) : null;
$monto = (float)$data->monto;
$observaciones = isset($data->observaciones) ? htmlspecialchars(strip_tags($data->observaciones)) : null;

$query = "INSERT INTO eventos (usuario_id, show_id, cliente, fecha, hora, direccion, monto, observaciones) 
          VALUES (:usuario_id, :show_id, :cliente, :fecha, :hora, :direccion, :monto, :observaciones)";
$stmt = $db->prepare($query);
$stmt->bindParam(":usuario_id", $usuario_id);
$stmt->bindParam(":show_id", $show_id);
$stmt->bindParam(":cliente", $cliente);
$stmt->bindParam(":fecha", $fecha);
$stmt->bindParam(":hora", $hora);
$stmt->bindParam(":direccion", $direccion);
$stmt->bindParam(":monto", $monto);
$stmt->bindParam(":observaciones", $observaciones);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(array("mensaje" => "Evento registrado correctamente en estado Pendiente.", "id_evento" => $db->lastInsertId()));
} else {
    http_response_code(500);
    echo json_encode(array("mensaje" => "Error al registrar el evento."));
}
?>