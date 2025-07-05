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
    $sql = "SELECT p.id, p.codigo_registro, p.fecha, p.turno_id, p.linea_id, p.concentrado_id,
                   p.material_procesado, p.produccion_cantidad, p.peso_aproximado_kg, 
                   p.ley_inferido_metalurgista, p.creado_en,
                   tp.nombre as turno_nombre, lp.nombre as linea_nombre, cp.nombre as concentrado_nombre,
                   l.codigo_muestra, l.ley_laboratorio,
                   (p.produccion_cantidad * COALESCE(p.peso_aproximado_kg, 0)) as carga_aproximada,
                   CASE 
                       WHEN l.ley_laboratorio IS NOT NULL THEN p.material_procesado * l.ley_laboratorio
                       WHEN p.ley_inferido_metalurgista IS NOT NULL THEN p.material_procesado * p.ley_inferido_metalurgista
                       ELSE 0
                   END as produccion_estimada
            FROM planta p 
            INNER JOIN turnos_planta tp ON p.turno_id = tp.id 
            INNER JOIN lineas_planta lp ON p.linea_id = lp.id 
            INNER JOIN concentrados_planta cp ON p.concentrado_id = cp.id
            LEFT JOIN laboratorio l ON l.tipo_registro = 'planta' AND l.registro_id = p.id
            WHERE p.id = ?";
    
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
    error_log("Error en obtener.php (planta): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
