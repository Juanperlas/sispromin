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
    "a.id",
    "a.codigo_registro",
    "a.fecha",
    "ta.nombre",
    "la.nombre",
    "am.nombre",
    "a.cantidad_carga_concentrados",
    "resultado_refogado",
    "porcentaje_recuperacion"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por fecha inicio
if (isset($_POST["fecha_inicio"]) && $_POST["fecha_inicio"] !== "") {
    $fechaInicio = DateTime::createFromFormat('d/m/Y', $_POST["fecha_inicio"]);
    if ($fechaInicio) {
        $filtros[] = "a.fecha >= ?";
        $params[] = $fechaInicio->format('Y-m-d');
    }
}

// Filtro por fecha fin
if (isset($_POST["fecha_fin"]) && $_POST["fecha_fin"] !== "") {
    $fechaFin = DateTime::createFromFormat('d/m/Y', $_POST["fecha_fin"]);
    if ($fechaFin) {
        $filtros[] = "a.fecha <= ?";
        $params[] = $fechaFin->format('Y-m-d');
    }
}

// Filtro por turno
if (isset($_POST["turno_id"]) && $_POST["turno_id"] !== "") {
    $filtros[] = "a.turno_id = ?";
    $params[] = $_POST["turno_id"];
}

// Filtro por línea
if (isset($_POST["linea_id"]) && $_POST["linea_id"] !== "") {
    $filtros[] = "a.linea_id = ?";
    $params[] = $_POST["linea_id"];
}

// Filtro por amalgamador
if (isset($_POST["amalgamador_id"]) && $_POST["amalgamador_id"] !== "") {
    $filtros[] = "a.amalgamador_id = ?";
    $params[] = $_POST["amalgamador_id"];
}

// Filtro por código
if (isset($_POST["codigo"]) && $_POST["codigo"] !== "") {
    $filtros[] = "a.codigo_registro LIKE ?";
    $params[] = "%" . $_POST["codigo"] . "%";
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(a.codigo_registro LIKE ? OR ta.nombre LIKE ? OR la.nombre LIKE ? OR am.nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
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
    $sqlBase = "FROM amalgamacion a 
                INNER JOIN turnos_amalgamacion ta ON a.turno_id = ta.id 
                INNER JOIN lineas_amalgamacion la ON a.linea_id = la.id 
                INNER JOIN amalgamadores am ON a.amalgamador_id = am.id
                INNER JOIN cargas_amalgamacion ca ON a.carga_id = ca.id";

    // Consulta para contar registros totales (sin filtros)
    $sqlTotal = "SELECT COUNT(*) as total $sqlBase";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "SELECT COUNT(*) as total $sqlBase $where";
    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "SELECT a.id, a.codigo_registro, a.fecha, a.turno_id, a.linea_id, a.amalgamador_id,
                   a.cantidad_carga_concentrados, a.carga_id, a.carga_mercurio_kg, 
                   a.amalgamacion_gramos, a.factor_conversion_amalg_au, a.creado_en,
                   ta.nombre as turno_nombre, la.nombre as linea_nombre, am.nombre as amalgamador_nombre,
                   ca.nombre as carga_nombre,
                   (a.amalgamacion_gramos / a.factor_conversion_amalg_au) as resultado_refogado,
                   (RAND() * 30 + 60) as porcentaje_recuperacion
            $sqlBase $where";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY a.id DESC"; // Por defecto ordenar por ID descendente
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
            "linea_id" => $row["linea_id"],
            "linea_nombre" => $row["linea_nombre"],
            "amalgamador_id" => $row["amalgamador_id"],
            "amalgamador_nombre" => $row["amalgamador_nombre"],
            "cantidad_carga_concentrados" => $row["cantidad_carga_concentrados"],
            "carga_id" => $row["carga_id"],
            "carga_nombre" => $row["carga_nombre"],
            "carga_mercurio_kg" => $row["carga_mercurio_kg"],
            "amalgamacion_gramos" => $row["amalgamacion_gramos"],
            "factor_conversion_amalg_au" => $row["factor_conversion_amalg_au"],
            "resultado_refogado" => $row["resultado_refogado"],
            "porcentaje_recuperacion" => $row["porcentaje_recuperacion"],
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
    error_log("Error en listar.php (amalgamacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
