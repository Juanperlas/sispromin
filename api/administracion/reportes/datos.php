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
if (!tienePermiso("administracion.reportes.ver")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para ver reportes"]);
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
    "fecha",
    "total_mina",
    "total_planta",
    "total_amalgamacion",
    "total_flotacion",
    "efectividad_mina_planta",
    "efectividad_mina_amalgamacion",
    "efectividad_planta_amalgamacion"
];

// Filtros adicionales
$filtros = [];
$params = [];

// Filtro por fecha inicio
if (isset($_POST["fecha_inicio"]) && $_POST["fecha_inicio"] !== "") {
    $fechaInicio = DateTime::createFromFormat('d/m/Y', $_POST["fecha_inicio"]);
    if ($fechaInicio) {
        $filtros[] = "fechas_unicas.fecha >= ?";
        $params[] = $fechaInicio->format('Y-m-d');
    }
}

// Filtro por fecha fin
if (isset($_POST["fecha_fin"]) && $_POST["fecha_fin"] !== "") {
    $fechaFin = DateTime::createFromFormat('d/m/Y', $_POST["fecha_fin"]);
    if ($fechaFin) {
        $filtros[] = "fechas_unicas.fecha <= ?";
        $params[] = $fechaFin->format('Y-m-d');
    }
}

// Si no hay filtros de fecha, mostrar solo el día actual por defecto
if (empty($filtros)) {
    $filtros[] = "fechas_unicas.fecha = ?";
    $params[] = date('Y-m-d');
}

// Filtro de búsqueda global
if ($search !== "") {
    $filtros[] = "fechas_unicas.fecha LIKE ?";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam]);
}

// Construir la condición WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Función para calcular efectividad y color
    function calcularEfectividad($valor1, $valor2)
    {
        if ($valor2 == 0) return ['efectividad' => 0, 'color' => 'danger'];

        $efectividad = ($valor1 / $valor2) * 100;

        if ($efectividad > 85) {
            $color = 'success';
        } elseif ($efectividad > 70) {
            $color = 'warning';
        } else {
            $color = 'danger';
        }

        return ['efectividad' => $efectividad, 'color' => $color];
    }

    // Consulta principal para obtener datos agrupados por fecha
    $sql = "SELECT 
            fechas_unicas.fecha,
            
            -- MINA: Suma de (material_extraido * ley) por cada registro individual
            COALESCE((
                SELECT SUM(pm.material_extraido * COALESCE(l_mina.ley_laboratorio, pm.ley_inferido_geologo))
                FROM produccion_mina pm
                LEFT JOIN laboratorio l_mina ON pm.id = l_mina.registro_id AND l_mina.tipo_registro = 'produccion_mina'
                WHERE pm.fecha = fechas_unicas.fecha
            ), 0) as total_mina,
            
            -- PLANTA: Suma de (material_procesado * ley) por cada registro individual
            COALESCE((
                SELECT SUM(p.material_procesado * COALESCE(l_planta.ley_laboratorio, p.ley_inferido_metalurgista))
                FROM planta p
                LEFT JOIN laboratorio l_planta ON p.id = l_planta.registro_id AND l_planta.tipo_registro = 'planta'
                WHERE p.fecha = fechas_unicas.fecha
            ), 0) as total_planta,
            
            -- AMALGAMACIÓN: Suma de (amalgamacion_gramos / factor_conversion) por cada registro individual
            COALESCE((
                SELECT SUM(a.amalgamacion_gramos / a.factor_conversion_amalg_au)
                FROM amalgamacion a
                WHERE a.fecha = fechas_unicas.fecha
            ), 0) as total_amalgamacion,
            
            -- FLOTACIÓN: Suma de resultados esperados por cada registro individual
            COALESCE((
                SELECT SUM(
                    (f.carga_mineral_promedio * COALESCE(l_flotacion.ley_laboratorio, 0)) + 
                    (COALESCE(f.carga_mineral_extra, 0) * COALESCE(f.ley_inferido_metalurgista_extra, 0))
                )
                FROM flotacion f
                LEFT JOIN laboratorio l_flotacion ON f.id = l_flotacion.registro_id AND l_flotacion.tipo_registro = 'flotacion'
                WHERE f.fecha = fechas_unicas.fecha
            ), 0) as total_flotacion,
            
            -- Contadores para verificación
            COALESCE((SELECT COUNT(*) FROM produccion_mina pm WHERE pm.fecha = fechas_unicas.fecha), 0) as registros_mina,
            COALESCE((SELECT COUNT(*) FROM planta p WHERE p.fecha = fechas_unicas.fecha), 0) as registros_planta,
            COALESCE((SELECT COUNT(*) FROM amalgamacion a WHERE a.fecha = fechas_unicas.fecha), 0) as registros_amalgamacion,
            COALESCE((SELECT COUNT(*) FROM flotacion f WHERE f.fecha = fechas_unicas.fecha), 0) as registros_flotacion
            
        FROM (
            SELECT DISTINCT pm.fecha as fecha FROM produccion_mina pm
            UNION 
            SELECT DISTINCT p.fecha as fecha FROM planta p
            UNION
            SELECT DISTINCT a.fecha as fecha FROM amalgamacion a
            UNION
            SELECT DISTINCT f.fecha as fecha FROM flotacion f
        ) fechas_unicas
        
        $where";

    // Ejecutar consulta para obtener todos los datos (para contar)
    $todosLosDatos = $conexion->select($sql, $params);
    $totalRecords = count($todosLosDatos);
    $totalFiltered = $totalRecords;

    // Aplicar ordenamiento
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY fechas_unicas.fecha DESC";
    }

    // Aplicar paginación
    $sql .= " LIMIT $start, $length";
    $registros = $conexion->select($sql, $params);

    // Preparar datos para la respuesta
    $data = [];
    foreach ($registros as $row) {
        // Formatear fecha
        $fechaFormateada = date("d/m/Y", strtotime($row["fecha"]));

        // Calcular efectividades
        $efectividadMinaPlanta = calcularEfectividad($row["total_planta"], $row["total_mina"]);
        $efectividadMinaAmalgamacion = calcularEfectividad($row["total_amalgamacion"], $row["total_mina"]);
        $efectividadPlantaAmalgamacion = calcularEfectividad($row["total_amalgamacion"], $row["total_planta"]);

        $data[] = [
            "fecha" => $row["fecha"],
            "fecha_formateada" => $fechaFormateada,
            "total_mina" => floatval($row["total_mina"]),
            "total_planta" => floatval($row["total_planta"]),
            "total_amalgamacion" => floatval($row["total_amalgamacion"]),
            "total_flotacion" => floatval($row["total_flotacion"]),
            "registros_mina" => intval($row["registros_mina"]),
            "registros_planta" => intval($row["registros_planta"]),
            "registros_amalgamacion" => intval($row["registros_amalgamacion"]),
            "registros_flotacion" => intval($row["registros_flotacion"]),
            "efectividad_mina_planta" => $efectividadMinaPlanta["efectividad"],
            "efectividad_mina_planta_color" => $efectividadMinaPlanta["color"],
            "efectividad_mina_amalgamacion" => $efectividadMinaAmalgamacion["efectividad"],
            "efectividad_mina_amalgamacion_color" => $efectividadMinaAmalgamacion["color"],
            "efectividad_planta_amalgamacion" => $efectividadPlantaAmalgamacion["efectividad"],
            "efectividad_planta_amalgamacion_color" => $efectividadPlantaAmalgamacion["color"]
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
    error_log("Error en datos.php (administracion/reportes): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
