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
if (!tienePermiso("registros.amalgamacion.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver registros de amalgamación"]);
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
    $sql = "SELECT a.id, a.codigo_registro, a.fecha, a.turno_id, a.linea_id, a.amalgamador_id,
                   a.cantidad_carga_concentrados, a.carga_id, a.carga_mercurio_kg, 
                   a.soda_caustica_kg, a.detergente_kg, a.cal_kg, a.lejia_litros,
                   a.amalgamacion_gramos, a.mercurio_recuperado_kg, a.factor_conversion_amalg_au, a.creado_en,
                   ta.nombre as turno_nombre, la.nombre as linea_nombre, am.nombre as amalgamador_nombre,
                   ca.nombre as carga_nombre,
                   (a.amalgamacion_gramos / a.factor_conversion_amalg_au) as resultado_refogado,
                   (RAND() * 30 + 70) as porcentaje_recuperacion
            FROM amalgamacion a 
            INNER JOIN turnos_amalgamacion ta ON a.turno_id = ta.id 
            INNER JOIN lineas_amalgamacion la ON a.linea_id = la.id 
            INNER JOIN amalgamadores am ON a.amalgamador_id = am.id
            INNER JOIN cargas_amalgamacion ca ON a.carga_id = ca.id
            WHERE a.id = ?";
    
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
    error_log("Error en obtener.php (amalgamacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
