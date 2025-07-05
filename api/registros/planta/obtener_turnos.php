<?php
// Incluir archivos necesarios
require_once "../../../db/funciones.php";
require_once "../../../db/conexion.php";

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
if (!tienePermiso("registros.planta.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver registros de producción planta"]);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Consulta para obtener todos los turnos de planta activos
    $sql = "SELECT id, codigo, nombre FROM turnos_planta ORDER BY codigo ASC";
    $turnos = $conexion->select($sql);

    // Preparar respuesta
    if (!empty($turnos)) {
        $response = [
            "success" => true,
            "data" => $turnos,
            "message" => "Turnos obtenidos correctamente"
        ];
    } else {
        $response = [
            "success" => true,
            "data" => [],
            "message" => "No hay turnos configurados"
        ];
    }
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "data" => [],
        "message" => "Error al obtener los turnos: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener_turnos.php (registros/planta): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
