<?php
require_once __DIR__ . '/conexion.php';

echo "=== SISPROMIN DATABASE SEEDER ACTUALIZADO ===\n";
echo "Iniciando el proceso de seeding con nueva estructura...\n\n";

try {
    $conexion = new Conexion();
    echo "✅ Conexión a la base de datos establecida.\n";
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// Función para generar contraseñas encriptadas
function encriptarContrasena($contrasena)
{
    return password_hash($contrasena, PASSWORD_BCRYPT);
}

// Desactivar restricciones de claves foráneas para limpieza
$conexion->query("SET FOREIGN_KEY_CHECKS = 0");

// Limpiar tablas de usuarios y preferencias
$tablas = ['usuarios_roles', 'preferencias_usuarios', 'sesiones_usuarios', 'usuarios', 'roles_permisos'];
foreach ($tablas as $tabla) {
    $conexion->query("TRUNCATE TABLE $tabla");
    echo "🧹 Tabla $tabla limpiada.\n";
}

// Reactivar restricciones de claves foráneas
$conexion->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n📝 Creando usuarios especializados...\n";

// Insertar superadmin con creado_por NULL
$superadmin_id = $conexion->insert('usuarios', [
    'username' => 'superadmin',
    'contrasena' => encriptarContrasena('admin123'),
    'nombre_completo' => 'Super Administrador',
    'correo' => 'superadmin@sispromin.com',
    'dni' => '12345678',
    'telefono' => '987654321',
    'direccion' => 'Av. Principal 123',
    'area' => 'Administración',
    'fotografia' => null,
    'creado_por' => null,
    'token_recordatorio' => null,
    'esta_activo' => 1
]);

// Insertar usuarios especializados
$usuarios = [
    // ADMINISTRADORES
    [
        'username' => 'admin',
        'contrasena' => encriptarContrasena('admin123'),
        'nombre_completo' => 'Administrador General',
        'correo' => 'admin@sispromin.com',
        'dni' => '87654321',
        'telefono' => '912345678',
        'direccion' => 'Calle Secundaria 456',
        'area' => 'Administración',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    // JEFES Y SUPERVISORES
    [
        'username' => 'jefe_operaciones',
        'contrasena' => encriptarContrasena('jefe123'),
        'nombre_completo' => 'Jefe de Operaciones',
        'correo' => 'jefe.operaciones@sispromin.com',
        'dni' => '45678912',
        'telefono' => '923456789',
        'direccion' => 'Av. Mina 789',
        'area' => 'Operaciones',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'supervisor_mina',
        'contrasena' => encriptarContrasena('mina123'),
        'nombre_completo' => 'Supervisor de Mina',
        'correo' => 'supervisor.mina@sispromin.com',
        'dni' => '11111111',
        'telefono' => '911111111',
        'direccion' => 'Zona Mina A',
        'area' => 'Mina',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'supervisor_planta',
        'contrasena' => encriptarContrasena('planta123'),
        'nombre_completo' => 'Supervisor de Planta',
        'correo' => 'supervisor.planta@sispromin.com',
        'dni' => '22222222',
        'telefono' => '922222222',
        'direccion' => 'Zona Planta B',
        'area' => 'Planta',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'supervisor_amalgamacion',
        'contrasena' => encriptarContrasena('amalgama123'),
        'nombre_completo' => 'Supervisor de Amalgamación',
        'correo' => 'supervisor.amalgamacion@sispromin.com',
        'dni' => '33333333',
        'telefono' => '933333333',
        'direccion' => 'Zona Amalgamación C',
        'area' => 'Amalgamación',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'supervisor_flotacion',
        'contrasena' => encriptarContrasena('flotacion123'),
        'nombre_completo' => 'Supervisor de Flotación',
        'correo' => 'supervisor.flotacion@sispromin.com',
        'dni' => '44444444',
        'telefono' => '944444444',
        'direccion' => 'Zona Flotación D',
        'area' => 'Flotación',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    // OPERADORES
    [
        'username' => 'operador_mina',
        'contrasena' => encriptarContrasena('opmina123'),
        'nombre_completo' => 'Operador de Mina',
        'correo' => 'operador.mina@sispromin.com',
        'dni' => '55555555',
        'telefono' => '955555555',
        'direccion' => 'Campamento Mina',
        'area' => 'Mina',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'operador_planta',
        'contrasena' => encriptarContrasena('opplanta123'),
        'nombre_completo' => 'Operador de Planta',
        'correo' => 'operador.planta@sispromin.com',
        'dni' => '66666666',
        'telefono' => '966666666',
        'direccion' => 'Campamento Planta',
        'area' => 'Planta',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'operador_amalgamacion',
        'contrasena' => encriptarContrasena('opamalgama123'),
        'nombre_completo' => 'Operador de Amalgamación',
        'correo' => 'operador.amalgamacion@sispromin.com',
        'dni' => '77777777',
        'telefono' => '977777777',
        'direccion' => 'Campamento Amalgamación',
        'area' => 'Amalgamación',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'operador_flotacion',
        'contrasena' => encriptarContrasena('opflotacion123'),
        'nombre_completo' => 'Operador de Flotación',
        'correo' => 'operador.flotacion@sispromin.com',
        'dni' => '88888888',
        'telefono' => '988888888',
        'direccion' => 'Campamento Flotación',
        'area' => 'Flotación',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    // INVITADO
    [
        'username' => 'invitado',
        'contrasena' => encriptarContrasena('invitado123'),
        'nombre_completo' => 'Usuario Invitado',
        'correo' => 'invitado@sispromin.com',
        'dni' => '99999999',
        'telefono' => '999999999',
        'direccion' => null,
        'area' => 'Externo',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ]
];

$usuario_ids = [$superadmin_id];
foreach ($usuarios as $usuario) {
    $usuario_id = $conexion->insert('usuarios', $usuario);
    $usuario_ids[] = $usuario_id;
    echo "👤 Usuario '{$usuario['username']}' creado con ID: $usuario_id\n";
}

echo "\n🔐 Asignando roles a usuarios...\n";

// Asignar roles a usuarios
$usuarios_roles = [
    [$usuario_ids[0], 1],  // superadmin -> superadmin
    [$usuario_ids[1], 2],  // admin -> admin
    [$usuario_ids[2], 3],  // jefe_operaciones -> jefe_operaciones
    [$usuario_ids[3], 4],  // supervisor_mina -> supervisor_mina
    [$usuario_ids[4], 5],  // supervisor_planta -> supervisor_planta
    [$usuario_ids[5], 6],  // supervisor_amalgamacion -> supervisor_amalgamacion
    [$usuario_ids[6], 7],  // supervisor_flotacion -> supervisor_flotacion
    [$usuario_ids[7], 8],  // operador_mina -> operador_mina
    [$usuario_ids[8], 9],  // operador_planta -> operador_planta
    [$usuario_ids[9], 10], // operador_amalgamacion -> operador_amalgamacion
    [$usuario_ids[10], 11], // operador_flotacion -> operador_flotacion
    [$usuario_ids[11], 12], // invitado -> invitado
];

$roles_nombres = [
    'superadmin',
    'admin',
    'jefe_operaciones',
    'supervisor_mina',
    'supervisor_planta',
    'supervisor_amalgamacion',
    'supervisor_flotacion',
    'operador_mina',
    'operador_planta',
    'operador_amalgamacion',
    'operador_flotacion',
    'invitado'
];

foreach ($usuarios_roles as $index => $ur) {
    $conexion->insert('usuarios_roles', [
        'usuario_id' => $ur[0],
        'rol_id' => $ur[1]
    ]);
    echo "🎭 Rol '{$roles_nombres[$index]}' asignado al usuario ID: {$ur[0]}\n";
}

echo "\n🔥 ASIGNANDO PERMISOS A ROLES...\n";

// Obtener todos los permisos existentes
$todosLosPermisos = $conexion->select("SELECT id FROM permisos ORDER BY id");
echo "📋 Total de permisos encontrados: " . count($todosLosPermisos) . "\n";

// SUPERADMIN: Todos los permisos
echo "🔥 Asignando TODOS los permisos al SUPERADMIN...\n";
$permisosAsignados = 0;
foreach ($todosLosPermisos as $permiso) {
    try {
        $conexion->insert('roles_permisos', [
            'rol_id' => 1, // superadmin
            'permiso_id' => $permiso['id']
        ]);
        $permisosAsignados++;
    } catch (Exception $e) {
        echo "⚠️ Error asignando permiso ID {$permiso['id']}: " . $e->getMessage() . "\n";
    }
}
echo "✅ Permisos asignados al superadmin: $permisosAsignados\n";

// ADMIN: Todos los permisos
echo "🔥 Asignando TODOS los permisos al ADMIN...\n";
$permisosAsignadosAdmin = 0;
foreach ($todosLosPermisos as $permiso) {
    try {
        $conexion->insert('roles_permisos', [
            'rol_id' => 2, // admin
            'permiso_id' => $permiso['id']
        ]);
        $permisosAsignadosAdmin++;
    } catch (Exception $e) {
        echo "⚠️ Error asignando permiso ID {$permiso['id']} al admin: " . $e->getMessage() . "\n";
    }
}
echo "✅ Permisos asignados al admin: $permisosAsignadosAdmin\n";

// JEFE OPERACIONES: Acceso a dashboard, registros y controles (no administración)
echo "🔥 Asignando permisos al JEFE DE OPERACIONES...\n";
$permisos_jefe = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.%' 
       OR nombre LIKE 'controles.%'
       OR nombre LIKE 'administracion.reportes.%'
");
foreach ($permisos_jefe as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 3, // jefe_operaciones
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al jefe de operaciones: " . count($permisos_jefe) . "\n";

// SUPERVISOR MINA: Dashboard + Registros/Controles de Mina
echo "🔥 Asignando permisos al SUPERVISOR DE MINA...\n";
$permisos_sup_mina = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.produccion_mina.%'
       OR nombre LIKE 'controles.mina.%'
");
foreach ($permisos_sup_mina as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 4, // supervisor_mina
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al supervisor de mina: " . count($permisos_sup_mina) . "\n";

// SUPERVISOR PLANTA: Dashboard + Registros/Controles de Planta
echo "🔥 Asignando permisos al SUPERVISOR DE PLANTA...\n";
$permisos_sup_planta = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.planta.%'
       OR nombre LIKE 'controles.planta.%'
");
foreach ($permisos_sup_planta as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 5, // supervisor_planta
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al supervisor de planta: " . count($permisos_sup_planta) . "\n";

// SUPERVISOR AMALGAMACIÓN: Dashboard + Registros/Controles de Amalgamación
echo "🔥 Asignando permisos al SUPERVISOR DE AMALGAMACIÓN...\n";
$permisos_sup_amalgamacion = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.amalgamacion.%'
       OR nombre LIKE 'controles.amalgamacion.%'
");
foreach ($permisos_sup_amalgamacion as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 6, // supervisor_amalgamacion
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al supervisor de amalgamación: " . count($permisos_sup_amalgamacion) . "\n";

// SUPERVISOR FLOTACIÓN: Dashboard + Registros/Controles de Flotación
echo "🔥 Asignando permisos al SUPERVISOR DE FLOTACIÓN...\n";
$permisos_sup_flotacion = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.flotacion.%'
       OR nombre LIKE 'controles.flotacion.%'
");
foreach ($permisos_sup_flotacion as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 7, // supervisor_flotacion
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al supervisor de flotación: " . count($permisos_sup_flotacion) . "\n";

// OPERADOR MINA: Dashboard + Solo registros de Mina (crear, ver, editar)
echo "🔥 Asignando permisos al OPERADOR DE MINA...\n";
$permisos_op_mina = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.produccion_mina.%'
");
foreach ($permisos_op_mina as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 8, // operador_mina
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al operador de mina: " . count($permisos_op_mina) . "\n";

// OPERADOR PLANTA: Dashboard + Solo registros de Planta
echo "🔥 Asignando permisos al OPERADOR DE PLANTA...\n";
$permisos_op_planta = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.planta.%'
");
foreach ($permisos_op_planta as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 9, // operador_planta
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al operador de planta: " . count($permisos_op_planta) . "\n";

// OPERADOR AMALGAMACIÓN: Dashboard + Solo registros de Amalgamación
echo "🔥 Asignando permisos al OPERADOR DE AMALGAMACIÓN...\n";
$permisos_op_amalgamacion = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.amalgamacion.%'
");
foreach ($permisos_op_amalgamacion as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 10, // operador_amalgamacion
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al operador de amalgamación: " . count($permisos_op_amalgamacion) . "\n";

// OPERADOR FLOTACIÓN: Dashboard + Solo registros de Flotación
echo "🔥 Asignando permisos al OPERADOR DE FLOTACIÓN...\n";
$permisos_op_flotacion = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE 'dashboard.%' 
       OR nombre LIKE 'registros.flotacion.%'
");
foreach ($permisos_op_flotacion as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 11, // operador_flotacion
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al operador de flotación: " . count($permisos_op_flotacion) . "\n";

// INVITADO: Solo visualización
echo "🔥 Asignando permisos al INVITADO...\n";
$permisos_invitado = $conexion->select("
    SELECT id FROM permisos 
    WHERE nombre LIKE '%.acceder' 
       OR nombre LIKE '%.ver'
       OR nombre LIKE 'dashboard.%'
");
foreach ($permisos_invitado as $permiso) {
    $conexion->insert('roles_permisos', [
        'rol_id' => 12, // invitado
        'permiso_id' => $permiso['id']
    ]);
}
echo "✅ Permisos asignados al invitado: " . count($permisos_invitado) . "\n";

echo "\n⚙️ Creando preferencias de usuarios...\n";

// Crear preferencias para cada usuario
$preferencias = [
    // Superadmin
    [
        'usuario_id' => $usuario_ids[0],
        'tema' => 'oscuro',
        'idioma' => 'es',
        'navbar_design' => 'modern',
        'navbar_bg_color' => '#2c3e50',
        'navbar_text_color' => '#ecf0f1',
        'navbar_active_bg_color' => '#3498db',
        'navbar_active_text_color' => '#ffffff',
        'topbar_bg_color' => '#2c3e50',
        'topbar_text_color' => '#ecf0f1',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 25
    ],
    // Admin
    [
        'usuario_id' => $usuario_ids[1],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'classic',
        'navbar_bg_color' => '#1571b0',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#1571b0',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 50
    ],
    // Jefe Operaciones
    [
        'usuario_id' => $usuario_ids[2],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'minimal',
        'navbar_bg_color' => '#e74c3c',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#f1c40f',
        'navbar_active_text_color' => '#2c3e50',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 25
    ],
    // Supervisor Mina
    [
        'usuario_id' => $usuario_ids[3],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#8B4513',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#8B4513',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 20
    ],
    // Supervisor Planta
    [
        'usuario_id' => $usuario_ids[4],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#2E8B57',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#2E8B57',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 20
    ],
    // Supervisor Amalgamación
    [
        'usuario_id' => $usuario_ids[5],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#FF6347',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#FF6347',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 20
    ],
    // Supervisor Flotación
    [
        'usuario_id' => $usuario_ids[6],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#4169E1',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#4169E1',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 20
    ],
    // Operador Mina
    [
        'usuario_id' => $usuario_ids[7],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#A0522D',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#A0522D',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 15
    ],
    // Operador Planta
    [
        'usuario_id' => $usuario_ids[8],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#228B22',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#228B22',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 15
    ],
    // Operador Amalgamación
    [
        'usuario_id' => $usuario_ids[9],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#DC143C',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#DC143C',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 15
    ],
    // Operador Flotación
    [
        'usuario_id' => $usuario_ids[10],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#1E90FF',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#1E90FF',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 15
    ],
    // Invitado
    [
        'usuario_id' => $usuario_ids[11],
        'tema' => 'claro',
        'idioma' => 'es',
        'navbar_design' => 'default',
        'navbar_bg_color' => '#6c757d',
        'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff',
        'navbar_active_text_color' => '#6c757d',
        'topbar_bg_color' => '#ffffff',
        'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard',
        'elementos_por_pagina' => 10
    ]
];

$usuarios_nombres = [
    'superadmin',
    'admin',
    'jefe_operaciones',
    'supervisor_mina',
    'supervisor_planta',
    'supervisor_amalgamacion',
    'supervisor_flotacion',
    'operador_mina',
    'operador_planta',
    'operador_amalgamacion',
    'operador_flotacion',
    'invitado'
];

foreach ($preferencias as $index => $pref) {
    $conexion->insert('preferencias_usuarios', $pref);
    echo "🎨 Preferencias creadas para usuario '{$usuarios_nombres[$index]}'\n";
}

echo "\n🏭 Creando datos iniciales de controles...\n";

// Insertar datos iniciales para turnos de mina
$turnos_mina_iniciales = [
    ['codigo' => 'TM001', 'nombre' => 'Turno Día'],
    ['codigo' => 'TM002', 'nombre' => 'Turno Noche'],
    ['codigo' => 'TM003', 'nombre' => 'Turno Madrugada']
];

foreach ($turnos_mina_iniciales as $turno) {
    $conexion->insert('turnos_mina', $turno);
    echo "⏰ Turno de mina '{$turno['nombre']}' creado\n";
}

// Insertar datos iniciales para frentes de mina
$frentes_mina_iniciales = [
    ['codigo' => 'FM001', 'nombre' => 'Frente Norte'],
    ['codigo' => 'FM002', 'nombre' => 'Frente Sur'],
    ['codigo' => 'FM003', 'nombre' => 'Frente Este'],
    ['codigo' => 'FM004', 'nombre' => 'Frente Oeste']
];

foreach ($frentes_mina_iniciales as $frente) {
    $conexion->insert('frentes_mina', $frente);
    echo "🏔️ Frente de mina '{$frente['nombre']}' creado\n";
}

// Insertar datos iniciales para turnos de planta
$turnos_planta_iniciales = [
    ['codigo' => 'TP001', 'nombre' => 'Turno Día Planta'],
    ['codigo' => 'TP002', 'nombre' => 'Turno Noche Planta']
];

foreach ($turnos_planta_iniciales as $turno) {
    $conexion->insert('turnos_planta', $turno);
    echo "⏰ Turno de planta '{$turno['nombre']}' creado\n";
}

// Insertar datos iniciales para líneas de planta
$lineas_planta_iniciales = [
    ['codigo' => 'LP001', 'nombre' => 'Línea A'],
    ['codigo' => 'LP002', 'nombre' => 'Línea B'],
    ['codigo' => 'LP003', 'nombre' => 'Línea C']
];

foreach ($lineas_planta_iniciales as $linea) {
    $conexion->insert('lineas_planta', $linea);
    echo "🏭 Línea de planta '{$linea['nombre']}' creada\n";
}

// Insertar datos iniciales para concentrados de planta
$concentrados_planta_iniciales = [
    ['codigo' => 'CP001', 'nombre' => 'Concentrado Oro'],
    ['codigo' => 'CP002', 'nombre' => 'Concentrado Plata'],
    ['codigo' => 'CP003', 'nombre' => 'Concentrado Mixto']
];

foreach ($concentrados_planta_iniciales as $concentrado) {
    $conexion->insert('concentrados_planta', $concentrado);
    echo "🥇 Concentrado de planta '{$concentrado['nombre']}' creado\n";
}

echo "\n🔍 VERIFICACIÓN FINAL...\n";

// Verificar permisos del superadmin
$permisosSuperadmin = $conexion->select(
    "SELECT COUNT(*) as total FROM roles_permisos WHERE rol_id = 1"
);
echo "🔐 Permisos del superadmin: " . $permisosSuperadmin[0]['total'] . "\n";

// Verificar usuarios creados
$usuariosCreados = $conexion->select("SELECT COUNT(*) as total FROM usuarios");
echo "👥 Usuarios creados: " . $usuariosCreados[0]['total'] . "\n";

// Verificar roles y permisos por rol
$rolesPermisos = $conexion->select("
    SELECT r.nombre as rol, COUNT(rp.permiso_id) as total_permisos
    FROM roles r
    LEFT JOIN roles_permisos rp ON r.id = rp.rol_id
    GROUP BY r.id, r.nombre
    ORDER BY r.id
");

echo "📊 Permisos por rol:\n";
foreach ($rolesPermisos as $rp) {
    echo "   • {$rp['rol']}: {$rp['total_permisos']} permisos\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 SEEDER ACTUALIZADO COMPLETADO EXITOSAMENTE\n";
echo str_repeat("=", 60) . "\n";
echo "📊 Resumen:\n";
echo "   • Usuarios creados: " . count($usuario_ids) . "\n";
echo "   • Roles especializados: " . count($roles_nombres) . "\n";
echo "   • Preferencias personalizadas: " . count($preferencias) . "\n";
echo "   • Permisos asignados al superadmin: $permisosAsignados\n";
echo "   • Permisos asignados al admin: $permisosAsignadosAdmin\n";
echo "   • Datos iniciales de controles creados\n";

echo "\n🔑 Credenciales de acceso:\n";
echo "   ADMINISTRADORES:\n";
echo "   • superadmin / admin123 (Acceso total)\n";
echo "   • admin / admin123 (Administrador completo)\n";
echo "\n   JEFES Y SUPERVISORES:\n";
echo "   • jefe_operaciones / jefe123 (Jefe de operaciones)\n";
echo "   • supervisor_mina / mina123 (Supervisor de mina)\n";
echo "   • supervisor_planta / planta123 (Supervisor de planta)\n";
echo "   • supervisor_amalgamacion / amalgama123 (Supervisor de amalgamación)\n";
echo "   • supervisor_flotacion / flotacion123 (Supervisor de flotación)\n";
echo "\n   OPERADORES:\n";
echo "   • operador_mina / opmina123 (Operador de mina)\n";
echo "   • operador_planta / opplanta123 (Operador de planta)\n";
echo "   • operador_amalgamacion / opamalgama123 (Operador de amalgamación)\n";
echo "   • operador_flotacion / opflotacion123 (Operador de flotación)\n";
echo "\n   OTROS:\n";
echo "   • invitado / invitado123 (Solo lectura)\n";

echo "\n✅ Base de datos actualizada y lista para usar!\n";
echo "🎯 Cada usuario tiene permisos específicos para su área de trabajo.\n";
