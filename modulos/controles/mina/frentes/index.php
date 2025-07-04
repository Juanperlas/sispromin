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
if (!tienePermiso('controles.mina.frentes.acceder')) {
    header("Location: ../../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Frentes de Mina";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/css/controles/mina/frentes/frentes_mina.css',
    'componentes/toast/toast.css'
];

$js_adicional = [
    'assets/js/colors.js',
    'assets/js/jquery-3.7.1.min.js',
    'assets/js/jquery.validate.min.js',
    'assets/plugins/datatables/js/datatables.min.js',
    'componentes/ajax/ajax-utils.js',
    'componentes/toast/toast.js',
    'assets/js/controles/mina/frentes/frentes_mina.js'
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
    <div class="frentes-layout">
        <!-- Tabla de frentes -->
        <div class="frentes-table-container">
            <div class="table-container">
                <table id="frentes-table" class="table table-sm table-hover">
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
        <div id="frente-detalle" class="frentes-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Frente</h2>
                <p class="detail-subtitle">Seleccione un frente para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un frente para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar frente -->
    <div class="modal fade" id="modal-frente" tabindex="-1" aria-labelledby="modal-frente-titulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-frente-titulo">Nuevo Frente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-frente">
                        <input type="hidden" id="frente-id">

                        <!-- Información del frente -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-geo-alt me-2"></i>Información del Frente
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="frente-codigo" class="form-label form-label-sm">Código <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="frente-codigo" required maxlength="50">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="frente-nombre" class="form-label form-label-sm">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" id="frente-nombre" required maxlength="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-frente" class="btn btn-sm btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón flotante para crear nuevo frente -->
    <?php if (tienePermiso('controles.mina.frentes.crear')): ?>
        <div class="floating-action-button">
            <button type="button" id="btn-nuevo-frente" class="btn btn-primary btn-fab" title="Nuevo Frente">
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