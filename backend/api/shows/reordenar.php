<?php
// backend/api/shows/reordenar.php
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

if (empty($data->show_id) || empty($data->orden) || !is_array($data->orden)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Se requiere show_id y un arreglo 'orden' con los IDs de truco en el orden deseado."));
    exit();
}

$show_id = (int)$data->show_id;

try {
    $db->beginTransaction();

    $query = "UPDATE show_trucos SET orden = :orden WHERE show_id = :show_id AND truco_id = :truco_id";
    $stmt = $db->prepare($query);

    foreach ($data->orden as $posicion => $truco_id) {
        $stmt->execute([
            ':orden' => $posicion + 1,
            ':show_id' => $show_id,
            ':truco_id' => (int)$truco_id
        ]);
    }

    $db->commit();
    http_response_code(200);
    echo json_encode(array("mensaje" => "Orden del setlist actualizado."));

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(array("mensaje" => "Error al reordenar el setlist: " . $e->getMessage()));
}
?>