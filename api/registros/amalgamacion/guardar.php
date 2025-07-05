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
$amalgamador_id = isset($_POST["amalgamador_id"]) ? intval($_POST["amalgamador_id"]) : 0;
$cantidad_carga_concentrados = isset($_POST["cantidad_carga_concentrados"]) ? floatval($_POST["cantidad_carga_concentrados"]) : 0;
$carga_id = isset($_POST["carga_id"]) ? intval($_POST["carga_id"]) : 0;
$carga_mercurio_kg = isset($_POST["carga_mercurio_kg"]) ? floatval($_POST["carga_mercurio_kg"]) : 0;
$amalgamacion_gramos = isset($_POST["amalgamacion_gramos"]) ? floatval($_POST["amalgamacion_gramos"]) : 0;

// Productos químicos opcionales
$soda_caustica_kg = isset($_POST["soda_caustica_kg"]) && $_POST["soda_caustica_kg"] !== "" ? floatval($_POST["soda_caustica_kg"]) : null;
$detergente_kg = isset($_POST["detergente_kg"]) && $_POST["detergente_kg"] !== "" ? floatval($_POST["detergente_kg"]) : null;
$cal_kg = isset($_POST["cal_kg"]) && $_POST["cal_kg"] !== "" ? floatval($_POST["cal_kg"]) : null;
$lejia_litros = isset($_POST["lejia_litros"]) && $_POST["lejia_litros"] !== "" ? floatval($_POST["lejia_litros"]) : null;
$mercurio_recuperado_kg = isset($_POST["mercurio_recuperado_kg"]) && $_POST["mercurio_recuperado_kg"] !== "" ? floatval($_POST["mercurio_recuperado_kg"]) : null;
$factor_conversion_amalg_au = isset($_POST["factor_conversion_amalg_au"]) ? floatval($_POST["factor_conversion_amalg_au"]) : 3.3;

// Determinar si es creación o edición
$esEdicion = $id > 0;

// Verificar permisos según la operación
if ($esEdicion) {
    if (!tienePermiso("registros.amalgamacion.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar registros de amalgamación"]);
        exit;
    }
} else {
    if (!tienePermiso("registros.amalgamacion.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear registros de amalgamación"]);
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

if ($amalgamador_id <= 0) {
    $errores["amalgamador_id"] = "El amalgamador es obligatorio";
}

if ($cantidad_carga_concentrados < 0) {
    $errores["cantidad_carga_concentrados"] = "La cantidad de carga debe ser mayor o igual a 0";
}

if ($carga_id <= 0) {
    $errores["carga_id"] = "El tipo de carga es obligatorio";
}

if ($carga_mercurio_kg < 0) {
    $errores["carga_mercurio_kg"] = "La carga de mercurio debe ser mayor o igual a 0";
}

if ($amalgamacion_gramos < 0) {
    $errores["amalgamacion_gramos"] = "La amalgamación debe ser mayor o igual a 0";
}

if ($factor_conversion_amalg_au <= 0) {
    $errores["factor_conversion_amalg_au"] = "El factor de conversión debe ser mayor a 0";
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
        $turno = $conexion->selectOne("SELECT codigo FROM turnos_amalgamacion WHERE id = ?", [$turno_id]);
        if (!$turno) {
            throw new Exception("Turno no encontrado");
        }

        // Obtener código de la línea
        $linea = $conexion->selectOne("SELECT codigo FROM lineas_amalgamacion WHERE id = ?", [$linea_id]);
        if (!$linea) {
            throw new Exception("Línea no encontrada");
        }

        // Formatear fecha (YYMMDD)
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        $fechaFormateada = $fechaObj->format('ymd');

        // Generar código: RA + fecha + código turno + código línea
        return "RA" . $fechaFormateada . $turno['codigo'] . $linea['codigo'];
    }

    if ($esEdicion) {
        // Verificar que el registro existe
        $registroExistente = $conexion->selectOne("SELECT id FROM amalgamacion WHERE id = ?", [$id]);
        if (!$registroExistente) {
            throw new Exception("El registro no existe");
        }

        // Generar nuevo código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $linea_id, $conexion);

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM amalgamacion WHERE codigo_registro = ? AND id != ?",
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
            "amalgamador_id" => $amalgamador_id,
            "cantidad_carga_concentrados" => $cantidad_carga_concentrados,
            "carga_id" => $carga_id,
            "carga_mercurio_kg" => $carga_mercurio_kg,
            "soda_caustica_kg" => $soda_caustica_kg,
            "detergente_kg" => $detergente_kg,
            "cal_kg" => $cal_kg,
            "lejia_litros" => $lejia_litros,
            "amalgamacion_gramos" => $amalgamacion_gramos,
            "mercurio_recuperado_kg" => $mercurio_recuperado_kg,
            "factor_conversion_amalg_au" => $factor_conversion_amalg_au
        ];

        $resultado = $conexion->update("amalgamacion", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar el registro");
        }

        $mensaje = "Registro actualizado correctamente";
        $registroId = $id;
    } else {
        // Generar código de registro
        $codigo_registro = generarCodigoRegistro($fecha, $turno_id, $linea_id, $conexion);

        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM amalgamacion WHERE codigo_registro = ?", [$codigo_registro]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un registro con ese código para la fecha, turno y línea seleccionados");
        }

        // Crear nuevo registro
        $datos = [
            "codigo_registro" => $codigo_registro,
            "fecha" => $fecha,
            "turno_id" => $turno_id,
            "linea_id" => $linea_id,
            "amalgamador_id" => $amalgamador_id,
            "cantidad_carga_concentrados" => $cantidad_carga_concentrados,
            "carga_id" => $carga_id,
            "carga_mercurio_kg" => $carga_mercurio_kg,
            "soda_caustica_kg" => $soda_caustica_kg,
            "detergente_kg" => $detergente_kg,
            "cal_kg" => $cal_kg,
            "lejia_litros" => $lejia_litros,
            "amalgamacion_gramos" => $amalgamacion_gramos,
            "mercurio_recuperado_kg" => $mercurio_recuperado_kg,
            "factor_conversion_amalg_au" => $factor_conversion_amalg_au,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $registroId = $conexion->insert("amalgamacion", $datos);

        if (!$registroId) {
            throw new Exception("Error al crear el registro");
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
    error_log("Error en guardar.php (amalgamacion): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
