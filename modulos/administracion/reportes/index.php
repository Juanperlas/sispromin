<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('administracion.reportes.acceder')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Auditoría General Diaria";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/plugins/datepicker/css/bootstrap-datepicker.min.css',
    'assets/css/administracion/reportes/reportes.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/colors.js',
    'assets/js/jquery-3.7.1.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'assets/plugins/datepicker/js/bootstrap-datepicker.min.js',
    'assets/plugins/datepicker/js/locales/bootstrap-datepicker.es.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/administracion/reportes/reportes.js'
];

// Incluir el header
$baseUrl = '../../../';
include_once '../../../includes/header.php';
include_once '../../../includes/navbar.php';
include_once '../../../includes/topbar.php';
?>

<div id="main-content" class="main-content">
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title"><?php echo $titulo; ?></h1>
        <div class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Por defecto se muestra solo el día actual
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros-header">
            <i class="bi bi-funnel me-2"></i>Filtros de Fecha
        </div>
        <div class="filtros-content">
            <div class="filtro-grupo">
                <label for="filtro-fecha-inicio" class="filtro-label">Fecha Inicio</label>
                <input type="text" id="filtro-fecha-inicio" class="filtro-select datepicker" placeholder="dd/mm/yyyy">
            </div>
            <div class="filtro-grupo">
                <label for="filtro-fecha-fin" class="filtro-label">Fecha Fin</label>
                <input type="text" id="filtro-fecha-fin" class="filtro-select datepicker" placeholder="dd/mm/yyyy">
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-arrow-clockwise"></i> Solo Hoy
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de reportes -->
    <div class="table-container">
        <table id="reportes-table" class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Total Mina (g)</th>
                    <th>Total Planta (g)</th>
                    <th>Total Amalgamación (g)</th>
                    <th>Total Flotación (g)</th>
                    <th>Efectividad<br>Mina → Planta</th>
                    <th>Efectividad<br>Mina → Amalgamación</th>
                    <th>Efectividad<br>Planta → Amalgamación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="9" class="text-center">Cargando datos...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal para ver detalles completos de auditoría -->
    <div class="modal fade" id="modal-detalle-auditoria" tabindex="-1" aria-labelledby="modal-detalle-titulo" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detalle-titulo">
                        <i class="bi bi-calendar-check me-2"></i>Auditoría General Diaria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modal-detalle-body">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando auditoría completa...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Componente de notificaciones toast -->
    <?php include_once '../../../componentes/toast/toast.php'; ?>
</div>

<?php
// Incluir el footer
include_once '../../../includes/footer.php';
?>