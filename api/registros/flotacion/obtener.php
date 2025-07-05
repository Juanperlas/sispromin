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
if (!tienePermiso("registros.produccion_mina.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver registros de producción mina"]);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "ID de registro no proporcionado"]);
    exit;
}

$id = intval($_GET["id"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Obtener datos del registro con JOINs
    $sql = "SELECT pm.id, pm.codigo_registro, pm.fecha, pm.turno_id, pm.frente_id,
                   pm.material_extraido, pm.desmonte, pm.ley_inferido_geologo, pm.creado_en,
                   tm.nombre as turno_nombre, fm.nombre as frente_nombre,
                   l.codigo_muestra, l.ley_laboratorio,
                   CASE 
                       WHEN l.ley_laboratorio IS NOT NULL THEN pm.material_extraido * l.ley_laboratorio
                       WHEN pm.ley_inferido_geologo IS NOT NULL THEN pm.material_extraido * pm.ley_inferido_geologo
                       ELSE 0
                   END as valor_calculado
            FROM produccion_mina pm 
            INNER JOIN turnos_mina tm ON pm.turno_id = tm.id 
            INNER JOIN frentes_mina fm ON pm.frente_id = fm.id 
            LEFT JOIN laboratorio l ON l.tipo_registro = 'produccion_mina' AND l.registro_id = pm.id
            WHERE pm.id = ?";
    
    $registro = $conexion->selectOne($sql, [$id]);

    if (!$registro) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Registro no encontrado"]);
        exit;
    }

    // Formatear fecha para mostrar
    $registro['fecha_formateada'] = date("d/m/Y", strtotime($registro["fecha"]));

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => $registro
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los datos del registro: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener.php (produccion_mina): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
