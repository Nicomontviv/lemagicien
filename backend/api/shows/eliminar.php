<?php
// ./backend/api/shows/eliminar.php

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

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Falta el ID del show."));
    exit();
}

try {
    // Iniciamos transacción para que, si algo falla, no se borre por la mitad
    $db->beginTransaction();

    // 1. Borramos los trucos asociados a este show en la tabla intermedia
    $query_relaciones = "DELETE FROM show_trucos WHERE show_id = :id";
    $stmt_relaciones = $db->prepare($query_relaciones);
    $stmt_relaciones->bindParam(":id", $data->id);
    $stmt_relaciones->execute();

    // 2. Borramos el show
    $query_show = "DELETE FROM shows WHERE id = :id";
    $stmt_show = $db->prepare($query_show);
    $stmt_show->bindParam(":id", $data->id);
    
    if ($stmt_show->execute()) {
        $db->commit();
        http_response_code(200);
        echo json_encode(array("mensaje" => "Show eliminado correctamente."));
    } else {
        $db->rollBack();
        http_response_code(503);
        echo json_encode(array("mensaje" => "No se pudo eliminar el show."));
    }
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("mensaje" => "Error interno del servidor."));
}
?>