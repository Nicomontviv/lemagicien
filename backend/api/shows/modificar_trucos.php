<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Sumamos OPTIONS
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

if (empty($data->show_id) || empty($data->truco_id) || empty($data->accion)) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Faltan datos obligatorios (show_id, truco_id, accion)."));
    exit();
}

$show_id = (int)$data->show_id;
$truco_id = (int)$data->truco_id;
$accion = strtolower(trim($data->accion));

if ($accion !== 'agregar' && $accion !== 'quitar') {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Acción no válida. Use 'agregar' o 'quitar'."));
    exit();
}

try {
    if ($accion === 'agregar') {
        // Escenario 1: Agregar truco (Validamos duplicados)
        $check = $db->prepare("SELECT COUNT(*) FROM show_trucos WHERE show_id = :show_id AND truco_id = :truco_id");
        $check->execute([':show_id' => $show_id, ':truco_id' => $truco_id]);
        
        if ($check->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(array("mensaje" => "El truco ya está asociado a este show."));
            exit();
        }

        // Calculamos el siguiente número de orden de la rutina
        $order_stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) FROM show_trucos WHERE show_id = :show_id");
        $order_stmt->execute([':show_id' => $show_id]);
        $nuevo_orden = $order_stmt->fetchColumn() + 1;

        $query = "INSERT INTO show_trucos (show_id, truco_id, orden) VALUES (:show_id, :truco_id, :orden)";
        $stmt = $db->prepare($query);
        $stmt->execute([':show_id' => $show_id, ':truco_id' => $truco_id, ':orden' => $nuevo_orden]);

        http_response_code(200);
        echo json_encode(array("mensaje" => "Truco agregado al show correctamente."));
        
    } else if ($accion === 'quitar') {
        // Escenario 2 y 3: Quitar truco verificando restricciones de cantidad
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM show_trucos WHERE show_id = :show_id");
        $count_stmt->execute([':show_id' => $show_id]);
        $total_trucos = $count_stmt->fetchColumn();

        // Escenario 3: Intentar dejar un show sin trucos
        if ($total_trucos <= 1) {
            http_response_code(400);
            echo json_encode(array("mensaje" => "Un show debe contener al menos un truco. No se puede quitar el último."));
            exit();
        }

        $query = "DELETE FROM show_trucos WHERE show_id = :show_id AND truco_id = :truco_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':show_id' => $show_id, ':truco_id' => $truco_id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("mensaje" => "Truco quitado del show correctamente."));
        } else {
            http_response_code(404);
            echo json_encode(array("mensaje" => "El truco no pertenecía a este show."));
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("mensaje" => "Error al modificar la rutina: " . $e->getMessage()));
}
?>