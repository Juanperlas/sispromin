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
$frente_id = isset($_POST["frente_id"]) ? intval($_POST["frente_id"]) : 0;
$material_extraido = isset($_POST["material_extraido"]) ? floatval($_POST["material_extraido"]) : 0;
$desmonte = isset($_POST["desmonte"]) ? floatval($_POST["desmonte"]) : 0;
$ley_inferido_geologo = isset($_POST["ley_inferido_geologo"]) && $_POST["ley_inferido_geologo"] !== "" ? floatval($_POST["ley_inferido_geologo"]) : null;

// Datos de laboratorio (opcionales)
$codigo_muestra = isset($_POST["codigo_muestra"]) ? sanitizar($_POST["codigo_muestra"]) : "";
$ley_laboratorio = isset($_POST["ley_laboratorio"]) && $_POST["ley_laboratorio"] !== "" ? floatval($_POST["ley_laboratorio"]) : null;

// Determinar si es creación o edición
$esEdicion = $id > 0;

// Verificar permisos según la operación
if ($esEdicion) {
    if (!tienePermiso("registros.produccion_mina.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar registros de producción mina"]);
        exit;
    }
} else {
    if (!tienePermiso("registros.produccion_mina.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear registros de producción mina"]);
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

if ($frente_id <= 0) {
    $errores["frente_id"] = "El frente es obligatorio";
}

if ($material_extraido < 0) {
    $errores["material_extraido"] = "El material extraído debe ser mayor o igual a 0";
}

if ($desmonte < 0) {
    $errores["desmonte"] = "El desmonte debe ser mayor o igual a 0";
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
    function generarCodigoRegistro($fecha, $turno_id, $conexion) {
        // Obtener código del turno
        $turno = $conexion->selectOne("SELECT codigo FROM turnos_mina WHERE id = ?", [$turno_id]);
        if (!$turno) {
            throw new Exception("Turno no encontrado");
        }

        // Formatear fecha (YYMMDD)
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        $fechaFormateada = $fechaObj->format('ymd');

        // Generar código: RM + fecha + código turno
        return "RM" . $fechaFormateada . $turno['codigo'];
    }

    if ($esEdicion) {
        // Verificar que el registro existe
        $registroExistente = $conexion->selectOne("SELECT id FROM produccion_mina WHERE id = ?", [$id]);
        if (!$registroExistente) {
            throw new Exception("El registro no existe");
        }

        // Generar nuevo código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $conexion);

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM produccion_mina WHERE codigo_registro = ? AND id != ?",
            [$codigo_registro, $id]
        );
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha y turno seleccionados");
        }

        // Actualizar registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "frente_id" => $frente_id,
            "material_extraido" => $material_extraido,
            "desmonte" => $desmonte,
            "ley_inferido_geologo" => $ley_inferido_geologo
        ];

        $resultado = $conexion->update("produccion_mina", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar el registro");
        }

        // Actualizar o eliminar registro de laboratorio
        $laboratorioExistente = $conexion->selectOne(
            "SELECT id FROM laboratorio WHERE tipo_registro = 'produccion_mina' AND registro_id = ?",
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
                $datosLab["tipo_registro"] = "produccion_mina";
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
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $conexion);

        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM produccion_mina WHERE codigo_registro = ?", [$codigo_registro]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha y turno seleccionados");
        }

        // Crear nuevo registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "frente_id" => $frente_id,
            "material_extraido" => $material_extraido,
            "desmonte" => $desmonte,
            "ley_inferido_geologo" => $ley_inferido_geologo,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $registroId = $conexion->insert("produccion_mina", $datos);

        if (!$registroId) {
            throw new Exception("Error al crear el registro");
        }

        // Crear registro de laboratorio si se proporcionaron datos
        if (!empty($codigo_muestra) && $ley_laboratorio !== null) {
            $datosLab = [
                "tipo_registro" => "produccion_mina",
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
    error_log("Error en guardar.php (produccion_mina): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
