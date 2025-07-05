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
if (!tienePermiso('registros.planta.acceder')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Registro de Producción Planta";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/plugins/datepicker/css/bootstrap-datepicker.min.css',
    'assets/css/registros/planta/produccion_planta.css',
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
    'assets/js/registros/planta/produccion_planta.js'
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
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros-header">Filtros</div>
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
                <label for="filtro-turno" class="filtro-label">Turno</label>
                <select id="filtro-turno" class="filtro-select">
                    <option value="">Todos los turnos</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-linea" class="filtro-label">Línea</label>
                <select id="filtro-linea" class="filtro-select">
                    <option value="">Todas las líneas</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label for="filtro-concentrado" class="filtro-label">Concentrado</label>
                <select id="filtro-concentrado" class="filtro-select">
                    <option value="">Todos los concentrados</option>
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
            </div>
        </div>
    </div>

    <!-- Layout de dos columnas -->
    <div class="produccion-layout">
        <!-- Tabla de registros -->
        <div class="produccion-table-container">
            <div class="table-container">
                <table id="produccion-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Línea</th>
                            <th>Mat. Proc. (TM)</th>
                            <th>Concentrado</th>
                            <th>Cantidad (Und)</th>
                            <th>Peso (Kg)</th>
                            <th>Carga (Kg)</th>
                            <th>Ley M. (g/t)</th>   
                            <th>Ley L. (g/t)</th>
                            <th>Prod. Est.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="14" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel de detalles -->
        <div id="registro-detalle" class="produccion-detail-container">
            <div class="detail-header">
                <h2 class="detail-title">Detalles del Registro</h2>
                <p class="detail-subtitle">Seleccione un registro para ver información</p>
            </div>
            <div class="detail-content">
                <div class="detail-empty">
                    <div class="detail-empty-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                        Seleccione un registro para ver sus detalles
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar registro -->
    <div class="modal fade" id="modal-registro" tabindex="-1" aria-labelledby="modal-registro-titulo" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-registro-titulo">Nuevo Registro de Producción Planta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-registro">
                        <input type="hidden" id="registro-id">

                        <!-- Información básica del registro -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-calendar-event me-2"></i>Información del Registro
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-fecha" class="form-label form-label-sm">Fecha <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm datepicker" id="registro-fecha" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-turno" class="form-label form-label-sm">Turno <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="registro-turno" required>
                                                <option value="">Seleccionar turno</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-linea" class="form-label form-label-sm">Línea <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="registro-linea" required>
                                                <option value="">Seleccionar línea</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-concentrado" class="form-label form-label-sm">Concentrado <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="registro-concentrado" required>
                                                <option value="">Seleccionar concentrado</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos de producción -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-gear me-2"></i>Datos de Producción
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-material-procesado" class="form-label form-label-sm">Material Procesado (TM) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-sm" id="registro-material-procesado" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-produccion-cantidad" class="form-label form-label-sm">Producción Cantidad <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-sm" id="registro-produccion-cantidad" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-peso-aproximado" class="form-label form-label-sm">Peso Aproximado (Kg)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-peso-aproximado" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-ley-metalurgista" class="form-label form-label-sm">Ley Inferido Metalurgista (g/t)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-ley-metalurgista" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos de laboratorio (opcional) -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-flask me-2"></i>Datos de Laboratorio (Opcional)
                                <div class="form-check form-switch ms-auto">
                                    <input class="form-check-input" type="checkbox" id="habilitar-laboratorio">
                                    <label class="form-check-label text-white" for="habilitar-laboratorio">
                                        Habilitar
                                    </label>
                                </div>
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-codigo-muestra" class="form-label form-label-sm">Código de Muestra</label>
                                            <input type="text" class="form-control form-control-sm" id="registro-codigo-muestra" disabled maxlength="50">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-ley-laboratorio" class="form-label form-label-sm">Ley Laboratorio (g/t)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-ley-laboratorio" step="0.01" min="0" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-guardar-registro" class="btn btn-sm btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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

    <!-- Botón flotante para crear nuevo registro -->
    <?php if (tienePermiso('registros.planta.crear')): ?>
        <div class="floating-action-button">
            <button type="button" id="btn-nuevo-registro" class="btn btn-primary btn-fab" title="Nuevo Registro">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Componente de notificaciones toast -->
    <?php include_once '../../../componentes/toast/toast.php'; ?>
</div>

<?php
// Incluir el footer
include_once '../../../includes/footer.php';
?>
