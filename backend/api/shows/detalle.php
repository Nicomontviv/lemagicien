<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Sumamos OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/conexion.php';

$database = new Conexion();
$db = $database->getConexion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "ID de show no válido."));
    exit();
}

$query_show = "SELECT id, nombre, descripcion, creado_en FROM shows WHERE id = :id";
$stmt_show = $db->prepare($query_show);
$stmt_show->bindParam(":id", $id);
$stmt_show->execute();

if ($stmt_show->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(array("mensaje" => "Show no encontrado."));
    exit();
}

$show = $stmt_show->fetch(PDO::FETCH_ASSOC);

// Buscamos los trucos de la rutina ordenados correctamente
$query_trucos = "SELECT t.id, t.nombre, t.descripcion, t.categoria, t.duracion_minutos, st.orden 
                 FROM show_trucos st 
                 JOIN trucos t ON st.truco_id = t.id 
                 WHERE st.show_id = :show_id 
                 ORDER BY st.orden ASC";
        
$stmt_trucos = $db->prepare($query_trucos);
$stmt_trucos->bindParam(":show_id", $id);
$stmt_trucos->execute();

$trucos = $stmt_trucos->fetchAll(PDO::FETCH_ASSOC);

// Escenario 1 y 2: Adjunta la descripción (si es null, viaja null de forma segura sin romperse)
$show['trucos'] = $trucos;

http_response_code(200);
echo json_encode($show);
?>