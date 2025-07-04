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
if (!tienePermiso("controles.mina.frentes.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver frentes de mina"]);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "ID de frente no proporcionado"]);
    exit;
}

$id = intval($_GET["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Obtener datos del frente
    $sql = "SELECT id, codigo, nombre, creado_en FROM frentes_mina WHERE id = ?";
    $frente = $conexion->selectOne($sql, [$id]);

    if (!$frente) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Frente no encontrado"]);
        exit;
    }

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => $frente
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos del frente: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener.php (frentes_mina): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
