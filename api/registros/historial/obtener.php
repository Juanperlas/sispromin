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
if (!tienePermiso("registros.historial_general.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver el historial general"]);
    exit;
}

// Verificar que se proporcionó un ID y tipo
if (!isset($_GET["id"]) || empty($_GET["id"]) || !isset($_GET["tipo"]) || empty($_GET["tipo"])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "ID y tipo de registro no proporcionados"]);
    exit;
}

$id = intval($_GET["id"]);
$tipo = sanitizar($_GET["tipo"]);

// Validar tipo de registro
$tiposValidos = ['mina', 'planta', 'amalgamacion', 'flotacion'];
if (!in_array($tipo, $tiposValidos)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Tipo de registro no válido"]);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $registro = null;

    switch ($tipo) {
        case 'mina':
            $sql = "SELECT pm.id, pm.codigo_registro, pm.fecha, pm.turno_id,
                           pm.material_extraido, pm.desmonte, pm.ley_inferido_geologo,
                           pm.creado_en,
                           tm.nombre as turno_nombre,
                           fm.nombre as frente_nombre
                    FROM produccion_mina pm
                    INNER JOIN turnos_mina tm ON pm.turno_id = tm.id
                    INNER JOIN frentes_mina fm ON pm.frente_id = fm.id
                    WHERE pm.id = ?";
            break;

        case 'planta':
            $sql = "SELECT p.id, p.codigo_registro, p.fecha, p.turno_id,
                           p.material_procesado, p.produccion_cantidad, p.peso_aproximado_kg,
                           p.ley_inferido_metalurgista, p.creado_en,
                           tp.nombre as turno_nombre,
                           lp.nombre as linea_nombre,
                           cp.nombre as concentrado_nombre
                    FROM planta p
                    INNER JOIN turnos_planta tp ON p.turno_id = tp.id
                    INNER JOIN lineas_planta lp ON p.linea_id = lp.id
                    INNER JOIN concentrados_planta cp ON p.concentrado_id = cp.id
                    WHERE p.id = ?";
            break;

        case 'amalgamacion':
            $sql = "SELECT a.id, a.codigo_registro, a.fecha, a.turno_id,
                           a.cantidad_carga_concentrados, a.carga_mercurio_kg, a.soda_caustica_kg,
                           a.detergente_kg, a.cal_kg, a.lejia_litros, a.amalgamacion_gramos,
                           a.factor_conversion_amalg_au, a.mercurio_recuperado_kg, a.creado_en,
                           ta.nombre as turno_nombre,
                           la.nombre as linea_nombre,
                           am.nombre as amalgamador_nombre,
                           ca.nombre as carga_nombre
                    FROM amalgamacion a
                    INNER JOIN turnos_amalgamacion ta ON a.turno_id = ta.id
                    INNER JOIN lineas_amalgamacion la ON a.linea_id = la.id
                    INNER JOIN amalgamadores am ON a.amalgamador_id = am.id
                    INNER JOIN cargas_amalgamacion ca ON a.carga_id = ca.id
                    WHERE a.id = ?";
            break;

        case 'flotacion':
            $sql = "SELECT f.id, f.codigo_registro, f.fecha, f.turno_id,
                           f.carga_mineral_promedio, f.carga_mineral_extra, 
                           f.codigo_muestra_mat_extra, f.ley_inferido_metalurgista_extra,
                           f.creado_en,
                           tf.nombre as turno_nombre,
                           l.codigo_muestra,
                           l.ley_laboratorio
                    FROM flotacion f
                    INNER JOIN turnos_flotacion tf ON f.turno_id = tf.id
                    LEFT JOIN laboratorio l ON f.id = l.registro_id AND l.tipo_registro = 'flotacion'
                    WHERE f.id = ?";
            break;
    }

    $registro = $conexion->selectOne($sql, [$id]);

    if (!$registro) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Registro no encontrado"]);
        exit;
    }

    // Formatear fecha para mostrar
    $registro['fecha_formateada'] = date("d/m/Y", strtotime($registro["fecha"]));

    // Obtener datos adicionales específicos del tipo si es necesario
    if ($tipo === 'flotacion') {
        // Obtener productos utilizados
        $sqlProductos = "SELECT fp.id, fp.cantidad, p.nombre as producto_nombre, p.codigo
                         FROM flotacion_productos fp
                         INNER JOIN productos_flotacion p ON fp.producto_id = p.id
                         WHERE fp.flotacion_id = ?
                         ORDER BY p.nombre";

        $productos = $conexion->select($sqlProductos, [$id]);
        $registro['productos'] = $productos;
    }

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
    error_log("Error en obtener.php (historial): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
