<?php
// Incluir archivos necesarios
require_once 'db/funciones.php';
require_once 'db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('dashboard.ver')) {
    header("Location: login.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Dashboard - SISPROMIN";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/css/dashboard.css',
    'assets/vendor/bootstrap-icons/bootstrap-icons.css'
];

$js_adicional = [
    'assets/js/jquery-3.7.1.min.js',
    'assets/vendor/chartjs/chart.min.js',
    'assets/js/dashboard.js'
];

// Incluir el header
$baseUrl = '';
include_once 'includes/header.php';
include_once 'includes/navbar.php';
include_once 'includes/topbar.php';
?>

<div id="main-content" class="main-content dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <div class="dashboard-title-section">
            <h1 class="dashboard-title">
                <i class="bi bi-speedometer2"></i>
                Panel de Control SISPROMIN
            </h1>
            <p class="dashboard-subtitle">Monitoreo en tiempo real - Sistema de Producción Minera</p>
        </div>
        <div class="dashboard-actions">
            <div class="dashboard-clock">
                <div class="clock-time" id="reloj-tiempo">--:--:--</div>
                <div class="clock-date" id="reloj-fecha">Cargando...</div>
            </div>
            <button type="button" class="btn-dashboard-action" id="btn-actualizar-dashboard">
                <i class="bi bi-arrow-clockwise"></i>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Métricas Principales (Solo 4) -->
    <div class="metrics-grid">
        <div class="metric-card metric-primary">
            <div class="metric-icon">
                <i class="bi bi-gem"></i>
            </div>
            <div class="metric-content">
                <div class="metric-number" id="produccion-hoy">0</div>
                <div class="metric-label">Producción Hoy (t)</div>
                <div class="metric-change" id="variacion-produccion">+0%</div>
            </div>
        </div>

        <div class="metric-card metric-success">
            <div class="metric-icon">
                <i class="bi bi-clipboard-data"></i>
            </div>
            <div class="metric-content">
                <div class="metric-number" id="registros-hoy">0</div>
                <div class="metric-label">Registros Hoy</div>
                <div class="metric-change neutral">Operaciones</div>
            </div>
        </div>

        <div class="metric-card metric-warning">
            <div class="metric-icon">
                <i class="bi bi-graph-up"></i> <!-- Ícono de promedio -->
            </div>
            <div class="metric-content">
                <div class="metric-number" id="ley-promedio">0</div>
                <div class="metric-label">Ley Promedio (g/t)</div>
                <div class="metric-change neutral">Laboratorio</div>
            </div>
        </div>

        <div class="metric-card metric-info">
            <div class="metric-icon">
                <i class="bi bi-speedometer2"></i>
            </div>
            <div class="metric-content">
                <div class="metric-number" id="eficiencia-operacional">0</div>
                <div class="metric-label">Eficiencia (%)</div>
                <div class="metric-progress">
                    <div class="progress">
                        <div class="progress-bar" id="eficiencia-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección Principal de Gráficas -->
    <div class="dashboard-grid">
        <!-- Gráfica Principal: Tendencia Semanal -->
        <div class="dashboard-card chart-main">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-graph-up"></i>
                    Tendencia de Producción (7 días)
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" title="Exportar">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-tendencia-semanal"></canvas>
            </div>
        </div>

        <!-- Gráfica Secundaria: Distribución de Procesos -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-pie-chart"></i>
                    Distribución por Proceso
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" title="Exportar">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-distribucion-procesos"></canvas>
            </div>
        </div>

        <!-- Producción Detallada de Hoy -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bar-chart"></i>
                    Producción vs Meta (Hoy)
                </h3>
                <div class="card-actions">
                    <button class="btn-card-action" title="Exportar">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <canvas id="chart-produccion-hoy"></canvas>
            </div>
        </div>

    </div>

    <!-- Sección Inferior -->
    <div class="bottom-section">
        <!-- Gráfica de Comparación -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-list-check"></i>
                    Producción Detallada - Hoy
                </h3>
            </div>
            <div class="card-content">
                <div id="produccion-hoy-detalle" class="procesos-container">
                    <div class="text-center p-3">
                        <div class="spinner"></div>
                        <small>Cargando datos...</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas del Sistema -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bell"></i>
                    Alertas del Sistema
                </h3>
            </div>
            <div class="card-content">
                <div id="alertas-container" class="alertas-list">
                    <div class="text-center p-3">
                        <div class="spinner"></div>
                        <small>Verificando alertas...</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clock-history"></i>
                    Actividad Reciente
                </h3>
            </div>
            <div class="card-content">
                <div id="actividad-reciente" class="actividad-list">
                    <div class="text-center p-3">
                        <div class="spinner"></div>
                        <small>Cargando actividad...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación Rápida -->
    <div class="quick-nav">
        <div class="quick-nav-header">
            <h3><i class="bi bi-lightning"></i> Acceso Rápido</h3>
        </div>
        <div class="quick-nav-grid">
            <button class="quick-nav-btn" data-modulo="modulos/registros/mina/index.php">
                <i class="bi bi-minecart"></i>
                <span>Registro Mina</span>
            </button>
            <button class="quick-nav-btn" data-modulo="modulos/registros/planta/index.php">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Registro Planta</span>
            </button>
            <button class="quick-nav-btn" data-modulo="modulos/registros/amalgamacion/index.php">
                <i class="bi bi-droplet-half"></i>
                <span>Amalgamación</span>
            </button>
            <button class="quick-nav-btn" data-modulo="modulos/registros/flotacion/index.php">
                <i class="bi bi-water"></i>
                <span>Flotación</span>
            </button>
            <button class="quick-nav-btn" data-modulo="modulos/registros/historial/index.php">
                <i class="bi bi-clock-history"></i>
                <span>Historial</span>
            </button>
            <button class="quick-nav-btn" data-modulo="modulos/registros/estadistica/index.php">
                <i class="bi bi-graph-up"></i>
                <span>Estadísticas</span>
            </button>
        </div>
    </div>

    <!-- Footer del Dashboard -->
    <div class="dashboard-footer">
        <div class="footer-info">
            <p><strong>SISPROMIN</strong> - Sistema de Producción Minera</p>
            <p id="next-refresh">Próxima actualización en 5:00</p>
        </div>
        <div class="footer-status">
            <span class="status-indicator online"></span>
            <span>Sistema en línea</span>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once 'includes/footer.php';
?>