<?php
// Obtener la página actual para marcar el elemento activo
$pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
$request_uri = $_SERVER['REQUEST_URI'];
$request_parts = explode('/', trim($request_uri, '/'));

// Mejorar la detección de la ruta actual
$pagina_ruta = '';
if (count($request_parts) > 0) {
    // Eliminar el nombre del archivo y la extensión
    $last_part = end($request_parts);
    if (strpos($last_part, '.php') !== false) {
        array_pop($request_parts);
    }

    // Reconstruir la ruta
    if (count($request_parts) > 0) {
        $pagina_ruta = implode('/', $request_parts);
    }
}

// Verificar si estamos en un módulo específico - REGISTROS
$is_mina = strpos($pagina_ruta, 'modulos/registros/mina') !== false;
$is_planta = strpos($pagina_ruta, 'modulos/registros/planta') !== false;
$is_amalgamacion = strpos($pagina_ruta, 'modulos/registros/amalgamacion') !== false;
$is_flotacion = strpos($pagina_ruta, 'modulos/registros/flotacion') !== false;
$is_historial_general = strpos($pagina_ruta, 'modulos/registros/historial_general') !== false;
$is_estadistica = strpos($pagina_ruta, 'modulos/registros/estadistica') !== false;

// Verificar si estamos en un módulo específico - CONTROLES MINA
$is_turnos_mina = strpos($pagina_ruta, 'modulos/controles/mina/turnos') !== false;
$is_frentes_mina = strpos($pagina_ruta, 'modulos/controles/mina/frentes') !== false;

// Verificar si estamos en un módulo específico - CONTROLES PLANTA
$is_turnos_planta = strpos($pagina_ruta, 'modulos/controles/planta/turnos') !== false;
$is_lineas_planta = strpos($pagina_ruta, 'modulos/controles/planta/lineas') !== false;
$is_concentrados_planta = strpos($pagina_ruta, 'modulos/controles/planta/concentrados') !== false;

// Verificar si estamos en un módulo específico - CONTROLES AMALGAMACIÓN
$is_turnos_amalgamacion = strpos($pagina_ruta, 'modulos/controles/amalgamacion/turnos') !== false;
$is_lineas_amalgamacion = strpos($pagina_ruta, 'modulos/controles/amalgamacion/lineas') !== false;
$is_amalgamadores = strpos($pagina_ruta, 'modulos/controles/amalgamacion/amalgamadores') !== false;
$is_cargas_amalgamacion = strpos($pagina_ruta, 'modulos/controles/amalgamacion/cargas') !== false;

// Verificar si estamos en un módulo específico - CONTROLES FLOTACIÓN
$is_turnos_flotacion = strpos($pagina_ruta, 'modulos/controles/flotacion/turnos') !== false;
$is_productos_flotacion = strpos($pagina_ruta, 'modulos/controles/flotacion/productos') !== false;

// Verificar si estamos en un módulo específico - ADMINISTRACIÓN
$is_usuarios = strpos($pagina_ruta, 'modulos/administracion/usuarios') !== false;
$is_roles = strpos($pagina_ruta, 'modulos/administracion/rolespermisos') !== false;
$is_reportes = strpos($pagina_ruta, 'modulos/administracion/reportes') !== false;

// Obtener datos del usuario una sola vez
$usuario = getUsuarioActual();
$es_admin = esAdmin();

// Verificar permisos una sola vez para optimizar rendimiento
$permisos = [
    // REGISTROS
    'registros_produccion_mina' => tienePermiso('registros.produccion_mina.acceder'),
    'registros_planta' => tienePermiso('registros.planta.acceder'),
    'registros_amalgamacion' => tienePermiso('registros.amalgamacion.acceder'),
    'registros_flotacion' => tienePermiso('registros.flotacion.acceder'),
    'registros_historial_general' => tienePermiso('registros.historial_general.acceder'),
    'registros_estadistica' => tienePermiso('registros.estadistica.acceder'),

    // CONTROLES MINA
    'controles_turnos_mina' => tienePermiso('controles.mina.turnos.acceder'),
    'controles_frentes_mina' => tienePermiso('controles.mina.frentes.acceder'),

    // CONTROLES PLANTA
    'controles_turnos_planta' => tienePermiso('controles.planta.turnos.acceder'),
    'controles_lineas_planta' => tienePermiso('controles.planta.lineas.acceder'),
    'controles_concentrados_planta' => tienePermiso('controles.planta.concentrados.acceder'),

    // CONTROLES AMALGAMACIÓN
    'controles_turnos_amalgamacion' => tienePermiso('controles.amalgamacion.turnos.acceder'),
    'controles_lineas_amalgamacion' => tienePermiso('controles.amalgamacion.lineas.acceder'),
    'controles_amalgamadores' => tienePermiso('controles.amalgamacion.amalgamadores.acceder'),
    'controles_cargas_amalgamacion' => tienePermiso('controles.amalgamacion.cargas.acceder'),

    // CONTROLES FLOTACIÓN
    'controles_turnos_flotacion' => tienePermiso('controles.flotacion.turnos.acceder'),
    'controles_productos_flotacion' => tienePermiso('controles.flotacion.productos.acceder'),

    // ADMINISTRACIÓN
    'admin_usuarios' => tienePermiso('administracion.usuarios.acceder'),
    'admin_roles' => tienePermiso('administracion.rolespermisos.acceder'),
    'admin_reportes' => tienePermiso('administracion.reportes.acceder'),
];

// Verificar si hay al menos un permiso en cada sección para mostrar la sección completa
$mostrar_registros = $permisos['registros_produccion_mina'] || $permisos['registros_planta'] ||
    $permisos['registros_amalgamacion'] || $permisos['registros_flotacion'] ||
    $permisos['registros_historial_general'] || $permisos['registros_estadistica'];

$mostrar_controles_mina = $permisos['controles_turnos_mina'] || $permisos['controles_frentes_mina'];

$mostrar_controles_planta = $permisos['controles_turnos_planta'] || $permisos['controles_lineas_planta'] ||
    $permisos['controles_concentrados_planta'];

$mostrar_controles_amalgamacion = $permisos['controles_turnos_amalgamacion'] || $permisos['controles_lineas_amalgamacion'] ||
    $permisos['controles_amalgamadores'] || $permisos['controles_cargas_amalgamacion'];

$mostrar_controles_flotacion = $permisos['controles_turnos_flotacion'] || $permisos['controles_productos_flotacion'];

$mostrar_administracion = $es_admin && ($permisos['admin_usuarios'] || $permisos['admin_roles'] || $permisos['admin_reportes']);
?>

<!-- Sidebar Menu -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo getPageUrl('dashboard.php'); ?>" class="sidebar-logo">
            <img src="<?php echo getAssetUrl('assets/img/logo.png'); ?>" alt="SISPROMIN" class="logo logo-expanded" />
            <img src="<?php echo getAssetUrl('assets/img/logo-icon.png'); ?>" alt="SISPROMIN" class="logo logo-collapsed" />
        </a>
    </div>

    <div class="sidebar-content">
        <!-- GENERAL -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">General</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?php echo $pagina_actual == 'dashboard' ? 'active' : ''; ?>">
                    <a href="<?php echo getPageUrl('dashboard.php'); ?>" class="sidebar-menu-link">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- REGISTROS -->
        <?php if ($mostrar_registros): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Registros</div>
                <ul class="sidebar-menu">
                    <?php if ($permisos['registros_produccion_mina']): ?>
                        <li class="sidebar-menu-item <?php echo $is_mina ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/registros/mina/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-minecart"></i>
                                <span>Producción Mina</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['registros_planta']): ?>
                        <li class="sidebar-menu-item <?php echo $is_planta ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/registros/planta/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-building"></i>
                                <span>Planta</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['registros_amalgamacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_amalgamacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/registros/amalgamacion/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-droplet"></i>
                                <span>Amalgamación</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['registros_flotacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_flotacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/registros/flotacion/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-water"></i>
                                <span>Flotación</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['registros_historial_general']): ?>
                        <li class="sidebar-menu-item <?php echo $is_historial_general ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/registros/historial_general/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-clock-history"></i>
                                <span>Historial General</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['registros_estadistica']): ?>
                        <li class="sidebar-menu-item <?php echo $is_estadistica ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/registros/estadistica/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-bar-chart-line"></i>
                                <span>Estadísticas</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- CONTROLES MINA -->
        <?php if ($mostrar_controles_mina): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Controles Mina</div>
                <ul class="sidebar-menu">
                    <?php if ($permisos['controles_turnos_mina']): ?>
                        <li class="sidebar-menu-item <?php echo $is_turnos_mina ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/mina/turnos/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-clock"></i>
                                <span>Turnos</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_frentes_mina']): ?>
                        <li class="sidebar-menu-item <?php echo $is_frentes_mina ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/mina/frentes/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-geo-alt"></i>
                                <span>Frentes</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- CONTROLES PLANTA -->
        <?php if ($mostrar_controles_planta): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Controles Planta</div>
                <ul class="sidebar-menu">
                    <?php if ($permisos['controles_turnos_planta']): ?>
                        <li class="sidebar-menu-item <?php echo $is_turnos_planta ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/planta/turnos/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-clock"></i>
                                <span>Turnos</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_lineas_planta']): ?>
                        <li class="sidebar-menu-item <?php echo $is_lineas_planta ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/planta/lineas/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-diagram-3"></i>
                                <span>Líneas</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_concentrados_planta']): ?>
                        <li class="sidebar-menu-item <?php echo $is_concentrados_planta ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/planta/concentrados/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-layers"></i>
                                <span>Concentrados</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- CONTROLES AMALGAMACIÓN -->
        <?php if ($mostrar_controles_amalgamacion): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Controles Amalgamación</div>
                <ul class="sidebar-menu">
                    <?php if ($permisos['controles_turnos_amalgamacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_turnos_amalgamacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/amalgamacion/turnos/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-clock"></i>
                                <span>Turnos</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_lineas_amalgamacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_lineas_amalgamacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/amalgamacion/lineas/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-diagram-3"></i>
                                <span>Líneas</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_amalgamadores']): ?>
                        <li class="sidebar-menu-item <?php echo $is_amalgamadores ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/amalgamacion/amalgamadores/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-person-gear"></i>
                                <span>Amalgamadores</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_cargas_amalgamacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_cargas_amalgamacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/amalgamacion/cargas/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-box-seam"></i>
                                <span>Tipos de Carga</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- CONTROLES FLOTACIÓN -->
        <?php if ($mostrar_controles_flotacion): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Controles Flotación</div>
                <ul class="sidebar-menu">
                    <?php if ($permisos['controles_turnos_flotacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_turnos_flotacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/flotacion/turnos/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-clock"></i>
                                <span>Turnos</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['controles_productos_flotacion']): ?>
                        <li class="sidebar-menu-item <?php echo $is_productos_flotacion ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/controles/flotacion/productos/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-flask"></i>
                                <span>Productos Químicos</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ADMINISTRACIÓN (solo para administradores) -->
        <?php if ($mostrar_administracion): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Administración</div>
                <ul class="sidebar-menu">
                    <?php if ($permisos['admin_usuarios']): ?>
                        <li class="sidebar-menu-item <?php echo $is_usuarios ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/administracion/usuarios/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-people"></i>
                                <span>Usuarios</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['admin_roles']): ?>
                        <li class="sidebar-menu-item <?php echo $is_roles ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/administracion/rolespermisos/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-shield-lock"></i>
                                <span>Roles y Permisos</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($permisos['admin_reportes']): ?>
                        <li class="sidebar-menu-item <?php echo $is_reportes ? 'active' : ''; ?>">
                            <a href="<?php echo getPageUrl('modulos/administracion/reportes/index.php'); ?>" class="sidebar-menu-link">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Reportes</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>