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
if (!tienePermiso("registros.flotacion.eliminar")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para eliminar registros de flotación"]);
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
    echo json_encode(["success" => false, "message" => "ID de registro no proporcionado"]);
    exit;
}

$id = intval($_POST["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Verificar que el registro existe
    $registro = $conexion->selectOne("SELECT id, codigo_registro FROM flotacion WHERE id = ?", [$id]);
    if (!$registro) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "El registro no existe"]);
        exit;
    }

    // Iniciar transacción
    $conn->beginTransaction();

    // Eliminar productos químicos asociados
    $conexion->delete("flotacion_productos", "flotacion_id = ?", [$id]);

    // Eliminar registro de laboratorio asociado
    $conexion->delete("laboratorio", "registro_id = ? AND tipo_registro = 'flotacion'", [$id]);

    // Eliminar el registro principal
    $resultado = $conexion->delete("flotacion", "id = ?", [$id]);

    if (!$resultado) {
        throw new Exception("Error al eliminar el registro");
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta exitosa
    $response = [
        "success" => true,
        "message" => "Registro eliminado correctamente",
        "data" => [
            "id" => $id,
            "codigo_registro" => $registro["codigo_registro"]
        ]
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al eliminar el registro: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en eliminar.php (flotacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
