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
if (!tienePermiso('registros.amalgamacion.acceder')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Título de la página
$titulo = "Registro de Amalgamación";

// Definir CSS y JS adicionales para este módulo
$css_adicional = [
    'assets/css/colors.css',
    'assets/plugins/datatables/css/datatables.min.css',
    'assets/plugins/datepicker/css/bootstrap-datepicker.min.css',
    'assets/css/registros/amalgamacion/produccion_amalgamacion.css',
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
    'assets/js/registros/amalgamacion/produccion_amalgamacion.js'
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
                <label for="filtro-amalgamador" class="filtro-label">Amalgamador</label>
                <select id="filtro-amalgamador" class="filtro-select">
                    <option value="">Todos los amalgamadores</option>
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
                <table id="amalgamacion-table" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Línea</th>
                            <th>Amalgamador</th>
                            <th>Cant. Carga (Kg)</th>
                            <th>Resultado Refogado (g)</th>
                            <th>% Recuperación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="text-center">Cargando datos...</td>
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
                    <h5 class="modal-title" id="modal-registro-titulo">Nuevo Registro de Amalgamación</h5>
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
                                            <label for="registro-amalgamador" class="form-label form-label-sm">Amalgamador <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="registro-amalgamador" required>
                                                <option value="">Seleccionar amalgamador</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos de carga -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-box-seam me-2"></i>Datos de Carga
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-cantidad-carga" class="form-label form-label-sm">Cantidad Carga Concentrados (Kg) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-sm" id="registro-cantidad-carga" step="0.1" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-tipo-carga" class="form-label form-label-sm">Tipo de Carga <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="registro-tipo-carga" required>
                                                <option value="">Seleccionar tipo de carga</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-mercurio" class="form-label form-label-sm">Carga Mercurio (Kg) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-sm" id="registro-mercurio" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-amalgamacion" class="form-label form-label-sm">Amalgamación (g) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-sm" id="registro-amalgamacion" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Productos químicos -->
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-flask me-2"></i>Productos Químicos (Opcional)
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-soda-caustica" class="form-label form-label-sm">Soda Cáustica (Kg)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-soda-caustica" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-detergente" class="form-label form-label-sm">Detergente (Kg)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-detergente" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-cal" class="form-label form-label-sm">Cal (Kg)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-cal" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-lejia" class="form-label form-label-sm">Lejía (Litros)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-lejia" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-mercurio-recuperado" class="form-label form-label-sm">Mercurio Recuperado (Kg)</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-mercurio-recuperado" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="registro-factor-conversion" class="form-label form-label-sm">Factor Conversión Au</label>
                                            <input type="number" class="form-control form-control-sm" id="registro-factor-conversion" step="0.001" min="0" value="3.3">
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
    <?php if (tienePermiso('registros.amalgamacion.crear')): ?>
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
