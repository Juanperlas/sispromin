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
if (!tienePermiso("controles.amalgamacion.cargas.eliminar")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para eliminar tipos de carga de amalgamación"]);
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
    echo json_encode(["success" => false, "message" => "ID de tipo de carga no proporcionado"]);
    exit;
}

$id = intval($_POST["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Verificar que el tipo de carga existe
    $cargaExistente = $conexion->selectOne("SELECT id, codigo, nombre FROM cargas_amalgamacion WHERE id = ?", [$id]);
    if (!$cargaExistente) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "El tipo de carga no existe"]);
        exit;
    }

    // Verificar si el tipo de carga está siendo utilizado en registros
    $enUso = $conexion->selectOne("SELECT COUNT(*) as total FROM amalgamacion WHERE carga_id = ?", [$id]);
    if ($enUso && $enUso["total"] > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "No se puede eliminar el tipo de carga '{$cargaExistente['nombre']}' porque está siendo utilizado en registros de amalgamación"
        ]);
        exit;
    }

    // Eliminar el tipo de carga
    $resultado = $conexion->delete("cargas_amalgamacion", "id = ?", [$id]);

    if ($resultado) {
        $response = [
            "success" => true,
            "message" => "Tipo de carga '{$cargaExistente['nombre']}' eliminado correctamente"
        ];
    } else {
        throw new Exception("Error al eliminar el tipo de carga");
    }
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al eliminar el tipo de carga: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en eliminar.php (cargas): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
