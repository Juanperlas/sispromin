<?php
// Incluir archivos necesarios
require_once '../../../../db/funciones.php';
require_once '../../../../db/conexion.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('controles.amalgamacion.cargas.acceder')) {
    header("Location: ../../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Tipos de Carga de Amalgamación";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/controles/amalgamacion/cargas/cargas_amalgamacion.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/colors.js',
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/controles/amalgamacion/cargas/cargas_amalgamacion.js'
];

// Incluir el header
$baseUrl = '../../../../';
include_once '../../../../includes/header.php';
include_once '../../../../includes/navbar.php';
include_once '../../../../includes/topbar.php';
?>

<div id="main-content" class="main-content">
    <!-- Cabecera compacta -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="page-title"><?php echo $titulo; ?></h1>
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros-header">Filtros</div>
        <div class="filtros-content">
            <div class="filtro-grupo">
                <label for="filtro-codigo" class="filtro-label">Código</label>
                <input type="text" id="filtro-codigo" class="filtro-select" placeholder="Buscar por código">
            </div>
            <div class="filtro-grupo">
                <label for="filtro-nombre" class="filtro-label">Nombre</label>
                <input type="text" id="filtro-nombre" class="filtro-select" placeholder="Buscar por nombre">
            </div>
            <div class="filtros-actions">
                <button id="btn-aplicar-filtros" class="btn-aplicar">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <button id="btn-limpiar-filtros" class="btn-limpiar">
                    <i class="bi bi-x"></i> Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="cargas-layout">
        <!-- Tabla de tipos de carga -->
        <div class="cargas-table-container">
            <div class="table-container">
                <table id="cargas-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="carga-detalle" class="cargas-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Tipo de Carga</h2>
                <p class="detail-subtitle">Seleccione un tipo de carga para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un tipo de carga para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar tipo de carga -->
    <div class="modal fade" id="modal-carga" tabindex="-1" aria-labelledby="modal-carga-titulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-carga-titulo">Nuevo Tipo de Carga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-carga">
                        <input type="hidden" id="carga-id">

                        <!-- Información del tipo de carga -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-box me-2"></i>Información del Tipo de Carga
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="carga-codigo" class="form-label form-label-sm">Código <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="carga-codigo" required maxlength="50">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="carga-nombre" class="form-label form-label-sm">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="carga-nombre" required maxlength="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-carga" class="btn btn-sm btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del tipo de carga -->
    <div class="modal fade" id="modal-ver-carga" tabindex="-1" aria-labelledby="modal-ver-carga-titulo" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-ver-carga-titulo">
                        <i class="bi bi-eye me-2"></i>Detalles del Tipo de Carga
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modal-ver-carga-body">
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

    <!-- Botón flotante para crear nuevo tipo de carga -->
    <?php if (tienePermiso('controles.amalgamacion.cargas.crear')): ?>
        <div class="floating-action-button">
            <button type="button" id="btn-nueva-carga" class="btn btn-primary btn-fab" title="Nuevo Tipo de Carga">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Componente de notificaciones toast -->
    <?php include_once '../../../../componentes/toast/toast.php'; ?>
</div>

<?php
// Incluir el footer
include_once '../../../../includes/footer.php';
?>