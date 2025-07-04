<?php
// Incluir archivos necesarios
require_once "../../../../db/funciones.php";
require_once "../../../../db/conexion.php";

// Configurar headers para JSON
header('Content-Type: application/json');

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
if (!tienePermiso("controles.flotacion.productos.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver productos de flotación"]);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de producto no proporcionado"]);
    exit;
}

$id = intval($_GET["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Obtener datos del producto
    $sql = "SELECT id, codigo, nombre, creado_en FROM productos_flotacion WHERE id = ?";
    $producto = $conexion->selectOne($sql, [$id]);

    if (!$producto) {
        echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
        exit;
    }

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => $producto
    ];

    echo json_encode($response);
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos del producto: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener.php (productos_flotacion): " . $e->getMessage());

    echo json_encode($response);
}
