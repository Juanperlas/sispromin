<?php
// Incluir archivos necesarios
require_once "../../../../db/funciones.php";
require_once "../../../../db/conexion.php";

// Configurar headers para JSON
header('Content-Type: application/json');

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

// Verificar permisos
if (!tienePermiso('controles.flotacion.productos.ver')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver productos de flotación"]);
    exit;
}

try {
    $conexion = new Conexion();

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
        "codigo",
        "nombre",
        "creado_en"
    ];

    // Filtros adicionales
    $filtros = [];
    $params = [];

    // Filtro por código
    if (isset($_POST["codigo"]) && $_POST["codigo"] !== "") {
        $filtros[] = "codigo LIKE ?";
        $params[] = "%" . $_POST["codigo"] . "%";
    }

    // Filtro por nombre
    if (isset($_POST["nombre"]) && $_POST["nombre"] !== "") {
        $filtros[] = "nombre LIKE ?";
        $params[] = "%" . $_POST["nombre"] . "%";
    }

    // Filtro de búsqueda global
    if ($search !== "") {
        $filtros[] = "(codigo LIKE ? OR nombre LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam]);
    }

    // Construir la condición WHERE
    $where = "";
    if (!empty($filtros)) {
        $where = "WHERE " . implode(" AND ", $filtros);
    }

    // Consulta para contar registros totales (sin filtros)
    $sqlTotal = "SELECT COUNT(*) as total FROM productos_flotacion";
    $resultadoTotal = $conexion->selectOne($sqlTotal);
    $totalRecords = $resultadoTotal["total"];

    // Consulta para contar registros filtrados
    $sqlFiltered = "SELECT COUNT(*) as total FROM productos_flotacion $where";
    $resultadoFiltered = $conexion->selectOne($sqlFiltered, $params);
    $totalFiltered = $resultadoFiltered["total"];

    // Consulta principal para obtener los datos
    $sql = "SELECT id, codigo, nombre, creado_en FROM productos_flotacion $where";

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY id DESC";
    }

    // Aplicar paginación
    $sql .= " LIMIT $start, $length";

    // Ejecutar consulta
    $productos = $conexion->select($sql, $params);

    // Preparar datos para la respuesta
    $data = [];
    foreach ($productos as $row) {
        // Formatear fecha
        $fechaFormateada = date("d/m/Y H:i", strtotime($row["creado_en"]));

        // Agregar datos a la respuesta
        $data[] = [
            "id" => $row["id"],
            "codigo" => $row["codigo"],
            "nombre" => $row["nombre"],
            "creado_en" => $row["creado_en"],
            "fecha_creacion_formateada" => $fechaFormateada
        ];
    }

    // Preparar respuesta para DataTables
    $response = [
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFiltered,
        "data" => $data
    ];

    echo json_encode($response);
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "draw" => isset($draw) ? $draw : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Error al obtener los datos: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en listar.php (productos_flotacion): " . $e->getMessage());

    echo json_encode($response);
}
