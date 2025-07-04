<?php
require_once __DIR__ . '/conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está autenticado
 * @return bool
 */
function estaAutenticado()
{
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario tiene el rol de administrador o superadmin
 * @return bool
 */
function esAdmin()
{
    if (!estaAutenticado()) {
        return false;
    }
    $conexion = new Conexion();
    $roles = $conexion->getUserRoles($_SESSION['usuario_id']);
    return in_array('admin', $roles) || in_array('superadmin', $roles);
}

/**
 * Verifica si el usuario tiene el rol de superadmin
 * @return bool
 */
function esSuperAdmin()
{
    if (!estaAutenticado()) {
        return false;
    }
    $conexion = new Conexion();
    $roles = $conexion->getUserRoles($_SESSION['usuario_id']);
    return in_array('superadmin', $roles);
}

/**
 * Verifica si el usuario tiene un permiso específico
 * @param string $permiso
 * @return bool
 */
function tienePermiso($permiso)
{
    if (!estaAutenticado()) {
        return false;
    }
    $conexion = new Conexion();
    return $conexion->hasPermission($_SESSION['usuario_id'], $permiso);
}

/**
 * Redirige al usuario al login si no está autenticado
 */
function verificarAutenticacion()
{
    if (!estaAutenticado()) {
        // Guardar la URL actual para redirigir después del login
        $_SESSION['redirigir_despues_login'] = $_SERVER['REQUEST_URI'];
        header("Location: " . getPageUrl('login.php'));
        exit;
    }
}

/**
 * Verifica si el usuario tiene permisos de administrador, si no, redirige
 */
function verificarAdmin()
{
    verificarAutenticacion();
    if (!esAdmin()) {
        header("Location: " . getPageUrl('dashboard.php?error=no_autorizado'));
        exit;
    }
}

/**
 * Verifica si el usuario tiene permisos de superadmin, si no, redirige
 */
function verificarSuperAdmin()
{
    verificarAutenticacion();
    if (!esSuperAdmin()) {
        header("Location: " . getPageUrl('dashboard.php?error=no_autorizado'));
        exit;
    }
}

/**
 * Verifica si el usuario tiene un permiso específico, si no, redirige
 * @param string $permiso
 */
function verificarPermiso($permiso)
{
    verificarAutenticacion();
    if (!tienePermiso($permiso)) {
        header("Location: " . getPageUrl('dashboard.php?error=no_autorizado'));
        exit;
    }
}

/**
 * Establece un mensaje en la sesión
 * @param string $tipo Tipo de mensaje (success, error, warning, info)
 * @param string $texto Texto del mensaje
 */
function setMensaje($tipo, $texto)
{
    $_SESSION['mensaje'] = [
        'tipo' => $tipo,
        'texto' => $texto
    ];
}

/**
 * Obtiene el mensaje de la sesión y lo elimina
 * @return array|null
 */
function getMensaje()
{
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
        return $mensaje;
    }
    return null;
}

/**
 * Obtiene los datos del usuario actual
 * @return array|null
 */
function getUsuarioActual()
{
    if (!estaAutenticado()) {
        return null;
    }

    $conexion = new Conexion();
    $usuario = $conexion->selectOne(
        "SELECT u.id, u.username, u.nombre_completo AS nombre, u.correo, u.fotografia, u.area,
                GROUP_CONCAT(r.nombre) AS roles
         FROM usuarios u
         LEFT JOIN usuarios_roles ur ON u.id = ur.usuario_id
         LEFT JOIN roles r ON ur.rol_id = r.id
         WHERE u.id = ? AND u.esta_activo = 1
         GROUP BY u.id",
        [$_SESSION['usuario_id']]
    );

    if ($usuario) {
        $usuario['roles'] = $usuario['roles'] ? explode(',', $usuario['roles']) : [];
    }

    return $usuario;
}

/**
 * Función para sanitizar entradas
 * @param string $data
 * @return string
 */
function sanitizar($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Registra el inicio de sesión de un usuario
 * @param int $usuario_id
 * @return int ID de la sesión creada
 */
function registrarInicioSesion($usuario_id)
{
    $conexion = new Conexion();
    return $conexion->insert('sesiones_usuarios', [
        'usuario_id' => $usuario_id,
        'esta_activa' => 1
    ]);
}

/**
 * Obtiene el ID del usuario actualmente autenticado
 * @return int|null
 */
function getUsuarioId()
{
    if (!estaAutenticado()) {
        return null;
    }
    return $_SESSION['usuario_id'];
}

/**
 * Registra el cierre de sesión de un usuario
 * @param int $usuario_id
 * @return bool
 */
function registrarCierreSesion($usuario_id)
{
    $conexion = new Conexion();
    $conexion->update(
        'sesiones_usuarios',
        [
            'fin_sesion' => date('Y-m-d H:i:s'),
            'esta_activa' => 0
        ],
        'usuario_id = ? AND esta_activa = 1',
        [$usuario_id]
    );
    return true;
}

/**
 * Obtiene estadísticas para el dashboard de producción minera
 * @return array
 */
function obtenerEstadisticasDashboard()
{
    $conexion = new Conexion();

    // Estadísticas de producción del mes actual
    $mesActual = date('Y-m');

    $produccionMina = $conexion->selectOne(
        "SELECT COUNT(*) as registros, COALESCE(SUM(material_extraido), 0) as total_extraido 
         FROM produccion_mina 
         WHERE DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );

    $produccionPlanta = $conexion->selectOne(
        "SELECT COUNT(*) as registros, COALESCE(SUM(material_procesado), 0) as total_procesado 
         FROM planta 
         WHERE DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );

    $produccionAmalgamacion = $conexion->selectOne(
        "SELECT COUNT(*) as registros, COALESCE(SUM(amalgamacion_gramos), 0) as total_amalgamacion 
         FROM amalgamacion 
         WHERE DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );

    $produccionFlotacion = $conexion->selectOne(
        "SELECT COUNT(*) as registros, COALESCE(SUM(carga_mineral_promedio), 0) as total_flotacion 
         FROM flotacion 
         WHERE DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );

    return [
        'registros_mina' => $produccionMina['registros'] ?? 0,
        'total_extraido' => $produccionMina['total_extraido'] ?? 0,
        'registros_planta' => $produccionPlanta['registros'] ?? 0,
        'total_procesado' => $produccionPlanta['total_procesado'] ?? 0,
        'registros_amalgamacion' => $produccionAmalgamacion['registros'] ?? 0,
        'total_amalgamacion' => $produccionAmalgamacion['total_amalgamacion'] ?? 0,
        'registros_flotacion' => $produccionFlotacion['registros'] ?? 0,
        'total_flotacion' => $produccionFlotacion['total_flotacion'] ?? 0
    ];
}

/**
 * Obtiene la ruta base del proyecto
 * @return string
 */
function getProjectRoot()
{
    return '/sispromin/';
}

/**
 * Obtiene la URL para assets
 * @param string $path
 * @return string
 */
function getAssetUrl($path = '')
{
    return getProjectRoot() . ltrim($path, '/');
}

/**
 * Obtiene la URL para páginas
 * @param string $path
 * @return string
 */
function getPageUrl($path = '')
{
    return getProjectRoot() . ltrim($path, '/');
}

/**
 * Valida si un email es válido
 * @param string $email
 * @return bool
 */
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Genera una contraseña aleatoria
 * @param int $longitud
 * @return string
 */
function generarContrasena($longitud = 8)
{
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $contrasena = '';
    for ($i = 0; $i < $longitud; $i++) {
        $contrasena .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $contrasena;
}

/**
 * Formatea un número con separadores de miles
 * @param float $numero
 * @param int $decimales
 * @return string
 */
function formatearNumero($numero, $decimales = 2)
{
    return number_format($numero, $decimales, '.', ',');
}

/**
 * Convierte una fecha al formato español
 * @param string $fecha
 * @return string
 */
function formatearFecha($fecha)
{
    $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    $timestamp = strtotime($fecha);
    $dia = date('d', $timestamp);
    $mes = $meses[date('n', $timestamp)];
    $año = date('Y', $timestamp);

    return "$dia de $mes de $año";
}
