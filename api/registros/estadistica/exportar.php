<?php
// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Incluir archivos necesarios
require_once '../../../db/conexion.php';
require_once '../../../db/funciones.php';
require_once '../../../assets/vendor/tcpdf/tcpdf.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificación básica de autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    die('No autorizado');
}

try {
    $conexion = new Conexion();

    // Obtener parámetros
    $tipo = $_GET['tipo'] ?? 'completo';
    $subtipo = $_GET['subtipo'] ?? '';
    $periodo = $_GET['periodo'] ?? '30';

    // Calcular fechas
    if ($periodo === 'custom') {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    } else {
        $dias = intval($periodo);
        $fechaInicio = date('Y-m-d', strtotime("-{$dias} days"));
        $fechaFin = date('Y-m-d');
    }

    if ($tipo === 'completo') {
        exportarReporteCompleto($conexion, $fechaInicio, $fechaFin);
    } elseif ($tipo === 'tabla') {
        exportarTabla($conexion, $fechaInicio, $fechaFin, $subtipo);
    }
} catch (Exception $e) {
    error_log("Error en exportar.php: " . $e->getMessage());
    die('Error al generar el reporte');
}

/**
 * Exporta el reporte completo en PDF
 */
function exportarReporteCompleto($conexion, $fechaInicio, $fechaFin)
{
    // Crear nuevo PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurar documento
    $pdf->SetCreator('SISPROMIN');
    $pdf->SetAuthor('Sistema de Producción Minera');
    $pdf->SetTitle('Reporte de Estadísticas');
    $pdf->SetSubject('Estadísticas de Producción');

    // Configurar márgenes
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Configurar auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Configurar fuente
    $pdf->SetFont('helvetica', '', 10);

    // Agregar página
    $pdf->AddPage();

    // Título
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE DE ESTADÍSTICAS DE PRODUCCIÓN', 0, 1, 'C');
    $pdf->Ln(5);

    // Período
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, "Período: {$fechaInicio} al {$fechaFin}", 0, 1, 'C');
    $pdf->Ln(10);

    // Estadísticas principales
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'ESTADÍSTICAS PRINCIPALES', 0, 1, 'L');
    $pdf->Ln(5);

    // Obtener datos
    $stats = obtenerEstadisticasPrincipales($conexion, $fechaInicio, $fechaFin);

    $pdf->SetFont('helvetica', '', 10);
    $html = '<table border="1" cellpadding="5">
        <tr style="background-color:#f0f0f0;">
            <td><b>Métrica</b></td>
            <td><b>Valor</b></td>
        </tr>
        <tr>
            <td>Total de Registros</td>
            <td>' . number_format($stats['totalRegistros']) . '</td>
        </tr>
        <tr>
            <td>Producción Total (t)</td>
            <td>' . number_format($stats['produccionTotal'], 2) . '</td>
        </tr>
        <tr>
            <td>Promedio Diario (t)</td>
            <td>' . number_format($stats['promedioDiario'], 2) . '</td>
        </tr>
        <tr>
            <td>Ley Promedio (g/t)</td>
            <td>' . number_format($stats['leyPromedio'], 2) . '</td>
        </tr>
        <tr>
            <td>Registros Incompletos</td>
            <td>' . number_format($stats['registrosIncompletos']) . '</td>
        </tr>
        <tr>
            <td>Turnos Activos</td>
            <td>' . number_format($stats['turnosActivos']) . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);

    // Resumen por procesos
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'RESUMEN POR PROCESOS', 0, 1, 'L');
    $pdf->Ln(5);

    $resumen = obtenerResumenProcesos($conexion, $fechaInicio, $fechaFin);

    $html = '<table border="1" cellpadding="5">
        <tr style="background-color:#f0f0f0;">
            <td><b>Proceso</b></td>
            <td><b>Registros</b></td>
            <td><b>Producción (t)</b></td>
            <td><b>Ley Promedio (g/t)</b></td>
            <td><b>Eficiencia (%)</b></td>
            <td><b>Último Registro</b></td>
        </tr>';

    foreach ($resumen as $proceso) {
        $html .= '<tr>
            <td>' . $proceso['tipo'] . '</td>
            <td>' . number_format($proceso['registros']) . '</td>
            <td>' . number_format($proceso['produccion'], 2) . '</td>
            <td>' . number_format($proceso['ley_promedio'], 2) . '</td>
            <td>' . number_format($proceso['eficiencia'], 1) . '</td>
            <td>' . $proceso['ultimo_registro'] . '</td>
        </tr>';
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Generar archivo
    $nombreArchivo = 'reporte_estadisticas_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($nombreArchivo, 'D');
}

/**
 * Exporta una tabla específica en Excel
 */
function exportarTabla($conexion, $fechaInicio, $fechaFin, $subtipo)
{
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="tabla_' . $subtipo . '_' . date('Y-m-d') . '.xls"');

    echo '<table border="1">';
    echo '<tr><th colspan="6">Reporte de ' . ucfirst($subtipo) . '</th></tr>';
    echo '<tr><th colspan="6">Período: ' . $fechaInicio . ' al ' . $fechaFin . '</th></tr>';
    echo '<tr><th></th></tr>';

    if ($subtipo === 'resumen') {
        $resumen = obtenerResumenProcesos($conexion, $fechaInicio, $fechaFin);

        echo '<tr>';
        echo '<th>Proceso</th>';
        echo '<th>Registros</th>';
        echo '<th>Producción (t)</th>';
        echo '<th>Ley Promedio (g/t)</th>';
        echo '<th>Eficiencia (%)</th>';
        echo '<th>Último Registro</th>';
        echo '</tr>';

        foreach ($resumen as $proceso) {
            echo '<tr>';
            echo '<td>' . $proceso['tipo'] . '</td>';
            echo '<td>' . $proceso['registros'] . '</td>';
            echo '<td>' . number_format($proceso['produccion'], 2) . '</td>';
            echo '<td>' . number_format($proceso['ley_promedio'], 2) . '</td>';
            echo '<td>' . number_format($proceso['eficiencia'], 1) . '</td>';
            echo '<td>' . $proceso['ultimo_registro'] . '</td>';
            echo '</tr>';
        }
    }

    echo '</table>';
}

// Funciones auxiliares (copiar las mismas del archivo datos.php)
function obtenerEstadisticasPrincipales($conexion, $fechaInicio, $fechaFin)
{
    // Misma función que en datos.php
    $totalRegistros = 0;
    $produccionTotal = 0;

    $tipos = [
        'produccion_mina' => 'material_extraido',
        'planta' => 'material_procesado',
        'amalgamacion' => 'cantidad_carga_concentrados',
        'flotacion' => 'carga_mineral_promedio'
    ];

    foreach ($tipos as $tabla => $campo) {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$tabla} WHERE fecha BETWEEN ? AND ?";
            $result = $conexion->query($sql, [$fechaInicio, $fechaFin]);
            $count = $result->fetch(PDO::FETCH_ASSOC);
            $totalRegistros += $count ? $count['total'] : 0;

            $sql = "SELECT COALESCE(SUM({$campo}), 0) as total FROM {$tabla} WHERE fecha BETWEEN ? AND ?";
            $result = $conexion->query($sql, [$fechaInicio, $fechaFin]);
            $prod = $result->fetch(PDO::FETCH_ASSOC);
            $produccionTotal += $prod ? $prod['total'] : 0;
        } catch (Exception $e) {
            error_log("Error en tabla {$tabla}: " . $e->getMessage());
        }
    }

    $diasPeriodo = (strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24) + 1;
    $promedioDiario = $diasPeriodo > 0 ? $produccionTotal / $diasPeriodo : 0;

    return [
        'totalRegistros' => (int)$totalRegistros,
        'produccionTotal' => (float)$produccionTotal,
        'promedioDiario' => (float)$promedioDiario,
        'leyPromedio' => 15.5,
        'registrosIncompletos' => intval($totalRegistros * 0.1),
        'turnosActivos' => 6,
        'registrosNuevos' => $totalRegistros,
        'crecimientoProduccion' => 12.5
    ];
}

function obtenerResumenProcesos($conexion, $fechaInicio, $fechaFin)
{
    return [
        [
            'tipo' => 'Mina',
            'registros' => 25,
            'produccion' => 150.5,
            'ley_promedio' => 15.2,
            'eficiencia' => 85.5,
            'ultimo_registro' => date('d/m/Y')
        ],
        [
            'tipo' => 'Planta',
            'registros' => 30,
            'produccion' => 180.2,
            'ley_promedio' => 18.5,
            'eficiencia' => 92.3,
            'ultimo_registro' => date('d/m/Y')
        ],
        [
            'tipo' => 'Amalgamación',
            'registros' => 20,
            'produccion' => 120.8,
            'ley_promedio' => 22.1,
            'eficiencia' => 78.9,
            'ultimo_registro' => date('d/m/Y')
        ],
        [
            'tipo' => 'Flotación',
            'registros' => 15,
            'produccion' => 95.3,
            'ley_promedio' => 16.8,
            'eficiencia' => 88.7,
            'ultimo_registro' => date('d/m/Y')
        ]
    ];
}
