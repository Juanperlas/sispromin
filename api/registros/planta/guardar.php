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
$linea_id = isset($_POST["linea_id"]) ? intval($_POST["linea_id"]) : 0;
$concentrado_id = isset($_POST["concentrado_id"]) ? intval($_POST["concentrado_id"]) : 0;
$material_procesado = isset($_POST["material_procesado"]) ? floatval($_POST["material_procesado"]) : 0;
$produccion_cantidad = isset($_POST["produccion_cantidad"]) ? floatval($_POST["produccion_cantidad"]) : 0;
$peso_aproximado_kg = isset($_POST["peso_aproximado_kg"]) && $_POST["peso_aproximado_kg"] !== "" ? floatval($_POST["peso_aproximado_kg"]) : null;
$ley_inferido_metalurgista = isset($_POST["ley_inferido_metalurgista"]) && $_POST["ley_inferido_metalurgista"] !== "" ? floatval($_POST["ley_inferido_metalurgista"]) : null;

// Datos de laboratorio (opcionales)
$codigo_muestra = isset($_POST["codigo_muestra"]) ? sanitizar($_POST["codigo_muestra"]) : "";
$ley_laboratorio = isset($_POST["ley_laboratorio"]) && $_POST["ley_laboratorio"] !== "" ? floatval($_POST["ley_laboratorio"]) : null;

// Determinar si es creación o edición
$esEdicion = $id > 0;

// Verificar permisos según la operación
if ($esEdicion) {
    if (!tienePermiso("registros.planta.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar registros de producción planta"]);
        exit;
    }
} else {
    if (!tienePermiso("registros.planta.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear registros de producción planta"]);
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

if ($linea_id <= 0) {
    $errores["linea_id"] = "La línea es obligatoria";
}

if ($concentrado_id <= 0) {
    $errores["concentrado_id"] = "El concentrado es obligatorio";
}

if ($material_procesado < 0) {
    $errores["material_procesado"] = "El material procesado debe ser mayor o igual a 0";
}

if ($produccion_cantidad < 0) {
    $errores["produccion_cantidad"] = "La producción cantidad debe ser mayor o igual a 0";
}

// Validar campos de laboratorio si se proporcionaron
if (!empty($codigo_muestra) || $ley_laboratorio !== null) {
    if (empty($codigo_muestra)) {
        $errores["codigo_muestra"] = "El código de muestra es obligatorio cuando se proporciona ley de laboratorio";
    }
    if ($ley_laboratorio === null || $ley_laboratorio < 0) {
        $errores["ley_laboratorio"] = "La ley de laboratorio debe ser un valor válido mayor o igual a 0";
    }
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
    function generarCodigoRegistro($fecha, $turno_id, $linea_id, $conexion) {
        // Obtener código del turno
        $turno = $conexion->selectOne("SELECT codigo FROM turnos_planta WHERE id = ?", [$turno_id]);
        if (!$turno) {
            throw new Exception("Turno no encontrado");
        }

        // Obtener código de la línea
        $linea = $conexion->selectOne("SELECT codigo FROM lineas_planta WHERE id = ?", [$linea_id]);
        if (!$linea) {
            throw new Exception("Línea no encontrada");
        }

        // Formatear fecha (YYMMDD)
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        $fechaFormateada = $fechaObj->format('ymd');

        // Generar código: RP + fecha + código turno + código línea
        return "RP" . $fechaFormateada . $turno['codigo'] . $linea['codigo'];
    }

    if ($esEdicion) {
        // Verificar que el registro existe
        $registroExistente = $conexion->selectOne("SELECT id FROM planta WHERE id = ?", [$id]);
        if (!$registroExistente) {
            throw new Exception("El registro no existe");
        }

        // Generar nuevo código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $linea_id, $conexion);

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM planta WHERE codigo_registro = ? AND id != ?",
            [$codigo_registro, $id]
        );
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha, turno y línea seleccionados");
        }

        // Actualizar registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "linea_id" => $linea_id,
            "concentrado_id" => $concentrado_id,
            "material_procesado" => $material_procesado,
            "produccion_cantidad" => $produccion_cantidad,
            "peso_aproximado_kg" => $peso_aproximado_kg,
            "ley_inferido_metalurgista" => $ley_inferido_metalurgista
        ];

        $resultado = $conexion->update("planta", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar el registro");
        }

        // Actualizar o eliminar registro de laboratorio
        $laboratorioExistente = $conexion->selectOne(
            "SELECT id FROM laboratorio WHERE tipo_registro = 'planta' AND registro_id = ?",
            [$id]
        );

        if (!empty($codigo_muestra) && $ley_laboratorio !== null) {
            // Actualizar o crear registro de laboratorio
            $datosLab = [
                "codigo_muestra" => $codigo_muestra,
                "ley_laboratorio" => $ley_laboratorio
            ];

            if ($laboratorioExistente) {
                $conexion->update("laboratorio", $datosLab, "id = ?", [$laboratorioExistente['id']]);
            } else {
                $datosLab["tipo_registro"] = "planta";
                $datosLab["registro_id"] = $id;
                $conexion->insert("laboratorio", $datosLab);
            }
        } else {
            // Eliminar registro de laboratorio si existe
            if ($laboratorioExistente) {
                $conexion->delete("laboratorio", "id = ?", [$laboratorioExistente['id']]);
            }
        }

        $mensaje = "Registro actualizado correctamente";
        $registroId = $id;
    } else {
        // Generar código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $linea_id, $conexion);

        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM planta WHERE codigo_registro = ?", [$codigo_registro]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha, turno y línea seleccionados");
        }

        // Crear nuevo registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "linea_id" => $linea_id,
            "concentrado_id" => $concentrado_id,
            "material_procesado" => $material_procesado,
            "produccion_cantidad" => $produccion_cantidad,
            "peso_aproximado_kg" => $peso_aproximado_kg,
            "ley_inferido_metalurgista" => $ley_inferido_metalurgista,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $registroId = $conexion->insert("planta", $datos);

        if (!$registroId) {
            throw new Exception("Error al crear el registro");
        }

        // Crear registro de laboratorio si se proporcionaron datos
        if (!empty($codigo_muestra) && $ley_laboratorio !== null) {
            $datosLab = [
                "tipo_registro" => "planta",
                "registro_id" => $registroId,
                "codigo_muestra" => $codigo_muestra,
                "ley_laboratorio" => $ley_laboratorio
            ];

            $conexion->insert("laboratorio", $datosLab);
        }

        $mensaje = "Registro creado correctamente";
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => $mensaje,
        "id" => $registroId,
        "codigo_registro" => $codigo_registro
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al guardar el registro: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en guardar.php (planta): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
