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
if (!tienePermiso("registros.flotacion.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver registros de flotación"]);
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

    // Obtener datos del registro con JOINs correctos
    $sql = "SELECT f.id, f.codigo_registro, f.fecha, f.turno_id,
                   f.carga_mineral_promedio, f.carga_mineral_extra, 
                   f.codigo_muestra_mat_extra, f.ley_inferido_metalurgista_extra,
                   f.creado_en,
                   tf.nombre as turno_nombre,
                   l.codigo_muestra,
                   l.ley_laboratorio,
                   -- Cálculo del resultado esperado
                   COALESCE(
                       (f.carga_mineral_promedio * COALESCE(l.ley_laboratorio, 0)) + 
                       (COALESCE(f.carga_mineral_extra, 0) * COALESCE(f.ley_inferido_metalurgista_extra, 0)),
                       0
                   ) as resultado_esperado,
                   -- Información sobre el estado del cálculo
                   CASE 
                       WHEN l.ley_laboratorio IS NULL THEN 'sin_laboratorio'
                       WHEN f.carga_mineral_extra IS NOT NULL AND f.carga_mineral_extra > 0 AND f.ley_inferido_metalurgista_extra IS NULL THEN 'falta_ley_extra'
                       WHEN f.carga_mineral_extra IS NOT NULL AND f.carga_mineral_extra > 0 AND f.ley_inferido_metalurgista_extra IS NOT NULL AND l.ley_laboratorio IS NOT NULL THEN 'completo'
                       WHEN f.carga_mineral_extra IS NULL AND l.ley_laboratorio IS NOT NULL THEN 'completo_sin_extra'
                       ELSE 'parcial'
                   END as estado_calculo,
                   -- Calificación aleatoria (como solicitaste)
                   (RAND() * 30 + 70) as calificacion
            FROM flotacion f 
            INNER JOIN turnos_flotacion tf ON f.turno_id = tf.id 
            LEFT JOIN laboratorio l ON f.id = l.registro_id AND l.tipo_registro = 'flotacion'
            WHERE f.id = ?";

    $registro = $conexion->selectOne($sql, [$id]);

    if (!$registro) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Registro no encontrado"]);
        exit;
    }

    // Formatear fecha para mostrar
    $registro['fecha_formateada'] = date("d/m/Y", strtotime($registro["fecha"]));

    // Obtener productos utilizados
    $sqlProductos = "SELECT fp.id, fp.cantidad, p.nombre as producto_nombre, p.codigo
                     FROM flotacion_productos fp
                     INNER JOIN productos_flotacion p ON fp.producto_id = p.id
                     WHERE fp.flotacion_id = ?
                     ORDER BY p.nombre";

    $productos = $conexion->select($sqlProductos, [$id]);
    $registro['productos'] = $productos;

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
    error_log("Error en obtener.php (flotacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
