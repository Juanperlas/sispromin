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
    "pm.id",
    "pm.codigo_registro",
    "pm.fecha",
    "tm.nombre",
    "fm.nombre",
    "pm.material_extraido",
    "pm.desmonte",
    "pm.ley_inferido_geologo",
    "l.ley_laboratorio",
    "valor_calculado"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por fecha inicio
if (isset($_POST["fecha_inicio"]) && $_POST["fecha_inicio"] !== "") {
    $fechaInicio = DateTime::createFromFormat('d/m/Y', $_POST["fecha_inicio"]);
    if ($fechaInicio) {
        $filtros[] = "pm.fecha >= ?";
        $params[] = $fechaInicio->format('Y-m-d');
    }
}

// Filtro por fecha fin
if (isset($_POST["fecha_fin"]) && $_POST["fecha_fin"] !== "") {
    $fechaFin = DateTime::createFromFormat('d/m/Y', $_POST["fecha_fin"]);
    if ($fechaFin) {
        $filtros[] = "pm.fecha <= ?";
        $params[] = $fechaFin->format('Y-m-d');
    }
}

// Filtro por turno
if (isset($_POST["turno_id"]) && $_POST["turno_id"] !== "") {
    $filtros[] = "pm.turno_id = ?";
    $params[] = $_POST["turno_id"];
}

// Filtro por frente
if (isset($_POST["frente_id"]) && $_POST["frente_id"] !== "") {
    $filtros[] = "pm.frente_id = ?";
    $params[] = $_POST["frente_id"];
}

// Filtro por código
if (isset($_POST["codigo"]) && $_POST["codigo"] !== "") {
    $filtros[] = "pm.codigo_registro LIKE ?";
    $params[] = "%" . $_POST["codigo"] . "%";
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(pm.codigo_registro LIKE ? OR tm.nombre LIKE ? OR fm.nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Construir la condición WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Consulta base con JOINs
    $sqlBase = "FROM produccion_mina pm 
                INNER JOIN turnos_mina tm ON pm.turno_id = tm.id 
                INNER JOIN frentes_mina fm ON pm.frente_id = fm.id 
                LEFT JOIN laboratorio l ON l.tipo_registro = 'produccion_mina' AND l.registro_id = pm.id";

    // Consulta para contar registros totales (sin filtros)
    $sqlTotal = "SELECT COUNT(*) as total $sqlBase";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "SELECT COUNT(*) as total $sqlBase $where";
    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "SELECT pm.id, pm.codigo_registro, pm.fecha, pm.turno_id, pm.frente_id,
                   pm.material_extraido, pm.desmonte, pm.ley_inferido_geologo, pm.creado_en,
                   tm.nombre as turno_nombre, fm.nombre as frente_nombre,
                   l.codigo_muestra, l.ley_laboratorio,
                   CASE 
                       WHEN l.ley_laboratorio IS NOT NULL THEN pm.material_extraido * l.ley_laboratorio
                       WHEN pm.ley_inferido_geologo IS NOT NULL THEN pm.material_extraido * pm.ley_inferido_geologo
                       ELSE 0
                   END as valor_calculado
            $sqlBase $where";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY pm.id DESC"; // Por defecto ordenar por ID descendente
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
            "frente_id" => $row["frente_id"],
            "frente_nombre" => $row["frente_nombre"],
            "material_extraido" => $row["material_extraido"],
            "desmonte" => $row["desmonte"],
            "ley_inferido_geologo" => $row["ley_inferido_geologo"],
            "codigo_muestra" => $row["codigo_muestra"],
            "ley_laboratorio" => $row["ley_laboratorio"],
            "valor_calculado" => $row["valor_calculado"],
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
    error_log("Error en listar.php (produccion_mina): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
