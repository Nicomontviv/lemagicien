<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
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

if (!empty($data->id)) {
    $query = "DELETE FROM trucos WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $id = htmlspecialchars(strip_tags($data->id));
    $stmt->bindParam(":id", $id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["mensaje" => "Ilusión eliminada del repertorio."]);
    } else {
        http_response_code(503);
        echo json_encode(["mensaje" => "No se pudo eliminar la ilusión."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "El ID de la ilusión es obligatorio."]);
}
?>