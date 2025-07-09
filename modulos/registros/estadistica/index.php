<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('registros.estadistica.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Estadísticas de Producción - SISPROMIN";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/css/registros/estadistica/estadisticas.css',
    'assets/vendor/bootstrap-icons/bootstrap-icons.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/vendor/chartjs/chart.min.js',
    'assets/js/registros/estadistica/estadisticas.js'
];

// Incluir el header
$baseUrl = '../../../';
include_once '../../../includes/header.php';
include_once '../../../includes/navbar.php';
include_once '../../../includes/topbar.php';
?>

<div id="main-content" class="main-content estadisticas-container">
    <!-- Header del Dashboard de Estadísticas -->
    <div class="estadisticas-header">
        <div class="estadisticas-title-section">
            <h1 class="estadisticas-title">
                <i class="bi bi-graph-up"></i>
                Estadísticas de Producción Minera
            </h1>
            <p class="estadisticas-subtitle">Análisis integral de datos de producción - Mina, Planta, Amalgamación y Flotación</p>
        </div>
        <div class="estadisticas-actions">
            <button type="button" class="btn-estadisticas-action" id="btn-exportar-reporte">
                <i class="bi bi-file-earmark-pdf"></i>
                Exportar Reporte
            </button>
            <button type="button" class="btn-estadisticas-action" id="btn-actualizar-datos">
                <i class="bi bi-arrow-clockwise"></i>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Filtros de Período -->
    <div class="filtros-periodo">
        <div class="filtros-header">
            <i class="bi bi-calendar-range me-2"></i>Período de Análisis
        </div>
        <div class="filtros-content">
            <div class="filtro-grupo">
                <label for="periodo-select" class="filtro-label">Período</label>
                <select id="periodo-select" class="filtro-select">
                    <option value="7">Últimos 7 días</option>
                    <option value="30" selected>Últimos 30 días</option>
                    <option value="90">Últimos 3 meses</option>
                    <option value="365">Último año</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div class="filtro-grupo" id="fecha-custom" style="display: none;">
                <label for="fecha-inicio" class="filtro-label">Fecha Inicio</label>
                <input type="date" id="fecha-inicio" class="filtro-select">
            </div>
            <div class="filtro-grupo" id="fecha-custom-fin" style="display: none;">
                <label for="fecha-fin" class="filtro-label">Fecha Fin</label>
                <input type="date" id="fecha-fin" class="filtro-select">
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas Principales -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="bi bi-minecart"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="total-registros">0</div>
                <div class="stat-label">Total Registros</div>
                <div class="stat-change positive" id="registros-change">+0 este período</div>
            </div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="bi bi-gem"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="produccion-total">0</div>
                <div class="stat-label">Producción Total (t)</div>
                <div class="stat-change positive" id="produccion-change">0% vs período anterior</div>
            </div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="bi bi-speedometer2"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="promedio-diario">0</div>
                <div class="stat-label">Promedio Diario (t)</div>
                <div class="stat-change neutral" id="promedio-change">Producción diaria</div>
            </div>
        </div>

        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="bi bi-flask"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="ley-promedio">0</div>
                <div class="stat-label">Ley Promedio (g/t)</div>
                <div class="stat-change positive" id="ley-change">Laboratorio</div>
            </div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="registros-incompletos">0</div>
                <div class="stat-label">Registros Incompletos</div>
                <div class="stat-change negative" id="incompletos-change">Requieren atención</div>
            </div>
        </div>

        <div class="stat-card stat-secondary">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="turnos-activos">0</div>
                <div class="stat-label">Turnos Activos</div>
                <div class="stat-change neutral" id="turnos-change">En operación</div>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficas Principales -->
    <div class="estadisticas-grid">
        <!-- Gráfica 1: Producción por Tipo de Proceso -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-pie-chart-fill"></i>
                    Producción por Tipo de Proceso
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-produccion-tipo">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-produccion-tipo"></canvas>
            </div>
        </div>

        <!-- Gráfica 2: Tendencia de Producción Diaria -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-graph-up"></i>
                    Tendencia de Producción Diaria
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-tendencia">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-tendencia-produccion"></canvas>
            </div>
        </div>

        <!-- Gráfica 3: Distribución por Turnos -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clock-fill"></i>
                    Distribución por Turnos
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-turnos">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-distribucion-turnos"></canvas>
            </div>
        </div>

        <!-- Gráfica 4: Comparación Ley Laboratorio vs Inferido -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bar-chart-steps"></i>
                    Ley: Laboratorio vs Inferido
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-leyes">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-comparacion-leyes"></canvas>
            </div>
        </div>

        <!-- Gráfica 5: Eficiencia por Línea/Frente -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-speedometer"></i>
                    Eficiencia por Línea/Frente
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-eficiencia">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-eficiencia-lineas"></canvas>
            </div>
        </div>

        <!-- Gráfica 6: Consumo de Productos Químicos -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-droplet-half"></i>
                    Consumo de Productos Químicos
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-productos">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-productos-quimicos"></canvas>
            </div>
        </div>
    </div>

    <!-- Sección de Análisis Detallado -->
    <div class="analisis-detallado">
        <!-- Tabla de Resumen por Proceso -->
        <div class="estadisticas-card table-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-table"></i>
                    Resumen Detallado por Proceso
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" id="btn-export-resumen">
                        <i class="bi bi-file-earmark-excel"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="estadisticas-table" id="tabla-resumen-procesos">
                        <thead>
                            <tr>
                                <th>Proceso</th>
                                <th>Registros</th>
                                <th>Producción (t)</th>
                                <th>Ley Promedio (g/t)</th>
                                <th>Eficiencia (%)</th>
                                <th>Último Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Indicadores de Rendimiento -->
        <div class="estadisticas-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-speedometer2"></i>
                    Indicadores de Rendimiento (KPIs)
                </h3>
            </div>
            <div class="card-content">
                <div class="kpis-grid">
                    <div class="kpi-item">
                        <div class="kpi-icon">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="kpi-crecimiento">0%</div>
                            <div class="kpi-label">Crecimiento Mensual</div>
                        </div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-icon">
                            <i class="bi bi-target"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="kpi-precision">0%</div>
                            <div class="kpi-label">Precisión Ley Lab.</div>
                        </div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="kpi-tiempo-promedio">0h</div>
                            <div class="kpi-label">Tiempo Prom. Proceso</div>
                        </div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="kpi-completitud">0%</div>
                            <div class="kpi-label">Completitud Datos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer de Estadísticas -->
    <div class="estadisticas-footer">
        <div class="footer-info">
            <p><strong>SISPROMIN</strong> - Sistema de Producción Minera</p>
            <p>Estadísticas generadas el: <span id="fecha-generacion">--</span> | Última actualización: <span id="ultima-actualizacion">--</span></p>
        </div>
        <div class="footer-actions">
            <button class="btn-footer-action" id="btn-imprimir-reporte">
                <i class="bi bi-printer"></i>
                Imprimir
            </button>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../../includes/footer.php';
?>