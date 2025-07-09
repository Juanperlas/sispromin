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
if (!tienePermiso('registros.historial_general.acceder')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Historial General de Registros";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/plugins/datepicker/css/bootstrap-datepicker.min.css',
    'assets/css/registros/historial/historial_general.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/colors.js',
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'assets/plugins/datepicker/js/bootstrap-datepicker.min.js',
    'assets/plugins/datepicker/js/locales/bootstrap-datepicker.es.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/registros/historial/historial_general.js'
];

// Incluir el header
$baseUrl = '../../../';
include_once '../../../includes/header.php';
include_once '../../../includes/navbar.php';
include_once '../../../includes/topbar.php';
?>

<div id="main-content" class="main-content">
    <!-- Cabecera compacta -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="page-title"><?php echo $titulo; ?></h1>
        <div class="page-stats">
            <span class="badge bg-info" id="total-registros">Cargando...</span>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros-header">
            <i class="bi bi-funnel me-2"></i>Filtros de Búsqueda
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
            <div class="filtro-grupo">
                <label for="filtro-tipo-registro" class="filtro-label">Tipo de Registro</label>
                <select id="filtro-tipo-registro" class="filtro-select">
                    <option value="">Todos los tipos</option>
                    <option value="mina">Mina</option>
                    <option value="planta">Planta</option>
                    <option value="amalgamacion">Amalgamación</option>
                    <option value="flotacion">Flotación</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-codigo" class="filtro-label">Código</label>
                <input type="text" id="filtro-codigo" class="filtro-select" placeholder="Buscar por código">
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-x"></i> Limpiar
                </button>
                <button id="btn-hoy" class="btn-hoy">
                    <i class="bi bi-calendar-day"></i> Hoy
                </button>
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="historial-layout">
        <!-- Tabla de registros -->
        <div class="historial-table-container">
            <div class="table-container">
                <table id="historial-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="registro-detalle" class="historial-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Registro</h2>
                <p class="detail-subtitle">Seleccione un registro para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un registro para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del registro -->
    <div class="modal fade" id="modal-ver-registro" tabindex="-1" aria-labelledby="modal-ver-registro-titulo" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-ver-registro-titulo">
                        <i class="bi bi-eye me-2"></i>Detalles del Registro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modal-ver-registro-body">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Cargando detalles...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-editar-desde-modal" class="btn btn-warning" style="display: none;">
                        <i class="bi bi-pencil me-2"></i>Editar
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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