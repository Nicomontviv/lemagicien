<?php
// backend/api/trucos/buscar.php
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

$nombre = isset($_GET['nombre']) ? htmlspecialchars(strip_tags($_GET['nombre'])) : '';
$categoria = isset($_GET['categoria']) ? htmlspecialchars(strip_tags($_GET['categoria'])) : '';

$query = "SELECT id, nombre, descripcion, categoria, duracion_minutos, creado_en FROM trucos WHERE 1=1";

if (!empty($nombre)) {
    $query .= " AND nombre LIKE :nombre";
}
if (!empty($categoria)) {
    $query .= " AND categoria = :categoria";
}

$query .= " ORDER BY nombre ASC";
$stmt = $db->prepare($query);

if (!empty($nombre)) {
    $nombre_param = "%{$nombre}%";
    $stmt->bindParam(":nombre", $nombre_param);
}
if (!empty($categoria)) {
    $stmt->bindParam(":categoria", $categoria);
}

$stmt->execute();

if ($stmt->rowCount() > 0) {
    $trucos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode($trucos);
} else {
    // Si no hay trucos, devolvemos un array vacío con código 200 para que Vue no falle
    http_response_code(200);
    echo json_encode([]); 
}
?>