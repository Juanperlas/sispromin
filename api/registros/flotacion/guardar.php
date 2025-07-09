<?php
// Incluir archivos necesarios
require_once "../../../db/funciones.php";
require_once "../../../db/conexion.php";

// Verificar si es una solicitud AJAX
$esAjax = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

if (!$esAjax) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso no permitido"]);
    exit;
}

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Obtener datos del formulario
$id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$fecha = isset($_POST["fecha"]) ? sanitizar($_POST["fecha"]) : "";
$turno_id = isset($_POST["turno_id"]) ? intval($_POST["turno_id"]) : 0;
$carga_mineral_promedio = isset($_POST["carga_mineral_promedio"]) ? floatval($_POST["carga_mineral_promedio"]) : 0;

// Datos opcionales
$carga_mineral_extra = isset($_POST["carga_mineral_extra"]) && $_POST["carga_mineral_extra"] !== "" ? floatval($_POST["carga_mineral_extra"]) : null;
$codigo_muestra_mat_extra = isset($_POST["codigo_muestra_mat_extra"]) && $_POST["codigo_muestra_mat_extra"] !== "" ? sanitizar($_POST["codigo_muestra_mat_extra"]) : null;
$ley_inferido_metalurgista_extra = isset($_POST["ley_inferido_metalurgista_extra"]) && $_POST["ley_inferido_metalurgista_extra"] !== "" ? floatval($_POST["ley_inferido_metalurgista_extra"]) : null;

// Datos de laboratorio opcionales
$codigo_muestra = isset($_POST["codigo_muestra"]) && $_POST["codigo_muestra"] !== "" ? sanitizar($_POST["codigo_muestra"]) : null;
$ley_laboratorio = isset($_POST["ley_laboratorio"]) && $_POST["ley_laboratorio"] !== "" ? floatval($_POST["ley_laboratorio"]) : null;

// Productos químicos
$productos = isset($_POST["productos"]) ? json_decode($_POST["productos"], true) : [];

// Determinar si es creación o edición
$esEdicion = $id > 0;

// Verificar permisos según la operación
if ($esEdicion) {
    if (!tienePermiso("registros.flotacion.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar registros de flotación"]);
        exit;
    }
} else {
    if (!tienePermiso("registros.flotacion.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear registros de flotación"]);
        exit;
    }
}

// Validar datos obligatorios
$errores = [];

if (empty($fecha)) {
    $errores["fecha"] = "La fecha es obligatoria";
}

if ($turno_id <= 0) {
    $errores["turno_id"] = "El turno es obligatorio";
}

if ($carga_mineral_promedio < 0) {
    $errores["carga_mineral_promedio"] = "La carga mineral promedio debe ser mayor o igual a 0";
}

// Validar código de muestra extra si hay carga extra
if ($carga_mineral_extra && $carga_mineral_extra > 0 && empty($codigo_muestra_mat_extra)) {
    $errores["codigo_muestra_mat_extra"] = "Debe ingresar el código de muestra para el material extra";
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos o inválidos",
        "errors" => $errores
    ]);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Iniciar transacción
    $conn->beginTransaction();

    // Función para generar código de registro
    function generarCodigoRegistro($fecha, $turno_id, $conexion)
    {
        // Obtener código del turno
        $turno = $conexion->selectOne("SELECT codigo FROM turnos_flotacion WHERE id = ?", [$turno_id]);
        if (!$turno) {
            throw new Exception("Turno no encontrado");
        }

        // Formatear fecha (YYMMDD)
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        $fechaFormateada = $fechaObj->format('ymd');

        // Generar código: RF + fecha + código turno
        return "RF" . $fechaFormateada . $turno['codigo'];
    }

    // Función para comparar productos
    function compararProductos($productosActuales, $productosNuevos)
    {
        // Normalizar productos actuales
        $actualesNormalizados = [];
        foreach ($productosActuales as $p) {
            $actualesNormalizados[$p['producto_id']] = floatval($p['cantidad']);
        }

        // Normalizar productos nuevos
        $nuevosNormalizados = [];
        foreach ($productosNuevos as $p) {
            if (!empty($p['producto_id']) && !empty($p['cantidad'])) {
                $nuevosNormalizados[intval($p['producto_id'])] = floatval($p['cantidad']);
            }
        }

        // Comparar
        return $actualesNormalizados === $nuevosNormalizados;
    }

    if ($esEdicion) {
        // Verificar que el registro existe
        $registroExistente = $conexion->selectOne("SELECT id FROM flotacion WHERE id = ?", [$id]);
        if (!$registroExistente) {
            throw new Exception("El registro no existe");
        }

        // Generar nuevo código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $conexion);

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM flotacion WHERE codigo_registro = ? AND id != ?",
            [$codigo_registro, $id]
        );
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha y turno seleccionados");
        }

        // Verificar código de muestra extra único (si se proporciona)
        if ($codigo_muestra_mat_extra) {
            $codigoExtraDuplicado = $conexion->selectOne(
                "SELECT id FROM flotacion WHERE codigo_muestra_mat_extra = ? AND id != ?",
                [$codigo_muestra_mat_extra, $id]
            );
            if ($codigoExtraDuplicado) {
                throw new Exception("El código de muestra de material extra ya existe");
            }
        }

        // Actualizar registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "carga_mineral_promedio" => $carga_mineral_promedio,
            "carga_mineral_extra" => $carga_mineral_extra,
            "codigo_muestra_mat_extra" => $codigo_muestra_mat_extra,
            "ley_inferido_metalurgista_extra" => $ley_inferido_metalurgista_extra
        ];

        $resultado = $conexion->update("flotacion", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar el registro");
        }

        // Actualizar o crear registro de laboratorio si se proporcionan datos
        if ($codigo_muestra || $ley_laboratorio) {
            // Verificar si ya existe un registro de laboratorio
            $labExistente = $conexion->selectOne(
                "SELECT id FROM laboratorio WHERE registro_id = ? AND tipo_registro = 'flotacion'",
                [$id]
            );

            if ($labExistente) {
                // Actualizar registro existente
                $datosLab = [
                    "codigo_muestra" => $codigo_muestra,
                    "ley_laboratorio" => $ley_laboratorio
                ];
                $conexion->update("laboratorio", $datosLab, "id = ?", [$labExistente['id']]);
            } else {
                // Crear nuevo registro
                $datosLab = [
                    "registro_id" => $id,
                    "tipo_registro" => "flotacion",
                    "codigo_muestra" => $codigo_muestra,
                    "ley_laboratorio" => $ley_laboratorio,
                    "creado_en" => date("Y-m-d H:i:s")
                ];
                $conexion->insert("laboratorio", $datosLab);
            }
        } else {
            // Eliminar registro de laboratorio si existe pero no se proporcionan datos
            $conexion->delete("laboratorio", "registro_id = ? AND tipo_registro = 'flotacion'", [$id]);
        }

        // MEJORAR: Solo actualizar productos si realmente cambiaron
        $productosActuales = $conexion->select(
            "SELECT producto_id, cantidad FROM flotacion_productos WHERE flotacion_id = ?",
            [$id]
        );

        $productosHanCambiado = !compararProductos($productosActuales, $productos);

        if ($productosHanCambiado) {
            // Solo eliminar y reinsertar si hay cambios
            $conexion->delete("flotacion_productos", "flotacion_id = ?", [$id]);

            // Insertar productos químicos nuevos
            if (!empty($productos)) {
                foreach ($productos as $producto) {
                    if (!empty($producto['producto_id']) && !empty($producto['cantidad'])) {
                        $datosProducto = [
                            "flotacion_id" => $id,
                            "producto_id" => intval($producto['producto_id']),
                            "cantidad" => floatval($producto['cantidad'])
                        ];
                        $conexion->insert("flotacion_productos", $datosProducto);
                    }
                }
            }
        }

        $mensaje = "Registro actualizado correctamente";
        $registroId = $id;
    } else {
        // Generar código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $conexion);

        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM flotacion WHERE codigo_registro = ?", [$codigo_registro]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha y turno seleccionados");
        }

        // Verificar código de muestra extra único (si se proporciona)
        if ($codigo_muestra_mat_extra) {
            $codigoExtraDuplicado = $conexion->selectOne(
                "SELECT id FROM flotacion WHERE codigo_muestra_mat_extra = ?",
                [$codigo_muestra_mat_extra]
            );
            if ($codigoExtraDuplicado) {
                throw new Exception("El código de muestra de material extra ya existe");
            }
        }

        // Crear nuevo registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "carga_mineral_promedio" => $carga_mineral_promedio,
            "carga_mineral_extra" => $carga_mineral_extra,
            "codigo_muestra_mat_extra" => $codigo_muestra_mat_extra,
            "ley_inferido_metalurgista_extra" => $ley_inferido_metalurgista_extra,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $registroId = $conexion->insert("flotacion", $datos);

        if (!$registroId) {
            throw new Exception("Error al crear el registro");
        }

        // Crear registro de laboratorio si se proporcionan datos
        if ($codigo_muestra || $ley_laboratorio) {
            $datosLab = [
                "registro_id" => $registroId,
                "tipo_registro" => "flotacion",
                "codigo_muestra" => $codigo_muestra,
                "ley_laboratorio" => $ley_laboratorio,
                "creado_en" => date("Y-m-d H:i:s")
            ];
            $conexion->insert("laboratorio", $datosLab);
        }

        // Insertar productos químicos
        if (!empty($productos)) {
            foreach ($productos as $producto) {
                if (!empty($producto['producto_id']) && !empty($producto['cantidad'])) {
                    $datosProducto = [
                        "flotacion_id" => $registroId,
                        "producto_id" => intval($producto['producto_id']),
                        "cantidad" => floatval($producto['cantidad'])
                    ];
                    $conexion->insert("flotacion_productos", $datosProducto);
                }
            }
        }

        $mensaje = "Registro creado correctamente";
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta exitosa
    $response = [
        "success" => true,
        "message" => $mensaje,
        "data" => [
            "id" => $registroId,
            "codigo_registro" => $codigo_registro
        ]
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en guardar.php (flotacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
