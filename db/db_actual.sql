CREATE DATABASE IF NOT EXISTS sispromin_db;
USE sispromin_db;

-- Tabla de Módulos: Almacena los módulos del sistema dinámicamente
CREATE TABLE modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
);

-- Tabla de Permisos: Define permisos granulares asociados a módulos
CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE RESTRICT,
    INDEX idx_nombre (nombre)
);

-- Tabla de Roles: Almacena roles dinámicos
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    esta_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
);

-- Tabla de Relación Roles-Permisos: Asocia permisos a roles
CREATE TABLE roles_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE RESTRICT,
    UNIQUE (rol_id, permiso_id)
);

-- Tabla de Usuarios: Gestiona los usuarios del sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE,
    dni VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    area VARCHAR(50),
    fotografia VARCHAR(255),
    creado_por INT,
    token_recordatorio VARCHAR(255),
    esta_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_dni (dni)
);

-- Tabla de Preferencias de Usuarios: Almacena configuraciones personalizadas de los usuarios
CREATE TABLE preferencias_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tema VARCHAR(50) DEFAULT 'claro',
    idioma VARCHAR(10) DEFAULT 'es',
    navbar_design VARCHAR(50) DEFAULT 'default',
    navbar_bg_color VARCHAR(7),
    navbar_text_color VARCHAR(7),
    navbar_active_bg_color VARCHAR(7),
    navbar_active_text_color VARCHAR(7),
    topbar_bg_color VARCHAR(7),
    topbar_text_color VARCHAR(7),
    pagina_inicio VARCHAR(50) DEFAULT 'dashboard',
    elementos_por_pagina INT DEFAULT 10,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id)
);

-- Tabla de Relación Usuarios-Roles: Asocia roles a usuarios
CREATE TABLE usuarios_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    UNIQUE (usuario_id, rol_id)
);

-- Tabla de Sesiones de Usuarios: Registra ingresos y cierres de sesión
CREATE TABLE sesiones_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    inicio_sesion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fin_sesion TIMESTAMP,
    esta_activa BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLAS DE CONTROLES - NUEVA ESTRUCTURA
-- =====================================================

-- CONTROLES MINA
CREATE TABLE turnos_mina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE frentes_mina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

-- CONTROLES PLANTA
CREATE TABLE turnos_planta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE lineas_planta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE concentrados_planta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

-- CONTROLES AMALGAMACIÓN
CREATE TABLE turnos_amalgamacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE lineas_amalgamacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE amalgamadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE cargas_amalgamacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

-- CONTROLES FLOTACIÓN
CREATE TABLE turnos_flotacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

CREATE TABLE productos_flotacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo)
);

-- =====================================================
-- TABLAS DE REGISTROS
-- =====================================================

-- Tabla de Registro de Producción de Mina
CREATE TABLE produccion_mina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    turno_id INT NOT NULL,
    frente_id INT NOT NULL,
    material_extraido DECIMAL(15,2) NOT NULL,
    desmonte DECIMAL(15,2) NOT NULL,
    ley_inferido_geologo DECIMAL(15,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turno_id) REFERENCES turnos_mina(id) ON DELETE RESTRICT,
    FOREIGN KEY (frente_id) REFERENCES frentes_mina(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_turno_id (turno_id),
    INDEX idx_frente_id (frente_id)
);

-- Tabla de Registro de Planta
CREATE TABLE planta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    turno_id INT NOT NULL,
    linea_id INT NOT NULL,
    material_procesado DECIMAL(15,2) NOT NULL,
    concentrado_id INT NOT NULL,
    produccion_cantidad DECIMAL(15,2) NOT NULL,
    peso_aproximado_kg DECIMAL(15,2),
    ley_inferido_metalurgista DECIMAL(15,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turno_id) REFERENCES turnos_planta(id) ON DELETE RESTRICT,
    FOREIGN KEY (linea_id) REFERENCES lineas_planta(id) ON DELETE RESTRICT,
    FOREIGN KEY (concentrado_id) REFERENCES concentrados_planta(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_turno_id (turno_id),
    INDEX idx_linea_id (linea_id)
);

-- Tabla de Registro de Amalgamación
CREATE TABLE amalgamacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    turno_id INT NOT NULL,
    linea_id INT NOT NULL,
    amalgamador_id INT NOT NULL,
    cantidad_carga_concentrados DECIMAL(15,1) NOT NULL,
    carga_id INT NOT NULL,
    carga_mercurio_kg DECIMAL(15,2) NOT NULL,
    amalgamacion_gramos DECIMAL(15,2) NOT NULL,
    mercurio_recuperado_kg DECIMAL(15,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turno_id) REFERENCES turnos_amalgamacion(id) ON DELETE RESTRICT,
    FOREIGN KEY (linea_id) REFERENCES lineas_amalgamacion(id) ON DELETE RESTRICT,
    FOREIGN KEY (amalgamador_id) REFERENCES amalgamadores(id) ON DELETE RESTRICT,
    FOREIGN KEY (carga_id) REFERENCES cargas_amalgamacion(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_turno_id (turno_id),
    INDEX idx_linea_id (linea_id)
);

-- Tabla de Registro de Flotación
CREATE TABLE flotacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    turno_id INT NOT NULL,
    carga_mineral_promedio DECIMAL(15,2) NOT NULL,
    carga_mineral_extra DECIMAL(15,2),
    codigo_muestra_mat_extra VARCHAR(50),
    ley_inferido_metalurgista_extra DECIMAL(15,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turno_id) REFERENCES turnos_flotacion(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_turno_id (turno_id)
);

-- Tabla Intermedia para Productos Químicos en Flotación
CREATE TABLE flotacion_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flotacion_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(15,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flotacion_id) REFERENCES flotacion(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos_flotacion(id) ON DELETE RESTRICT,
    UNIQUE (flotacion_id, producto_id)
);

-- Tabla de Informes de Laboratorio
CREATE TABLE laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_registro ENUM('produccion_mina', 'planta', 'flotacion') NOT NULL,
    registro_id INT NOT NULL,
    codigo_muestra VARCHAR(50) NOT NULL,
    ley_laboratorio DECIMAL(15,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo_registro (tipo_registro),
    INDEX idx_registro_id (registro_id),
    INDEX idx_codigo_muestra (codigo_muestra)
);

-- =====================================================
-- INSERCIÓN DE DATOS INICIALES - NUEVA ESTRUCTURA
-- =====================================================

-- Inserción inicial de módulos con nueva estructura
INSERT INTO modulos (nombre, descripcion) VALUES
-- GENERAL
('dashboard', 'Panel principal del sistema'),
-- REGISTROS
('registros', 'Gestión de registros'),
('registros.produccion_mina', 'Registro de producción de mina'),
('registros.planta', 'Registro de planta'),
('registros.amalgamacion', 'Registro de amalgamación'),
('registros.flotacion', 'Registro de flotación'),
('registros.historial_general', 'Módulo de historial general'),
('registros.estadistica', 'Módulo de estadísticas y reportes'),
-- CONTROLES MINA
('controles.mina', 'Gestión de controles de mina'),
('controles.mina.turnos', 'Gestión de turnos de mina'),
('controles.mina.frentes', 'Gestión de frentes de mina'),
-- CONTROLES PLANTA
('controles.planta', 'Gestión de controles de planta'),
('controles.planta.turnos', 'Gestión de turnos de planta'),
('controles.planta.lineas', 'Gestión de líneas de planta'),
('controles.planta.concentrados', 'Gestión de concentrados de planta'),
-- CONTROLES AMALGAMACIÓN
('controles.amalgamacion', 'Gestión de controles de amalgamación'),
('controles.amalgamacion.turnos', 'Gestión de turnos de amalgamación'),
('controles.amalgamacion.lineas', 'Gestión de líneas de amalgamación'),
('controles.amalgamacion.amalgamadores', 'Gestión de amalgamadores'),
('controles.amalgamacion.cargas', 'Gestión de cargas de amalgamación'),
-- CONTROLES FLOTACIÓN
('controles.flotacion', 'Gestión de controles de flotación'),
('controles.flotacion.turnos', 'Gestión de turnos de flotación'),
('controles.flotacion.productos', 'Gestión de productos de flotación'),
-- ADMINISTRACIÓN
('administracion', 'Gestión administrativa'),
('administracion.usuarios', 'Submódulo de usuarios'),
('administracion.rolespermisos', 'Submódulo de roles y permisos'),
('administracion.reportes', 'Submódulo de reportes');

-- Inserción inicial de permisos con nueva estructura
INSERT INTO permisos (modulo_id, nombre, descripcion) VALUES
-- Dashboard (modulo_id: 1)
(1, 'dashboard.acceder', 'Permite acceder al panel principal'),
(1, 'dashboard.ver', 'Permite ver el panel principal'),
-- Registros (modulo_id: 2)
(2, 'registros.acceder', 'Permite acceder al módulo de registros'),
(2, 'registros.ver', 'Permite ver todos los registros'),
-- Producción de Mina (modulo_id: 3)
(3, 'registros.produccion_mina.acceder', 'Permite acceder al módulo de producción de mina'),
(3, 'registros.produccion_mina.ver', 'Permite ver registros de producción de mina'),
(3, 'registros.produccion_mina.crear', 'Permite crear registros de producción de mina'),
(3, 'registros.produccion_mina.editar', 'Permite editar registros de producción de mina'),
(3, 'registros.produccion_mina.eliminar', 'Permite eliminar registros de producción de mina'),
-- Planta (modulo_id: 4)
(4, 'registros.planta.acceder', 'Permite acceder al módulo de planta'),
(4, 'registros.planta.ver', 'Permite ver registros de planta'),
(4, 'registros.planta.crear', 'Permite crear registros de planta'),
(4, 'registros.planta.editar', 'Permite editar registros de planta'),
(4, 'registros.planta.eliminar', 'Permite eliminar registros de planta'),
-- Amalgamación (modulo_id: 5)
(5, 'registros.amalgamacion.acceder', 'Permite acceder al módulo de amalgamación'),
(5, 'registros.amalgamacion.ver', 'Permite ver registros de amalgamación'),
(5, 'registros.amalgamacion.crear', 'Permite crear registros de amalgamación'),
(5, 'registros.amalgamacion.editar', 'Permite editar registros de amalgamación'),
(5, 'registros.amalgamacion.eliminar', 'Permite eliminar registros de amalgamación'),
-- Flotación (modulo_id: 6)
(6, 'registros.flotacion.acceder', 'Permite acceder al módulo de flotación'),
(6, 'registros.flotacion.ver', 'Permite ver registros de flotación'),
(6, 'registros.flotacion.crear', 'Permite crear registros de flotación'),
(6, 'registros.flotacion.editar', 'Permite editar registros de flotación'),
(6, 'registros.flotacion.eliminar', 'Permite eliminar registros de flotación'),
-- Historial General (modulo_id: 7)
(7, 'registros.historial_general.acceder', 'Permite acceder al módulo de historial general'),
(7, 'registros.historial_general.ver', 'Permite ver el historial general'),
(7, 'registros.historial_general.exportar', 'Permite exportar historial general'),
-- Estadística (modulo_id: 8)
(8, 'registros.estadistica.acceder', 'Permite acceder al módulo de estadísticas'),
(8, 'registros.estadistica.ver', 'Permite ver el panel de estadísticas'),
(8, 'registros.estadistica.exportar', 'Permite exportar estadísticas y reportes'),
-- Controles Mina (modulo_id: 9)
(9, 'controles.mina.acceder', 'Permite acceder al módulo de controles de mina'),
(9, 'controles.mina.ver', 'Permite ver el panel de controles de mina'),
-- Turnos Mina (modulo_id: 10)
(10, 'controles.mina.turnos.acceder', 'Permite acceder al submódulo de turnos de mina'),
(10, 'controles.mina.turnos.ver', 'Permite ver la lista de turnos de mina'),
(10, 'controles.mina.turnos.crear', 'Permite crear nuevos turnos de mina'),
(10, 'controles.mina.turnos.editar', 'Permite editar turnos de mina'),
(10, 'controles.mina.turnos.eliminar', 'Permite eliminar turnos de mina'),
-- Frentes Mina (modulo_id: 11)
(11, 'controles.mina.frentes.acceder', 'Permite acceder al submódulo de frentes de mina'),
(11, 'controles.mina.frentes.ver', 'Permite ver la lista de frentes de mina'),
(11, 'controles.mina.frentes.crear', 'Permite crear nuevos frentes de mina'),
(11, 'controles.mina.frentes.editar', 'Permite editar frentes de mina'),
(11, 'controles.mina.frentes.eliminar', 'Permite eliminar frentes de mina'),
-- Controles Planta (modulo_id: 12)
(12, 'controles.planta.acceder', 'Permite acceder al módulo de controles de planta'),
(12, 'controles.planta.ver', 'Permite ver el panel de controles de planta'),
-- Turnos Planta (modulo_id: 13)
(13, 'controles.planta.turnos.acceder', 'Permite acceder al submódulo de turnos de planta'),
(13, 'controles.planta.turnos.ver', 'Permite ver la lista de turnos de planta'),
(13, 'controles.planta.turnos.crear', 'Permite crear nuevos turnos de planta'),
(13, 'controles.planta.turnos.editar', 'Permite editar turnos de planta'),
(13, 'controles.planta.turnos.eliminar', 'Permite eliminar turnos de planta'),
-- Líneas Planta (modulo_id: 14)
(14, 'controles.planta.lineas.acceder', 'Permite acceder al submódulo de líneas de planta'),
(14, 'controles.planta.lineas.ver', 'Permite ver la lista de líneas de planta'),
(14, 'controles.planta.lineas.crear', 'Permite crear nuevas líneas de planta'),
(14, 'controles.planta.lineas.editar', 'Permite editar líneas de planta'),
(14, 'controles.planta.lineas.eliminar', 'Permite eliminar líneas de planta'),
-- Concentrados Planta (modulo_id: 15)
(15, 'controles.planta.concentrados.acceder', 'Permite acceder al submódulo de concentrados de planta'),
(15, 'controles.planta.concentrados.ver', 'Permite ver la lista de concentrados de planta'),
(15, 'controles.planta.concentrados.crear', 'Permite crear nuevos concentrados de planta'),
(15, 'controles.planta.concentrados.editar', 'Permite editar concentrados de planta'),
(15, 'controles.planta.concentrados.eliminar', 'Permite eliminar concentrados de planta'),
-- Controles Amalgamación (modulo_id: 16)
(16, 'controles.amalgamacion.acceder', 'Permite acceder al módulo de controles de amalgamación'),
(16, 'controles.amalgamacion.ver', 'Permite ver el panel de controles de amalgamación'),
-- Turnos Amalgamación (modulo_id: 17)
(17, 'controles.amalgamacion.turnos.acceder', 'Permite acceder al submódulo de turnos de amalgamación'),
(17, 'controles.amalgamacion.turnos.ver', 'Permite ver la lista de turnos de amalgamación'),
(17, 'controles.amalgamacion.turnos.crear', 'Permite crear nuevos turnos de amalgamación'),
(17, 'controles.amalgamacion.turnos.editar', 'Permite editar turnos de amalgamación'),
(17, 'controles.amalgamacion.turnos.eliminar', 'Permite eliminar turnos de amalgamación'),
-- Líneas Amalgamación (modulo_id: 18)
(18, 'controles.amalgamacion.lineas.acceder', 'Permite acceder al submódulo de líneas de amalgamación'),
(18, 'controles.amalgamacion.lineas.ver', 'Permite ver la lista de líneas de amalgamación'),
(18, 'controles.amalgamacion.lineas.crear', 'Permite crear nuevas líneas de amalgamación'),
(18, 'controles.amalgamacion.lineas.editar', 'Permite editar líneas de amalgamación'),
(18, 'controles.amalgamacion.lineas.eliminar', 'Permite eliminar líneas de amalgamación'),
-- Amalgamadores (modulo_id: 19)
(19, 'controles.amalgamacion.amalgamadores.acceder', 'Permite acceder al submódulo de amalgamadores'),
(19, 'controles.amalgamacion.amalgamadores.ver', 'Permite ver la lista de amalgamadores'),
(19, 'controles.amalgamacion.amalgamadores.crear', 'Permite crear nuevos amalgamadores'),
(19, 'controles.amalgamacion.amalgamadores.editar', 'Permite editar amalgamadores'),
(19, 'controles.amalgamacion.amalgamadores.eliminar', 'Permite eliminar amalgamadores'),
-- Cargas Amalgamación (modulo_id: 20)
(20, 'controles.amalgamacion.cargas.acceder', 'Permite acceder al submódulo de cargas de amalgamación'),
(20, 'controles.amalgamacion.cargas.ver', 'Permite ver la lista de cargas de amalgamación'),
(20, 'controles.amalgamacion.cargas.crear', 'Permite crear nuevas cargas de amalgamación'),
(20, 'controles.amalgamacion.cargas.editar', 'Permite editar cargas de amalgamación'),
(20, 'controles.amalgamacion.cargas.eliminar', 'Permite eliminar cargas de amalgamación'),
-- Controles Flotación (modulo_id: 21)
(21, 'controles.flotacion.acceder', 'Permite acceder al módulo de controles de flotación'),
(21, 'controles.flotacion.ver', 'Permite ver el panel de controles de flotación'),
-- Turnos Flotación (modulo_id: 22)
(22, 'controles.flotacion.turnos.acceder', 'Permite acceder al submódulo de turnos de flotación'),
(22, 'controles.flotacion.turnos.ver', 'Permite ver la lista de turnos de flotación'),
(22, 'controles.flotacion.turnos.crear', 'Permite crear nuevos turnos de flotación'),
(22, 'controles.flotacion.turnos.editar', 'Permite editar turnos de flotación'),
(22, 'controles.flotacion.turnos.eliminar', 'Permite eliminar turnos de flotación'),
-- Productos Flotación (modulo_id: 23)
(23, 'controles.flotacion.productos.acceder', 'Permite acceder al submódulo de productos de flotación'),
(23, 'controles.flotacion.productos.ver', 'Permite ver la lista de productos de flotación'),
(23, 'controles.flotacion.productos.crear', 'Permite crear nuevos productos de flotación'),
(23, 'controles.flotacion.productos.editar', 'Permite editar productos de flotación'),
(23, 'controles.flotacion.productos.eliminar', 'Permite eliminar productos de flotación'),
-- Administración (modulo_id: 24)
(24, 'administracion.acceder', 'Permite acceder al módulo de administración'),
(24, 'administracion.ver', 'Permite ver el panel de administración'),
-- Usuarios (modulo_id: 25)
(25, 'administracion.usuarios.acceder', 'Permite acceder al submódulo de usuarios'),
(25, 'administracion.usuarios.ver', 'Permite ver la lista de usuarios'),
(25, 'administracion.usuarios.crear', 'Permite crear nuevos usuarios'),
(25, 'administracion.usuarios.editar', 'Permite editar usuarios existentes'),
-- Roles y Permisos (modulo_id: 26)
(26, 'administracion.rolespermisos.acceder', 'Permite acceder al submódulo de roles y permisos'),
(26, 'administracion.rolespermisos.ver', 'Permite ver roles y permisos'),
(26, 'administracion.rolespermisos.crear', 'Permite crear roles y permisos'),
(26, 'administracion.rolespermisos.editar', 'Permite editar roles y permisos'),
-- Reportes (modulo_id: 27)
(27, 'administracion.reportes.acceder', 'Permite acceder al submódulo de reportes'),
(27, 'administracion.reportes.ver', 'Permite ver reportes'),
(27, 'administracion.reportes.exportar', 'Permite exportar reportes');

-- Inserción inicial de roles actualizados
INSERT INTO roles (nombre, descripcion) VALUES
('superadmin', 'Rol con acceso completo a todas las funcionalidades del sistema'),
('admin', 'Rol administrativo con acceso completo'),
('jefe_operaciones', 'Jefe de operaciones con acceso a todos los registros y controles'),
('supervisor_mina', 'Supervisor especializado en operaciones de mina'),
('supervisor_planta', 'Supervisor especializado en operaciones de planta'),
('supervisor_amalgamacion', 'Supervisor especializado en operaciones de amalgamación'),
('supervisor_flotacion', 'Supervisor especializado en operaciones de flotación'),
('operador_mina', 'Operador especializado en registros de mina'),
('operador_planta', 'Operador especializado en registros de planta'),
('operador_amalgamacion', 'Operador especializado en registros de amalgamación'),
('operador_flotacion', 'Operador especializado en registros de flotación'),
('invitado', 'Rol con permisos limitados, solo visualización');

-- Verificación de módulos creados
SELECT 'Módulos creados:' as verificacion, COUNT(*) as total FROM modulos;

-- Verificar permisos creados
SELECT 'Permisos creados:' as verificacion, COUNT(*) as total FROM permisos;

-- Verificar roles creados
SELECT 'Roles creados:' as verificacion, COUNT(*) as total FROM roles;

-- Mostrar estructura de módulos y permisos
SELECT 'Estructura de permisos:' as verificacion, 
       m.nombre as modulo, 
       COUNT(p.id) as total_permisos
FROM modulos m
LEFT JOIN permisos p ON m.id = p.modulo_id
GROUP BY m.id, m.nombre
ORDER BY m.id;

-- =====================================================
-- BASE DE DATOS COMPLETA CREADA EXITOSAMENTE
-- =====================================================
