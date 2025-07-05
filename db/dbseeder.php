<?php
require_once __DIR__ . '/conexion.php';

echo "=== SISPROMIN DATABASE SEEDER OPTIMIZADO ===\n";
echo "Iniciando el proceso de seeding con usuarios y turnos optimizados...\n\n";

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
$tablas_a_limpiar = ['usuarios_roles', 'preferencias_usuarios', 'sesiones_usuarios', 'usuarios', 'roles_permisos'];
foreach ($tablas_a_limpiar as $tabla) {
    $conexion->query("TRUNCATE TABLE $tabla");
    echo "🧹 Tabla $tabla limpiada.\n";
}

// Limpiar tablas de controles (si quieres reiniciar los turnos y frentes cada vez)
// Nota: Si no quieres que estos datos se trunquen cada vez que corres el seeder,
// puedes comentar estas líneas.
$tablas_controles = [
    'turnos_mina', 'frentes_mina',
    'turnos_planta', 'lineas_planta', 'concentrados_planta',
    'turnos_amalgamacion', 'lineas_amalgamacion', 'amalgamadores', 'cargas_amalgamacion',
    'turnos_flotacion', 'productos_flotacion'
];
foreach ($tablas_controles as $tabla) {
    $conexion->query("TRUNCATE TABLE $tabla");
    echo "🧹 Tabla $tabla limpiada.\n";
}


// Reactivar restricciones de claves foráneas
$conexion->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n📝 Creando usuarios principales...\n";

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

// Insertar usuarios especializados (uno por cada rol principal)
$usuarios = [
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
    [
        'username' => 'usuario_mina',
        'contrasena' => encriptarContrasena('mina123'),
        'nombre_completo' => 'Usuario Mina',
        'correo' => 'usuario.mina@sispromin.com',
        'dni' => '11111111',
        'telefono' => '911111111',
        'direccion' => 'Zona Mina',
        'area' => 'Mina',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'usuario_planta',
        'contrasena' => encriptarContrasena('planta123'),
        'nombre_completo' => 'Usuario Planta',
        'correo' => 'usuario.planta@sispromin.com',
        'dni' => '22222222',
        'telefono' => '922222222',
        'direccion' => 'Zona Planta',
        'area' => 'Planta',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'usuario_amalgamacion',
        'contrasena' => encriptarContrasena('amalgama123'),
        'nombre_completo' => 'Usuario Amalgamación',
        'correo' => 'usuario.amalgamacion@sispromin.com',
        'dni' => '33333333',
        'telefono' => '933333333',
        'direccion' => 'Zona Amalgamación',
        'area' => 'Amalgamación',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ],
    [
        'username' => 'usuario_flotacion',
        'contrasena' => encriptarContrasena('flotacion123'),
        'nombre_completo' => 'Usuario Flotación',
        'correo' => 'usuario.flotacion@sispromin.com',
        'dni' => '44444444',
        'telefono' => '944444444',
        'direccion' => 'Zona Flotación',
        'area' => 'Flotación',
        'fotografia' => null,
        'creado_por' => $superadmin_id,
        'token_recordatorio' => null,
        'esta_activo' => 1
    ]
];

$usuario_ids = [$superadmin_id]; // El ID del superadmin ya está en la lista
foreach ($usuarios as $usuario) {
    $usuario_id = $conexion->insert('usuarios', $usuario);
    $usuario_ids[] = $usuario_id;
    echo "👤 Usuario '{$usuario['username']}' creado con ID: $usuario_id\n";
}

echo "\n🔐 Asignando roles a usuarios...\n";

// Mapeo de roles a usuarios (IDs de roles asumiendo el orden de inserción original)
$roles_para_asignar = [
    1, // superadmin -> superadmin (rol_id 1)
    2, // admin -> admin (rol_id 2)
    4, // usuario_mina -> supervisor_mina (rol_id 4)
    5, // usuario_planta -> supervisor_planta (rol_id 5)
    6, // usuario_amalgamacion -> supervisor_amalgamacion (rol_id 6)
    7  // usuario_flotacion -> supervisor_flotacion (rol_id 7)
];

// Nombres de roles para mensajes de consola
$roles_nombres_consol = [
    1 => 'superadmin',
    2 => 'admin',
    4 => 'supervisor_mina',
    5 => 'supervisor_planta',
    6 => 'supervisor_amalgamacion',
    7 => 'supervisor_flotacion'
];


foreach ($usuario_ids as $index => $u_id) {
    $rol_id_asignado = $roles_para_asignar[$index];
    $conexion->insert('usuarios_roles', [
        'usuario_id' => $u_id,
        'rol_id' => $rol_id_asignado
    ]);
    echo "🎭 Rol '{$roles_nombres_consol[$rol_id_asignado]}' asignado al usuario ID: {$u_id}\n";
}

echo "\n🔥 ASIGNANDO PERMISOS A ROLES...\n";

// Obtener todos los permisos existentes para una asignación eficiente
$todosLosPermisos = $conexion->select("SELECT id, nombre FROM permisos ORDER BY id");
$permisosPorNombre = [];
foreach ($todosLosPermisos as $p) {
    $permisosPorNombre[$p['nombre']] = $p['id'];
}
echo "📋 Total de permisos encontrados: " . count($todosLosPermisos) . "\n";

function asignarPermisos($conexion, $rol_id, $permisos_a_asignar, $permisosPorNombre, $nombre_rol) {
    $count = 0;
    foreach ($permisos_a_asignar as $permiso_nombre_patron) {
        // Usa un patrón para seleccionar los permisos
        $permisos_filtrados = array_filter($permisosPorNombre, function($k) use ($permiso_nombre_patron) {
            return strpos($k, $permiso_nombre_patron) === 0;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($permisos_filtrados as $permiso_id) {
            try {
                $conexion->insert('roles_permisos', [
                    'rol_id' => $rol_id,
                    'permiso_id' => $permiso_id
                ]);
                $count++;
            } catch (Exception $e) {
                // Ya existe la asignación, ignorar o loguear si es necesario
            }
        }
    }
    echo "✅ Permisos asignados al {$nombre_rol}: {$count}\n";
}


// SUPERADMIN: Todos los permisos (rol_id = 1)
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
        // Ignorar si ya existe
    }
}
echo "✅ Permisos asignados al superadmin: $permisosAsignados\n";


// ADMIN: Todos los permisos (rol_id = 2)
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
        // Ignorar si ya existe
    }
}
echo "✅ Permisos asignados al admin: $permisosAsignadosAdmin\n";


// SUPERVISOR MINA (rol_id = 4): Dashboard + Registros/Controles de Mina
echo "🔥 Asignando permisos al SUPERVISOR DE MINA...\n";
asignarPermisos($conexion, 4, [
    'dashboard.',
    'registros.produccion_mina.',
    'controles.mina.'
], $permisosPorNombre, 'supervisor de mina');

// SUPERVISOR PLANTA (rol_id = 5): Dashboard + Registros/Controles de Planta
echo "🔥 Asignando permisos al SUPERVISOR DE PLANTA...\n";
asignarPermisos($conexion, 5, [
    'dashboard.',
    'registros.planta.',
    'controles.planta.'
], $permisosPorNombre, 'supervisor de planta');

// SUPERVISOR AMALGAMACIÓN (rol_id = 6): Dashboard + Registros/Controles de Amalgamación
echo "🔥 Asignando permisos al SUPERVISOR DE AMALGAMACIÓN...\n";
asignarPermisos($conexion, 6, [
    'dashboard.',
    'registros.amalgamacion.',
    'controles.amalgamacion.'
], $permisosPorNombre, 'supervisor de amalgamación');

// SUPERVISOR FLOTACIÓN (rol_id = 7): Dashboard + Registros/Controles de Flotación
echo "🔥 Asignando permisos al SUPERVISOR DE FLOTACIÓN...\n";
asignarPermisos($conexion, 7, [
    'dashboard.',
    'registros.flotacion.',
    'controles.flotacion.'
], $permisosPorNombre, 'supervisor de flotación');

echo "\n⚙️ Creando preferencias de usuarios...\n";

// Crear preferencias para cada usuario (manteniendo el orden de $usuario_ids)
$preferencias_data = [
    // Superadmin
    [
        'usuario_id' => $usuario_ids[0], // superadmin
        'tema' => 'oscuro', 'idioma' => 'es', 'navbar_design' => 'modern',
        'navbar_bg_color' => '#2c3e50', 'navbar_text_color' => '#ecf0f1',
        'navbar_active_bg_color' => '#3498db', 'navbar_active_text_color' => '#ffffff',
        'topbar_bg_color' => '#2c3e50', 'topbar_text_color' => '#ecf0f1',
        'pagina_inicio' => 'dashboard', 'elementos_por_pagina' => 25
    ],
    // Admin
    [
        'usuario_id' => $usuario_ids[1], // admin
        'tema' => 'claro', 'idioma' => 'es', 'navbar_design' => 'classic',
        'navbar_bg_color' => '#1571b0', 'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff', 'navbar_active_text_color' => '#1571b0',
        'topbar_bg_color' => '#ffffff', 'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard', 'elementos_por_pagina' => 50
    ],
    // Usuario Mina
    [
        'usuario_id' => $usuario_ids[2], // usuario_mina
        'tema' => 'claro', 'idioma' => 'es', 'navbar_design' => 'default',
        'navbar_bg_color' => '#8B4513', 'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff', 'navbar_active_text_color' => '#8B4513',
        'topbar_bg_color' => '#ffffff', 'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard', 'elementos_por_pagina' => 20
    ],
    // Usuario Planta
    [
        'usuario_id' => $usuario_ids[3], // usuario_planta
        'tema' => 'claro', 'idioma' => 'es', 'navbar_design' => 'default',
        'navbar_bg_color' => '#2E8B57', 'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff', 'navbar_active_text_color' => '#2E8B57',
        'topbar_bg_color' => '#ffffff', 'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard', 'elementos_por_pagina' => 20
    ],
    // Usuario Amalgamación
    [
        'usuario_id' => $usuario_ids[4], // usuario_amalgamacion
        'tema' => 'claro', 'idioma' => 'es', 'navbar_design' => 'default',
        'navbar_bg_color' => '#FF6347', 'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff', 'navbar_active_text_color' => '#FF6347',
        'topbar_bg_color' => '#ffffff', 'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard', 'elementos_por_pagina' => 20
    ],
    // Usuario Flotación
    [
        'usuario_id' => $usuario_ids[5], // usuario_flotacion
        'tema' => 'claro', 'idioma' => 'es', 'navbar_design' => 'default',
        'navbar_bg_color' => '#4169E1', 'navbar_text_color' => '#ffffff',
        'navbar_active_bg_color' => '#ffffff', 'navbar_active_text_color' => '#4169E1',
        'topbar_bg_color' => '#ffffff', 'topbar_text_color' => '#333333',
        'pagina_inicio' => 'dashboard', 'elementos_por_pagina' => 20
    ]
];

$usuarios_nombres_pref = [
    'superadmin',
    'admin',
    'usuario_mina',
    'usuario_planta',
    'usuario_amalgamacion',
    'usuario_flotacion'
];

foreach ($preferencias_data as $index => $pref) {
    $conexion->insert('preferencias_usuarios', $pref);
    echo "🎨 Preferencias creadas para usuario '{$usuarios_nombres_pref[$index]}'\n";
}

echo "\n⏰ Insertando turnos fijos (T1, T2, T3, T4) en todas las tablas de turnos...\n";

$turnos_fijos = [
    ['codigo' => 'T1', 'nombre' => 'Turno Mañana'],
    ['codigo' => 'T2', 'nombre' => 'Turno Tarde'],
    ['codigo' => 'T3', 'nombre' => 'Turno Noche'],
    ['codigo' => 'T4', 'nombre' => 'Turno Día']
];

$tablas_turnos = ['turnos_mina', 'turnos_planta', 'turnos_amalgamacion', 'turnos_flotacion'];

foreach ($tablas_turnos as $tabla_turno) {
    echo "  -> Procesando tabla: {$tabla_turno}\n";
    foreach ($turnos_fijos as $turno) {
        try {
            // Asegura que los IDs 1,2,3,4 se inserten primero y sean estos turnos
            // Esto solo funciona en una tabla vacía. Si ya tiene datos, los IDs serán consecutivos.
            // Para garantizar ID específicos, podrías usar INSERT INTO ... VALUES (1, 'T1', 'Turno Mañana'), ...
            // Pero es más común que la aplicación maneje la inmutabilidad por `codigo` y `es_fijo`.
            $conexion->insert($tabla_turno, $turno);
            echo "    ✅ Turno '{$turno['codigo']} - {$turno['nombre']}' insertado en {$tabla_turno}.\n";
        } catch (Exception $e) {
            echo "    ⚠️ Advertencia: Turno '{$turno['codigo']}' ya existe en {$tabla_turno} o error al insertar. " . $e->getMessage() . "\n";
        }
    }
}


echo "\n🏭 Creando datos iniciales de controles adicionales...\n";

// Insertar datos iniciales para frentes de mina
$frentes_mina_iniciales = [
    ['codigo' => 'FM001', 'nombre' => 'Frente Norte'],
    ['codigo' => 'FM002', 'nombre' => 'Frente Sur'],
    ['codigo' => 'FM003', 'nombre' => 'Frente Este'],
    ['codigo' => 'FM004', 'nombre' => 'Frente Oeste']
];

foreach ($frentes_mina_iniciales as $frente) {
    try {
        $conexion->insert('frentes_mina', $frente);
        echo "🏔️ Frente de mina '{$frente['nombre']}' creado\n";
    } catch (Exception $e) {
        echo "⚠️ Advertencia: Frente de mina '{$frente['nombre']}' ya existe. " . $e->getMessage() . "\n";
    }
}

// Insertar datos iniciales para líneas de planta
$lineas_planta_iniciales = [
    ['codigo' => 'LP001', 'nombre' => 'Línea A'],
    ['codigo' => 'LP002', 'nombre' => 'Línea B'],
    ['codigo' => 'LP003', 'nombre' => 'Línea C']
];

foreach ($lineas_planta_iniciales as $linea) {
    try {
        $conexion->insert('lineas_planta', $linea);
        echo "🏭 Línea de planta '{$linea['nombre']}' creada\n";
    } catch (Exception $e) {
        echo "⚠️ Advertencia: Línea de planta '{$linea['nombre']}' ya existe. " . $e->getMessage() . "\n";
    }
}

// Insertar datos iniciales para concentrados de planta
$concentrados_planta_iniciales = [
    ['codigo' => 'CP001', 'nombre' => 'Concentrado Oro'],
    ['codigo' => 'CP002', 'nombre' => 'Concentrado Plata'],
    ['codigo' => 'CP003', 'nombre' => 'Concentrado Mixto']
];

foreach ($concentrados_planta_iniciales as $concentrado) {
    try {
        $conexion->insert('concentrados_planta', $concentrado);
        echo "🥇 Concentrado de planta '{$concentrado['nombre']}' creado\n";
    } catch (Exception $e) {
        echo "⚠️ Advertencia: Concentrado de planta '{$concentrado['nombre']}' ya existe. " . $e->getMessage() . "\n";
    }
}

// Puedes añadir más inserciones para amalgamadores, cargas_amalgamacion, productos_flotacion si es necesario
// para tener datos iniciales adicionales en esos controles.


echo "\n🔍 VERIFICACIÓN FINAL...\n";

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
    echo "   • {$rp['rol']}: {$rp['total_permisos']} permisos\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 SEEDER OPTIMIZADO COMPLETADO EXITOSAMENTE\n";
echo str_repeat("=", 60) . "\n";
echo "📊 Resumen:\n";
echo "   • Usuarios creados: " . count($usuario_ids) . "\n";
echo "   • Roles especializados: " . count($roles_para_asignar) . "\n";
echo "   • Preferencias personalizadas: " . count($preferencias_data) . "\n";
echo "   • Turnos fijos (T1, T2, T3, T4) insertados en todas las tablas de turnos.\n";
echo "   • Datos iniciales de controles adicionales creados.\n";

echo "\n🔑 Credenciales de acceso:\n";
echo "   • superadmin / admin123 (Acceso total)\n";
echo "   • admin / admin123 (Administrador completo)\n";
echo "   • usuario_mina / mina123 (Producción de Mina)\n";
echo "   • usuario_planta / planta123 (Planta)\n";
echo "   • usuario_amalgamacion / amalgama123 (Amalgamación)\n";
echo "   • usuario_flotacion / flotacion123 (Flotación)\n";

echo "\n✅ Base de datos actualizada y lista para usar!\n";
echo "🎯 Cada usuario tiene permisos específicos para su área de trabajo.\n";