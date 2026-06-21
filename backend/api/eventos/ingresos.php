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

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Filtramos solo los eventos marcados como "Realizado"
$query = "SELECT SUM(monto) as total_ingresos, COUNT(id) as cantidad_eventos 
          FROM eventos 
          WHERE estado = 'Realizado' AND MONTH(fecha) = :mes AND YEAR(fecha) = :anio";

$stmt = $db->prepare($query);
$stmt->bindParam(":mes", $mes);
$stmt->bindParam(":anio", $anio);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
$total = $resultado['total_ingresos'] !== null ? (float)$resultado['total_ingresos'] : 0.00;

http_response_code(200);
if ($total > 0) {
    echo json_encode(array(
        "total" => $total, 
        "eventos_realizados" => (int)$resultado['cantidad_eventos'],
        "periodo" => "$mes/$anio"
    ));
} else {
    echo json_encode(array(
        "total" => 0.00, 
        "mensaje" => "No existen ingresos registrados en este período.",
        "periodo" => "$mes/$anio"
    ));
}
?>