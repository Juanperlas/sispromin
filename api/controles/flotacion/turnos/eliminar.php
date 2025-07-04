<?php
// Incluir archivos necesarios
require_once "../../../../db/funciones.php";
require_once "../../../../db/conexion.php";

// Verificar si es una solicitud AJAX
$esAjax = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

if (!$esAjax) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso no permitido"]);
    exit;
}

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

// Verificar permiso
if (!tienePermiso("controles.flotacion.turnos.eliminar")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para eliminar turnos de flotación"]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_POST["id"]) || empty($_POST["id"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de turno no proporcionado"]);
    exit;
}

$id = intval($_POST["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar que el turno existe
    $turnoExistente = $conexion->selectOne("SELECT id, codigo, nombre FROM turnos_flotacion WHERE id = ?", [$id]);
    if (!$turnoExistente) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "El turno no existe"]);
        exit;
    }

    // Verificar si el turno está siendo utilizado en registros
    $enUso = $conexion->selectOne("SELECT COUNT(*) as total FROM flotacion WHERE turno_id = ?", [$id]);
    if ($enUso && $enUso["total"] > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "No se puede eliminar el turno porque está siendo utilizado en registros de flotación"
        ]);
        exit;
    }

    // Eliminar el turno
    $resultado = $conexion->delete("turnos_flotacion", "id = ?", [$id]);

    if ($resultado) {
        $response = [
            "success" => true,
            "message" => "Turno eliminado correctamente"
        ];
    } else {
        throw new Exception("Error al eliminar el turno");
    }
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al eliminar el turno: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en eliminar.php (turnos_flotacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
