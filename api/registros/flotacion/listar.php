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

// Parámetros de DataTables
$draw = isset($_POST["draw"]) ? intval($_POST["draw"]) : 1;
$start = isset($_POST["start"]) ? intval($_POST["start"]) : 0;
$length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
$search = isset($_POST["search"]["value"]) ? $_POST["search"]["value"] : "";

// Columna y dirección de ordenamiento
$orderColumn = isset($_POST["order"][0]["column"]) ? intval($_POST["order"][0]["column"]) : 0;
$orderDir = isset($_POST["order"][0]["dir"]) ? $_POST["order"][0]["dir"] : "desc";

// Mapeo de columnas para ordenamiento
$columns = [
    "f.id",
    "f.fecha",
    "f.codigo_registro",
    "tf.nombre",
    "f.carga_mineral_promedio",
    "l.ley_laboratorio",
    "f.carga_mineral_extra",
    "f.ley_inferido_metalurgista_extra",
    "resultado_esperado",
    "calificacion"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por fecha inicio
if (isset($_POST["fecha_inicio"]) && $_POST["fecha_inicio"] !== "") {
    $fechaInicio = DateTime::createFromFormat('d/m/Y', $_POST["fecha_inicio"]);
    if ($fechaInicio) {
        $filtros[] = "f.fecha >= ?";
        $params[] = $fechaInicio->format('Y-m-d');
    }
}

// Filtro por fecha fin
if (isset($_POST["fecha_fin"]) && $_POST["fecha_fin"] !== "") {
    $fechaFin = DateTime::createFromFormat('d/m/Y', $_POST["fecha_fin"]);
    if ($fechaFin) {
        $filtros[] = "f.fecha <= ?";
        $params[] = $fechaFin->format('Y-m-d');
    }
}

// Filtro por turno
if (isset($_POST["turno_id"]) && $_POST["turno_id"] !== "") {
    $filtros[] = "f.turno_id = ?";
    $params[] = $_POST["turno_id"];
}

// Filtro por código
if (isset($_POST["codigo"]) && $_POST["codigo"] !== "") {
    $filtros[] = "f.codigo_registro LIKE ?";
    $params[] = "%" . $_POST["codigo"] . "%";
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(f.codigo_registro LIKE ? OR tf.nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

// Construir la condición WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Consulta base con JOINs correctos
    $sqlBase = "FROM flotacion f 
                INNER JOIN turnos_flotacion tf ON f.turno_id = tf.id 
                LEFT JOIN laboratorio l ON f.id = l.registro_id AND l.tipo_registro = 'flotacion'";

    // Consulta para contar registros totales (sin filtros)
    $sqlTotal = "SELECT COUNT(*) as total $sqlBase";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "SELECT COUNT(*) as total $sqlBase $where";
    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
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
            $sqlBase $where";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY f.id DESC"; // Por defecto ordenar por ID descendente
    }

    // Aplicar paginación
    $sql .= " LIMIT $start, $length";

    // Ejecutar consulta
    $registros = $conexion->select($sql, $params);

    // Preparar datos para la respuesta
    $data = [];
    foreach ($registros as $row) {
        // Formatear fecha
        $fechaFormateada = date("d/m/Y", strtotime($row["fecha"]));

        // Agregar datos a la respuesta
        $data[] = [
            "id" => $row["id"],
            "codigo_registro" => $row["codigo_registro"],
            "fecha" => $row["fecha"],
            "fecha_formateada" => $fechaFormateada,
            "turno_id" => $row["turno_id"],
            "turno_nombre" => $row["turno_nombre"],
            "carga_mineral_promedio" => $row["carga_mineral_promedio"],
            "carga_mineral_extra" => $row["carga_mineral_extra"],
            "codigo_muestra_mat_extra" => $row["codigo_muestra_mat_extra"],
            "ley_inferido_metalurgista_extra" => $row["ley_inferido_metalurgista_extra"],
            "codigo_muestra" => $row["codigo_muestra"],
            "ley_laboratorio" => $row["ley_laboratorio"],
            "resultado_esperado" => $row["resultado_esperado"],
            "estado_calculo" => $row["estado_calculo"],
            "calificacion" => $row["calificacion"],
            "creado_en" => $row["creado_en"]
        ];
    }

    // Preparar respuesta para DataTables
    $response = [
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFiltered,
        "data" => $data
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "draw" => $draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Error al obtener los datos: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en listar.php (flotacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
