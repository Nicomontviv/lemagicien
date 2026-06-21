<?php
// backend/api/shows/buscar.php
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

// Recibimos el ID del usuario por la URL (ej: buscar.php?usuario_id=1)
$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;

if ($usuario_id > 0) {
    $query = "SELECT id, nombre, descripcion, creado_en FROM shows WHERE usuario_id = :usuario_id ORDER BY nombre ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $shows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($shows);
    } else {
        http_response_code(200);
        echo json_encode([]); // Array vacío si no tiene shows
    }
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "Falta el ID del usuario."]);
}
?>