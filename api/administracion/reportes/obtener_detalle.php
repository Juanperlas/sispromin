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

// Verificar que se proporcionó una fecha
if (!isset($_GET["fecha"]) || empty($_GET["fecha"])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Fecha no proporcionada"]);
    exit;
}

$fecha = sanitizar($_GET["fecha"]);

try {
    // Conexión a la base de datos
    $conexion = new Conexion();

    // Función para calcular efectividad
    function calcularEfectividadDetalle($valor1, $valor2)
    {
        if ($valor2 == 0) {
            return [
                'efectividad' => 0,
                'color' => 'danger',
                'texto' => 'Sin datos'
            ];
        }

        $efectividad = ($valor1 / $valor2) * 100;

        if ($efectividad > 85) {
            $color = 'success';
            $texto = 'Excelente';
        } elseif ($efectividad > 70) {
            $color = 'warning';
            $texto = 'Bueno';
        } else {
            $color = 'danger';
            $texto = 'Deficiente';
        }

        return [
            'efectividad' => $efectividad,
            'color' => $color,
            'texto' => $texto
        ];
    }

    // Obtener resumen del día usando subconsultas para evitar duplicaciones
    $sqlResumen = "SELECT 
                -- MINA: Suma de (material_extraido * ley) por cada registro individual
                COALESCE((
                    SELECT SUM(pm.material_extraido * COALESCE(l_mina.ley_laboratorio, pm.ley_inferido_geologo))
                    FROM produccion_mina pm
                    LEFT JOIN laboratorio l_mina ON pm.id = l_mina.registro_id AND l_mina.tipo_registro = 'produccion_mina'
                    WHERE pm.fecha = ?
                ), 0) as total_mina,
                
                -- PLANTA: Suma de (material_procesado * ley) por cada registro individual
                COALESCE((
                    SELECT SUM(p.material_procesado * COALESCE(l_planta.ley_laboratorio, p.ley_inferido_metalurgista))
                    FROM planta p
                    LEFT JOIN laboratorio l_planta ON p.id = l_planta.registro_id AND l_planta.tipo_registro = 'planta'
                    WHERE p.fecha = ?
                ), 0) as total_planta,
                
                -- AMALGAMACIÓN: Suma de (amalgamacion_gramos / factor_conversion) por cada registro individual
                COALESCE((
                    SELECT SUM(a.amalgamacion_gramos / a.factor_conversion_amalg_au)
                    FROM amalgamacion a
                    WHERE a.fecha = ?
                ), 0) as total_amalgamacion,
                
                -- FLOTACIÓN: Suma de resultados esperados por cada registro individual
                COALESCE((
                    SELECT SUM(
                        (f.carga_mineral_promedio * COALESCE(l_flotacion.ley_laboratorio, 0)) + 
                        (COALESCE(f.carga_mineral_extra, 0) * COALESCE(f.ley_inferido_metalurgista_extra, 0))
                    )
                    FROM flotacion f
                    LEFT JOIN laboratorio l_flotacion ON f.id = l_flotacion.registro_id AND l_flotacion.tipo_registro = 'flotacion'
                    WHERE f.fecha = ?
                ), 0) as total_flotacion";

    $resumen = $conexion->selectOne($sqlResumen, [$fecha, $fecha, $fecha, $fecha]);

    if (!$resumen) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "No se encontraron datos para la fecha especificada"]);
        exit;
    }

    // Calcular efectividades
    $efectividadMinaPlanta = calcularEfectividadDetalle($resumen["total_planta"], $resumen["total_mina"]);
    $efectividadMinaAmalgamacion = calcularEfectividadDetalle($resumen["total_amalgamacion"], $resumen["total_mina"]);
    $efectividadPlantaAmalgamacion = calcularEfectividadDetalle($resumen["total_amalgamacion"], $resumen["total_planta"]);

    // Agregar efectividades al resumen
    $resumen['efectividad_mina_planta'] = $efectividadMinaPlanta;
    $resumen['efectividad_mina_amalgamacion'] = $efectividadMinaAmalgamacion;
    $resumen['efectividad_planta_amalgamacion'] = $efectividadPlantaAmalgamacion;

    // Obtener detalles de MINA
    $sqlMina = "SELECT pm.id, pm.codigo_registro, pm.material_extraido, pm.ley_inferido_geologo,
                       tm.nombre as turno_nombre, fm.nombre as frente_nombre,
                       l.ley_laboratorio,
                       (pm.material_extraido * COALESCE(l.ley_laboratorio, pm.ley_inferido_geologo)) as produccion_estimada
                FROM produccion_mina pm
                INNER JOIN turnos_mina tm ON pm.turno_id = tm.id
                INNER JOIN frentes_mina fm ON pm.frente_id = fm.id
                LEFT JOIN laboratorio l ON pm.id = l.registro_id AND l.tipo_registro = 'produccion_mina'
                WHERE pm.fecha = ?
                ORDER BY pm.codigo_registro";

    $detallesMina = $conexion->select($sqlMina, [$fecha]);

    // Obtener detalles de PLANTA
    $sqlPlanta = "SELECT p.id, p.codigo_registro, p.material_procesado, p.ley_inferido_metalurgista,
                         tp.nombre as turno_nombre, lp.nombre as linea_nombre,
                         l.ley_laboratorio,
                         (p.material_procesado * COALESCE(l.ley_laboratorio, p.ley_inferido_metalurgista)) as produccion_estimada
                  FROM planta p
                  INNER JOIN turnos_planta tp ON p.turno_id = tp.id
                  INNER JOIN lineas_planta lp ON p.linea_id = lp.id
                  LEFT JOIN laboratorio l ON p.id = l.registro_id AND l.tipo_registro = 'planta'
                  WHERE p.fecha = ?
                  ORDER BY p.codigo_registro";

    $detallesPlanta = $conexion->select($sqlPlanta, [$fecha]);

    // Obtener detalles de AMALGAMACIÓN
    $sqlAmalgamacion = "SELECT a.id, a.codigo_registro, a.amalgamacion_gramos, a.factor_conversion_amalg_au,
                               ta.nombre as turno_nombre, la.nombre as linea_nombre,
                               (a.amalgamacion_gramos / a.factor_conversion_amalg_au) as resultado_au
                        FROM amalgamacion a
                        INNER JOIN turnos_amalgamacion ta ON a.turno_id = ta.id
                        INNER JOIN lineas_amalgamacion la ON a.linea_id = la.id
                        WHERE a.fecha = ?
                        ORDER BY a.codigo_registro";

    $detallesAmalgamacion = $conexion->select($sqlAmalgamacion, [$fecha]);

    // Obtener detalles de FLOTACIÓN
    $sqlFlotacion = "SELECT f.id, f.codigo_registro, f.carga_mineral_promedio, f.carga_mineral_extra,
                            f.ley_inferido_metalurgista_extra,
                            tf.nombre as turno_nombre,
                            l.ley_laboratorio,
                            ((f.carga_mineral_promedio * COALESCE(l.ley_laboratorio, 0)) + 
                             (COALESCE(f.carga_mineral_extra, 0) * COALESCE(f.ley_inferido_metalurgista_extra, 0))) as resultado_esperado
                     FROM flotacion f
                     INNER JOIN turnos_flotacion tf ON f.turno_id = tf.id
                     LEFT JOIN laboratorio l ON f.id = l.registro_id AND l.tipo_registro = 'flotacion'
                     WHERE f.fecha = ?
                     ORDER BY f.codigo_registro";

    $detallesFlotacion = $conexion->select($sqlFlotacion, [$fecha]);

    // Formatear fecha para mostrar
    $fechaFormateada = date("d/m/Y", strtotime($fecha));

    // Preparar respuesta
    $response = [
        "success" => true,
        "data" => [
            "fecha" => $fecha,
            "fecha_formateada" => $fechaFormateada,
            "resumen" => $resumen,
            "detalles" => [
                "mina" => $detallesMina,
                "planta" => $detallesPlanta,
                "amalgamacion" => $detallesAmalgamacion,
                "flotacion" => $detallesFlotacion
            ]
        ]
    ];
} catch (Exception $e) {
    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al obtener los detalles: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en obtener_detalle.php (administracion/reportes): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
