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
    "id",
    "tipo_registro",
    "codigo_registro",
    "fecha",
    "turno_nombre",
    "creado_en"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por fecha inicio
if (isset($_POST["fecha_inicio"]) && $_POST["fecha_inicio"] !== "") {
    $fechaInicio = DateTime::createFromFormat('d/m/Y', $_POST["fecha_inicio"]);
    if ($fechaInicio) {
        $filtros[] = "fecha >= ?";
        $params[] = $fechaInicio->format('Y-m-d');
    }
}

// Filtro por fecha fin
if (isset($_POST["fecha_fin"]) && $_POST["fecha_fin"] !== "") {
    $fechaFin = DateTime::createFromFormat('d/m/Y', $_POST["fecha_fin"]);
    if ($fechaFin) {
        $filtros[] = "fecha <= ?";
        $params[] = $fechaFin->format('Y-m-d');
    }
}

// Filtro por tipo de registro
if (isset($_POST["tipo_registro"]) && $_POST["tipo_registro"] !== "") {
    $filtros[] = "tipo_registro = ?";
    $params[] = $_POST["tipo_registro"];
}

// Filtro por código
if (isset($_POST["codigo"]) && $_POST["codigo"] !== "") {
    $filtros[] = "codigo_registro LIKE ?";
    $params[] = "%" . $_POST["codigo"] . "%";
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(codigo_registro LIKE ? OR turno_nombre LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

// Si no hay filtros de fecha, aplicar filtro por defecto (último día)
if (!isset($_POST["fecha_inicio"]) && !isset($_POST["fecha_fin"])) {
    $filtros[] = "fecha = CURDATE()";
}

// Construir la condición WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Consulta UNION para obtener todos los registros
    $sqlBase = "
        (SELECT 
            id, 
            'mina' as tipo_registro,
            codigo_registro, 
            fecha, 
            turno_id,
            creado_en,
            (SELECT nombre FROM turnos_mina WHERE id = produccion_mina.turno_id) as turno_nombre
         FROM produccion_mina)
        UNION ALL
        (SELECT 
            id, 
            'planta' as tipo_registro,
            codigo_registro, 
            fecha, 
            turno_id,
            creado_en,
            (SELECT nombre FROM turnos_planta WHERE id = planta.turno_id) as turno_nombre
         FROM planta)
        UNION ALL
        (SELECT 
            id, 
            'amalgamacion' as tipo_registro,
            codigo_registro, 
            fecha, 
            turno_id,
            creado_en,
            (SELECT nombre FROM turnos_amalgamacion WHERE id = amalgamacion.turno_id) as turno_nombre
         FROM amalgamacion)
        UNION ALL
        (SELECT 
            id, 
            'flotacion' as tipo_registro,
            codigo_registro, 
            fecha, 
            turno_id,
            creado_en,
            (SELECT nombre FROM turnos_flotacion WHERE id = flotacion.turno_id) as turno_nombre
         FROM flotacion)
    ";

    // Consulta para contar registros totales (sin filtros, pero con filtro por defecto de fecha)
    $sqlTotalBase = "SELECT COUNT(*) as total FROM ($sqlBase) as todos_registros";
    if (empty($filtros)) {
        $sqlTotalBase .= " WHERE fecha = CURDATE()";
    }
    $resultadoTotal = $conexion->selectOne($sqlTotalBase);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "SELECT COUNT(*) as total FROM ($sqlBase) as todos_registros $where";
    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "SELECT * FROM ($sqlBase) as todos_registros $where";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY id DESC"; // Por defecto ordenar por ID descendente
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

        // Formatear fecha de creación
        $creadoFormateado = date("d/m/Y H:i", strtotime($row["creado_en"]));

        // Agregar datos a la respuesta
        $data[] = [
            "id" => $row["id"],
            "tipo_registro" => $row["tipo_registro"],
            "codigo_registro" => $row["codigo_registro"],
            "fecha" => $row["fecha"],
            "fecha_formateada" => $fechaFormateada,
            "turno_id" => $row["turno_id"],
            "turno_nombre" => $row["turno_nombre"],
            "creado_en" => $row["creado_en"],
            "creado_formateado" => $creadoFormateado
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
    error_log("Error en listar.php (historial): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
