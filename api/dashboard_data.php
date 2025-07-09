<?php
// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Incluir archivos necesarios
require_once '../db/funciones.php';
require_once '../db/conexion.php';

// Configurar headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar permiso
if (!tienePermiso('dashboard.ver')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

try {
    $conexion = new Conexion();

    // Obtener datos del dashboard
    $dashboardData = [
        'metricas_principales' => obtenerMetricasPrincipales($conexion),
        'produccion_hoy' => obtenerProduccionHoy($conexion),
        'tendencia_semanal' => obtenerTendenciaSemanal($conexion),
        'distribucion_procesos' => obtenerDistribucionProcesos($conexion),
        'alertas' => obtenerAlertas($conexion),
        'actividad_reciente' => obtenerActividadReciente($conexion),
        'kpis_operacionales' => obtenerKPIsOperacionales($conexion)
    ];

    echo json_encode([
        'success' => true,
        'data' => $dashboardData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Error en dashboard_data.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

/**
 * Obtiene las métricas principales del dashboard
 */
function obtenerMetricasPrincipales($conexion)
{
    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('-1 day'));
    $hace7dias = date('Y-m-d', strtotime('-7 days'));
    $hace30dias = date('Y-m-d', strtotime('-30 days'));

    // Producción total hoy
    $produccionHoy = 0;
    $tablas = [
        'produccion_mina' => 'material_extraido',
        'planta' => 'material_procesado',
        'amalgamacion' => 'cantidad_carga_concentrados',
        'flotacion' => 'carga_mineral_promedio'
    ];

    foreach ($tablas as $tabla => $campo) {
        try {
            $result = $conexion->selectOne("
                SELECT COALESCE(SUM({$campo}), 0) as total 
                FROM {$tabla} 
                WHERE fecha = ?
            ", [$hoy]);
            $produccionHoy += $result ? $result['total'] : 0;
        } catch (Exception $e) {
            error_log("Error en tabla {$tabla}: " . $e->getMessage());
        }
    }

    // Producción ayer para comparación
    $produccionAyer = 0;
    foreach ($tablas as $tabla => $campo) {
        try {
            $result = $conexion->selectOne("
                SELECT COALESCE(SUM({$campo}), 0) as total 
                FROM {$tabla} 
                WHERE fecha = ?
            ", [$ayer]);
            $produccionAyer += $result ? $result['total'] : 0;
        } catch (Exception $e) {
            error_log("Error en tabla {$tabla}: " . $e->getMessage());
        }
    }

    // Registros totales hoy
    $registrosHoy = 0;
    foreach (array_keys($tablas) as $tabla) {
        try {
            $result = $conexion->selectOne("
                SELECT COUNT(*) as total 
                FROM {$tabla} 
                WHERE fecha = ?
            ", [$hoy]);
            $registrosHoy += $result ? $result['total'] : 0;
        } catch (Exception $e) {
            error_log("Error contando en tabla {$tabla}: " . $e->getMessage());
        }
    }

    // Ley promedio últimos 7 días
    $leyPromedio = $conexion->selectOne("
        SELECT AVG(ley_laboratorio) as promedio 
        FROM laboratorio 
        WHERE DATE(creado_en) BETWEEN ? AND ?
        AND ley_laboratorio IS NOT NULL
    ", [$hace7dias, $hoy]);

    // Registros incompletos (sin laboratorio)
    $registrosIncompletos = 0;
    foreach (array_keys($tablas) as $tabla) {
        try {
            $tipoRegistro = $tabla === 'produccion_mina' ? 'produccion_mina' : $tabla;
            $result = $conexion->selectOne("
                SELECT COUNT(*) as total 
                FROM {$tabla} t
                LEFT JOIN laboratorio l ON t.id = l.registro_id AND l.tipo_registro = ?
                WHERE t.fecha BETWEEN ? AND ? AND l.id IS NULL
            ", [$tipoRegistro, $hace7dias, $hoy]);
            $registrosIncompletos += $result ? $result['total'] : 0;
        } catch (Exception $e) {
            error_log("Error verificando registros incompletos en {$tabla}: " . $e->getMessage());
        }
    }

    // Turnos activos hoy
    $turnosActivos = $conexion->selectOne("
        SELECT COUNT(DISTINCT turno_id) as total FROM (
            SELECT turno_id FROM produccion_mina WHERE fecha = ?
            UNION
            SELECT turno_id FROM planta WHERE fecha = ?
            UNION  
            SELECT turno_id FROM amalgamacion WHERE fecha = ?
            UNION
            SELECT turno_id FROM flotacion WHERE fecha = ?
        ) as turnos
    ", [$hoy, $hoy, $hoy, $hoy]);

    // Calcular variación porcentual
    $variacionProduccion = $produccionAyer > 0 ?
        (($produccionHoy - $produccionAyer) / $produccionAyer) * 100 : 0;

    // Eficiencia operacional (simulada basada en metas)
    $eficienciaOperacional = min(95, max(60, 85 + ($variacionProduccion * 0.5)));

    return [
        'produccion_hoy' => (float)$produccionHoy,
        'produccion_ayer' => (float)$produccionAyer,
        'variacion_produccion' => (float)$variacionProduccion,
        'registros_hoy' => (int)$registrosHoy,
        'ley_promedio' => $leyPromedio ? (float)$leyPromedio['promedio'] : 0,
        'registros_incompletos' => (int)$registrosIncompletos,
        'turnos_activos' => $turnosActivos ? (int)$turnosActivos['total'] : 0,
        'eficiencia_operacional' => (float)$eficienciaOperacional
    ];
}

/**
 * Obtiene la producción detallada de hoy por proceso
 */
function obtenerProduccionHoy($conexion)
{
    $hoy = date('Y-m-d');
    $produccion = [];

    // Mina
    $mina = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(material_extraido), 0) as produccion,
            COUNT(*) as registros,
            AVG(ley_inferido_geologo) as ley_promedio
        FROM produccion_mina 
        WHERE fecha = ?
    ", [$hoy]);

    $produccion['mina'] = [
        'produccion' => $mina ? (float)$mina['produccion'] : 0,
        'registros' => $mina ? (int)$mina['registros'] : 0,
        'ley_promedio' => $mina ? (float)($mina['ley_promedio'] ?? 0) : 0,
        'meta_diaria' => 120.0,
        'estado' => 'activo'
    ];

    // Planta
    $planta = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(material_procesado), 0) as produccion,
            COUNT(*) as registros,
            AVG(ley_inferido_metalurgista) as ley_promedio
        FROM planta 
        WHERE fecha = ?
    ", [$hoy]);

    $produccion['planta'] = [
        'produccion' => $planta ? (float)$planta['produccion'] : 0,
        'registros' => $planta ? (int)$planta['registros'] : 0,
        'ley_promedio' => $planta ? (float)($planta['ley_promedio'] ?? 0) : 0,
        'meta_diaria' => 100.0,
        'estado' => 'activo'
    ];

    // Amalgamación
    $amalgamacion = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(cantidad_carga_concentrados), 0) as produccion,
            COUNT(*) as registros,
            COALESCE(SUM(amalgamacion_gramos), 0) as oro_producido
        FROM amalgamacion 
        WHERE fecha = ?
    ", [$hoy]);

    $produccion['amalgamacion'] = [
        'produccion' => $amalgamacion ? (float)$amalgamacion['produccion'] : 0,
        'registros' => $amalgamacion ? (int)$amalgamacion['registros'] : 0,
        'oro_producido' => $amalgamacion ? (float)$amalgamacion['oro_producido'] : 0,
        'meta_diaria' => 80.0,
        'estado' => 'activo'
    ];

    // Flotación
    $flotacion = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(carga_mineral_promedio), 0) as produccion,
            COUNT(*) as registros
        FROM flotacion 
        WHERE fecha = ?
    ", [$hoy]);

    $produccion['flotacion'] = [
        'produccion' => $flotacion ? (float)$flotacion['produccion'] : 0,
        'registros' => $flotacion ? (int)$flotacion['registros'] : 0,
        'meta_diaria' => 60.0,
        'estado' => 'activo'
    ];

    return $produccion;
}

/**
 * Obtiene la tendencia de producción de los últimos 7 días
 */
function obtenerTendenciaSemanal($conexion)
{
    $fechas = [];
    $produccionTotal = [];
    $registrosTotal = [];

    // Generar últimos 7 días
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-{$i} days"));
        $fechas[] = date('d/m', strtotime($fecha));

        // Calcular producción total del día
        $produccionDia = 0;
        $registrosDia = 0;

        $tablas = [
            'produccion_mina' => 'material_extraido',
            'planta' => 'material_procesado',
            'amalgamacion' => 'cantidad_carga_concentrados',
            'flotacion' => 'carga_mineral_promedio'
        ];

        foreach ($tablas as $tabla => $campo) {
            try {
                $result = $conexion->selectOne("
                    SELECT 
                        COALESCE(SUM({$campo}), 0) as produccion,
                        COUNT(*) as registros
                    FROM {$tabla} 
                    WHERE fecha = ?
                ", [$fecha]);

                if ($result) {
                    $produccionDia += $result['produccion'];
                    $registrosDia += $result['registros'];
                }
            } catch (Exception $e) {
                error_log("Error en tendencia semanal tabla {$tabla}: " . $e->getMessage());
            }
        }

        $produccionTotal[] = (float)$produccionDia;
        $registrosTotal[] = (int)$registrosDia;
    }

    return [
        'fechas' => $fechas,
        'produccion' => $produccionTotal,
        'registros' => $registrosTotal,
        'meta_diaria' => 360.0
    ];
}

/**
 * Obtiene la distribución de procesos del último mes
 */
function obtenerDistribucionProcesos($conexion)
{
    $hace30dias = date('Y-m-d', strtotime('-30 days'));
    $hoy = date('Y-m-d');

    $distribucion = [];

    // Mina
    $mina = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(material_extraido), 0) as total,
            COUNT(*) as registros
        FROM produccion_mina 
        WHERE fecha BETWEEN ? AND ?
    ", [$hace30dias, $hoy]);

    $distribucion[] = [
        'proceso' => 'Mina',
        'produccion' => $mina ? (float)$mina['total'] : 0,
        'registros' => $mina ? (int)$mina['registros'] : 0,
        'color' => '#8b4513'
    ];

    // Planta
    $planta = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(material_procesado), 0) as total,
            COUNT(*) as registros
        FROM planta 
        WHERE fecha BETWEEN ? AND ?
    ", [$hace30dias, $hoy]);

    $distribucion[] = [
        'proceso' => 'Planta',
        'produccion' => $planta ? (float)$planta['total'] : 0,
        'registros' => $planta ? (int)$planta['registros'] : 0,
        'color' => '#228b22'
    ];

    // Amalgamación
    $amalgamacion = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(cantidad_carga_concentrados), 0) as total,
            COUNT(*) as registros
        FROM amalgamacion 
        WHERE fecha BETWEEN ? AND ?
    ", [$hace30dias, $hoy]);

    $distribucion[] = [
        'proceso' => 'Amalgamación',
        'produccion' => $amalgamacion ? (float)$amalgamacion['total'] : 0,
        'registros' => $amalgamacion ? (int)$amalgamacion['registros'] : 0,
        'color' => '#ff8c00'
    ];

    // Flotación
    $flotacion = $conexion->selectOne("
        SELECT 
            COALESCE(SUM(carga_mineral_promedio), 0) as total,
            COUNT(*) as registros
        FROM flotacion 
        WHERE fecha BETWEEN ? AND ?
    ", [$hace30dias, $hoy]);

    $distribucion[] = [
        'proceso' => 'Flotación',
        'produccion' => $flotacion ? (float)$flotacion['total'] : 0,
        'registros' => $flotacion ? (int)$flotacion['registros'] : 0,
        'color' => '#4169e1'
    ];

    return $distribucion;
}

/**
 * Obtiene las alertas del sistema
 */
function obtenerAlertas($conexion)
{
    $alertas = [];
    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('-1 day'));

    // Verificar si hay producción hoy
    $produccionHoy = $conexion->selectOne("
        SELECT COUNT(*) as total FROM (
            SELECT id FROM produccion_mina WHERE fecha = ?
            UNION ALL
            SELECT id FROM planta WHERE fecha = ?
            UNION ALL
            SELECT id FROM amalgamacion WHERE fecha = ?
            UNION ALL
            SELECT id FROM flotacion WHERE fecha = ?
        ) as todos
    ", [$hoy, $hoy, $hoy, $hoy]);

    if (!$produccionHoy || $produccionHoy['total'] == 0) {
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Sin registros hoy',
            'mensaje' => 'No se han registrado operaciones para el día de hoy',
            'icono' => 'exclamation-triangle'
        ];
    }

    // Verificar registros incompletos
    $incompletos = $conexion->selectOne("
        SELECT COUNT(*) as total FROM (
            SELECT pm.id FROM produccion_mina pm
            LEFT JOIN laboratorio l ON pm.id = l.registro_id AND l.tipo_registro = 'produccion_mina'
            WHERE pm.fecha >= ? AND l.id IS NULL
            UNION ALL
            SELECT p.id FROM planta p
            LEFT JOIN laboratorio l ON p.id = l.registro_id AND l.tipo_registro = 'planta'
            WHERE p.fecha >= ? AND l.id IS NULL
        ) as incompletos
    ", [$ayer, $ayer]);

    if ($incompletos && $incompletos['total'] > 0) {
        $alertas[] = [
            'tipo' => 'danger',
            'titulo' => 'Registros incompletos',
            'mensaje' => "{$incompletos['total']} registros requieren datos de laboratorio",
            'icono' => 'flask'
        ];
    }

    // Verificar baja producción
    $produccionAyer = 0;
    $tablas = [
        'produccion_mina' => 'material_extraido',
        'planta' => 'material_procesado',
        'amalgamacion' => 'cantidad_carga_concentrados',
        'flotacion' => 'carga_mineral_promedio'
    ];

    foreach ($tablas as $tabla => $campo) {
        $result = $conexion->selectOne("
            SELECT COALESCE(SUM({$campo}), 0) as total 
            FROM {$tabla} 
            WHERE fecha = ?
        ", [$ayer]);
        $produccionAyer += $result ? $result['total'] : 0;
    }

    if ($produccionAyer < 300) { // Meta mínima diaria
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Producción por debajo de meta',
            'mensaje' => 'La producción de ayer fue de ' . number_format($produccionAyer, 1) . ' t (Meta: 360 t)',
            'icono' => 'trending-down'
        ];
    }

    // Si no hay alertas, mostrar estado normal
    if (empty($alertas)) {
        $alertas[] = [
            'tipo' => 'success',
            'titulo' => 'Operaciones normales',
            'mensaje' => 'Todos los sistemas funcionando correctamente',
            'icono' => 'check-circle'
        ];
    }

    return $alertas;
}

/**
 * Obtiene la actividad reciente
 */
function obtenerActividadReciente($conexion)
{
    $actividades = [];

    // Últimos registros de cada proceso
    $procesos = [
        'produccion_mina' => 'Mina',
        'planta' => 'Planta',
        'amalgamacion' => 'Amalgamación',
        'flotacion' => 'Flotación'
    ];

    foreach ($procesos as $tabla => $nombre) {
        try {
            $ultimo = $conexion->selectOne("
                SELECT fecha, creado_en, codigo_registro
                FROM {$tabla} 
                ORDER BY creado_en DESC 
                LIMIT 1
            ");

            if ($ultimo) {
                $actividades[] = [
                    'proceso' => $nombre,
                    'accion' => 'Nuevo registro',
                    'codigo' => $ultimo['codigo_registro'],
                    'fecha' => $ultimo['fecha'],
                    'timestamp' => $ultimo['creado_en'],
                    'tiempo_relativo' => calcularTiempoRelativo($ultimo['creado_en'])
                ];
            }
        } catch (Exception $e) {
            error_log("Error obteniendo actividad de {$tabla}: " . $e->getMessage());
        }
    }

    // Ordenar por timestamp más reciente
    usort($actividades, function ($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    return array_slice($actividades, 0, 5); // Últimas 5 actividades
}

/**
 * Obtiene los KPIs operacionales
 */
function obtenerKPIsOperacionales($conexion)
{
    $hace7dias = date('Y-m-d', strtotime('-7 days'));
    $hoy = date('Y-m-d');

    // Disponibilidad de equipos (simulada)
    $disponibilidad = 92.5;

    // Tiempo promedio de proceso (simulado)
    $tiempoPromedio = 6.8;

    // Calidad de ley (precisión laboratorio vs inferido)
    $precision = $conexion->selectOne("
        SELECT 
            AVG(ABS(l.ley_laboratorio - COALESCE(pm.ley_inferido_geologo, p.ley_inferido_metalurgista, 0)) / l.ley_laboratorio * 100) as diferencia
        FROM laboratorio l
        LEFT JOIN produccion_mina pm ON l.registro_id = pm.id AND l.tipo_registro = 'produccion_mina'
        LEFT JOIN planta p ON l.registro_id = p.id AND l.tipo_registro = 'planta'
        WHERE DATE(l.creado_en) BETWEEN ? AND ? 
        AND l.ley_laboratorio > 0
    ", [$hace7dias, $hoy]);

    $calidadLey = $precision && $precision['diferencia'] ?
        100 - (float)$precision['diferencia'] : 95;

    // Cumplimiento de metas
    $metaTotal = 360 * 7; // Meta semanal
    $produccionSemanal = 0;

    $tablas = [
        'produccion_mina' => 'material_extraido',
        'planta' => 'material_procesado',
        'amalgamacion' => 'cantidad_carga_concentrados',
        'flotacion' => 'carga_mineral_promedio'
    ];

    foreach ($tablas as $tabla => $campo) {
        $result = $conexion->selectOne("
            SELECT COALESCE(SUM({$campo}), 0) as total 
            FROM {$tabla} 
            WHERE fecha BETWEEN ? AND ?
        ", [$hace7dias, $hoy]);
        $produccionSemanal += $result ? $result['total'] : 0;
    }

    $cumplimientoMetas = $metaTotal > 0 ? ($produccionSemanal / $metaTotal) * 100 : 0;

    return [
        'disponibilidad_equipos' => (float)$disponibilidad,
        'tiempo_promedio_proceso' => (float)$tiempoPromedio,
        'calidad_ley' => (float)$calidadLey,
        'cumplimiento_metas' => (float)$cumplimientoMetas
    ];
}

/**
 * Calcula el tiempo relativo desde una fecha
 */
function calcularTiempoRelativo($timestamp)
{
    $tiempo = time() - strtotime($timestamp);

    if ($tiempo < 60) {
        return 'Hace ' . $tiempo . ' segundos';
    } elseif ($tiempo < 3600) {
        return 'Hace ' . floor($tiempo / 60) . ' minutos';
    } elseif ($tiempo < 86400) {
        return 'Hace ' . floor($tiempo / 3600) . ' horas';
    } else {
        return 'Hace ' . floor($tiempo / 86400) . ' días';
    }
}
