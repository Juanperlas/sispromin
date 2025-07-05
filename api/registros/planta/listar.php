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
    "p.id",
    "p.codigo_registro",
    "p.fecha",
    "tp.nombre",
    "lp.nombre",
    "p.material_procesado",
    "cp.nombre",
    "p.produccion_cantidad",
    "p.peso_aproximado_kg",
    "carga_aproximada",
    "p.ley_inferido_metalurgista",
    "l.ley_laboratorio",
    "produccion_estimada"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por fecha inicio
if (isset($_POST["fecha_inicio"]) && $_POST["fecha_inicio"] !== "") {
    $fechaInicio = DateTime::createFromFormat('d/m/Y', $_POST["fecha_inicio"]);
    if ($fechaInicio) {
        $filtros[] = "p.fecha >= ?";
        $params[] = $fechaInicio->format('Y-m-d');
    }
}

// Filtro por fecha fin
if (isset($_POST["fecha_fin"]) && $_POST["fecha_fin"] !== "") {
    $fechaFin = DateTime::createFromFormat('d/m/Y', $_POST["fecha_fin"]);
    if ($fechaFin) {
        $filtros[] = "p.fecha <= ?";
        $params[] = $fechaFin->format('Y-m-d');
    }
}

// Filtro por turno
if (isset($_POST["turno_id"]) && $_POST["turno_id"] !== "") {
    $filtros[] = "p.turno_id = ?";
    $params[] = $_POST["turno_id"];
}

// Filtro por línea
if (isset($_POST["linea_id"]) && $_POST["linea_id"] !== "") {
    $filtros[] = "p.linea_id = ?";
    $params[] = $_POST["linea_id"];
}

// Filtro por concentrado
if (isset($_POST["concentrado_id"]) && $_POST["concentrado_id"] !== "") {
    $filtros[] = "p.concentrado_id = ?";
    $params[] = $_POST["concentrado_id"];
}

// Filtro por código
if (isset($_POST["codigo"]) && $_POST["codigo"] !== "") {
    $filtros[] = "p.codigo_registro LIKE ?";
    $params[] = "%" . $_POST["codigo"] . "%";
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "(p.codigo_registro LIKE ? OR tp.nombre LIKE ? OR lp.nombre LIKE ? OR cp.nombre LIKE ?)";
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
    $sqlBase = "FROM planta p 
                INNER JOIN turnos_planta tp ON p.turno_id = tp.id 
                INNER JOIN lineas_planta lp ON p.linea_id = lp.id 
                INNER JOIN concentrados_planta cp ON p.concentrado_id = cp.id
                LEFT JOIN laboratorio l ON l.tipo_registro = 'planta' AND l.registro_id = p.id";

    // Consulta para contar registros totales (sin filtros)
    $sqlTotal = "SELECT COUNT(*) as total $sqlBase";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "SELECT COUNT(*) as total $sqlBase $where";
    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
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
            $sqlBase $where";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY p.id DESC"; // Por defecto ordenar por ID descendente
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
            "concentrado_id" => $row["concentrado_id"],
            "concentrado_nombre" => $row["concentrado_nombre"],
            "material_procesado" => $row["material_procesado"],
            "produccion_cantidad" => $row["produccion_cantidad"],
            "peso_aproximado_kg" => $row["peso_aproximado_kg"],
            "carga_aproximada" => $row["carga_aproximada"],
            "ley_inferido_metalurgista" => $row["ley_inferido_metalurgista"],
            "codigo_muestra" => $row["codigo_muestra"],
            "ley_laboratorio" => $row["ley_laboratorio"],
            "produccion_estimada" => $row["produccion_estimada"],
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
    error_log("Error en listar.php (planta): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
