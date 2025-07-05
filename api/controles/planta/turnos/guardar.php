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

// --- INICIO DEL CAMBIO ---
// IDs de los turnos predefinidos que no se pueden editar
$turnos_predefinidos_ids = [1, 2, 3, 4];

// Si es una operación de edición y el ID está en la lista de predefinidos
if ($esEdicion && in_array($id, $turnos_predefinidos_ids)) {
    http_response_code(403); // Código de estado Forbidden (Prohibido)
    echo json_encode(["success" => false, "message" => "Este turno es predefinido y no se puede editar."]);
    exit;
}
// --- FIN DEL CAMBIO ---

// Verificar permisos según la operación
if ($esEdicion) {
    if (!tienePermiso("controles.planta.turnos.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar turnos de planta"]);
        exit;
    }
} else {
    if (!tienePermiso("controles.planta.turnos.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear turnos de planta"]);
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
        // Verificar que el turno existe
        $turnoExistente = $conexion->selectOne("SELECT id FROM turnos_planta WHERE id = ?", [$id]);
        if (!$turnoExistente) {
            throw new Exception("El turno no existe");
        }

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM turnos_planta WHERE codigo = ? AND id != ?",
            [$codigo, $id]
        );
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un turno con ese código");
        }

        // Actualizar turno
        $datos = [
            "codigo" => $codigo,
            "nombre" => $nombre
        ];

        $resultado = $conexion->update("turnos_planta", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar el turno");
        }

        $mensaje = "Turno actualizado correctamente";
        $turnoId = $id;
    } else {
        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM turnos_planta WHERE codigo = ?", [$codigo]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un turno con ese código");
        }

        // Crear nuevo turno
        $datos = [
            "codigo" => $codigo,
            "nombre" => $nombre,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $turnoId = $conexion->insert("turnos_planta", $datos);

        if (!$turnoId) {
            throw new Exception("Error al crear el turno");
        }

        $mensaje = "Turno creado correctamente";
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => $mensaje,
        "id" => $turnoId
    ];
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al guardar el turno: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en guardar.php (turnos_planta): " . $e->getMessage());
}

// Devolver respuesta en formato JSON
header("Content-Type: application/json");
echo json_encode($response);
exit;