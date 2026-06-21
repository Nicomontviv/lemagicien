<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Si consultan un ID específico, devolvemos el detalle
if ($id) {
    $query = "SELECT e.*, s.nombre as nombre_show 
              FROM eventos e 
              JOIN shows s ON e.show_id = s.id 
              WHERE e.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } else {
        http_response_code(404);
        echo json_encode(array("mensaje" => "Evento no encontrado."));
    }
    exit();
}

// Si no hay ID, devolvemos toda la agenda ordenada cronológicamente
$query = "SELECT e.id, e.fecha, e.hora, e.cliente, e.estado, s.nombre as show_nombre 
          FROM eventos e 
          JOIN shows s ON e.show_id = s.id 
          ORDER BY e.fecha ASC, e.hora ASC";
$stmt = $db->prepare($query);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    http_response_code(200);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    http_response_code(200); // 200 OK porque la petición es válida, simplemente la agenda está vacía
    echo json_encode(array("mensaje" => "No hay eventos cargados en la agenda.", "eventos" => []));
}
?>