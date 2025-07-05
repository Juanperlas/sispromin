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
if (!tienePermiso("registros.produccion_mina.eliminar")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para eliminar registros de producción mina"]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Obtener ID del registro
$id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de registro no válido"]);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Iniciar transacción
    $conn->beginTransaction();

    // Verificar que el registro existe
    $registroExistente = $conexion->selectOne("SELECT id, codigo_registro FROM produccion_mina WHERE id = ?", [$id]);
    if (!$registroExistente) {
        throw new Exception("El registro no existe");
    }

    // Eliminar registros de laboratorio relacionados
    $conexion->delete("laboratorio", "tipo_registro = 'produccion_mina' AND registro_id = ?", [$id]);

    // Eliminar el registro principal
    $resultado = $conexion->delete("produccion_mina", "id = ?", [$id]);

    if (!$resultado) {
        throw new Exception("Error al eliminar el registro");
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => "Registro eliminado correctamente"
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al eliminar el registro: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en eliminar.php (produccion_mina): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
