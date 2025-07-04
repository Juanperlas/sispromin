<?php
// Incluir archivos necesarios
require_once "../../../../db/funciones.php";
require_once "../../../../db/conexion.php";

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
$codigo = isset($_POST["codigo"]) ? sanitizar($_POST["codigo"]) : "";
$nombre = isset($_POST["nombre"]) ? sanitizar($_POST["nombre"]) : "";

// Determinar si es creación o edición
$esEdicion = $id > 0;

// Verificar permisos según la operación
if ($esEdicion) {
    if (!tienePermiso("controles.planta.lineas.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar líneas de planta"]);
        exit;
    }
} else {
    if (!tienePermiso("controles.planta.lineas.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear líneas de planta"]);
        exit;
    }
}

// Validar datos obligatorios
if (empty($codigo) || empty($nombre)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos",
        "errors" => [
            "codigo" => empty($codigo) ? "El código es obligatorio" : "",
            "nombre" => empty($nombre) ? "El nombre es obligatorio" : ""
        ]
    ]);
    exit;
}

try {
    // Conexión a la base de datos
    $conexion = new Conexion();
    $conn = $conexion->getConexion();

    // Iniciar transacción
    $conn->beginTransaction();

    if ($esEdicion) {
        // Verificar que la línea existe
        $lineaExistente = $conexion->selectOne("SELECT id FROM lineas_planta WHERE id = ?", [$id]);
        if (!$lineaExistente) {
            throw new Exception("La línea no existe");
        }

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM lineas_planta WHERE codigo = ? AND id != ?",
            [$codigo, $id]
        );
        if ($codigoDuplicado) {
            throw new Exception("Ya existe una línea con ese código");
        }

        // Actualizar línea
        $datos = [
            "codigo" => $codigo,
            "nombre" => $nombre
        ];

        $resultado = $conexion->update("lineas_planta", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar la línea");
        }

        $mensaje = "Línea actualizada correctamente";
        $lineaId = $id;
    } else {
        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM lineas_planta WHERE codigo = ?", [$codigo]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe una línea con ese código");
        }

        // Crear nueva línea
        $datos = [
            "codigo" => $codigo,
            "nombre" => $nombre,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $lineaId = $conexion->insert("lineas_planta", $datos);

        if (!$lineaId) {
            throw new Exception("Error al crear la línea");
        }

        $mensaje = "Línea creada correctamente";
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => $mensaje,
        "id" => $lineaId
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al guardar la línea: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en guardar.php (lineas_planta): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;
