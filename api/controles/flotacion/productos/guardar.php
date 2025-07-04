<?php
// Incluir archivos necesarios
require_once "../../../../db/funciones.php";
require_once "../../../../db/conexion.php";

// Configurar headers para JSON
header("Content-Type: application/json");

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
    if (!tienePermiso("controles.flotacion.productos.editar")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para editar productos de flotación"]);
        exit;
    }
} else {
    if (!tienePermiso("controles.flotacion.productos.crear")) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tiene permisos para crear productos de flotación"]);
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
        // Verificar que el producto existe
        $productoExistente = $conexion->selectOne("SELECT id FROM productos_flotacion WHERE id = ?", [$id]);
        if (!$productoExistente) {
            throw new Exception("El producto no existe");
        }

        // Verificar que el código no esté duplicado (excluyendo el registro actual)
        $codigoDuplicado = $conexion->selectOne(
            "SELECT id FROM productos_flotacion WHERE codigo = ? AND id != ?",
            [$codigo, $id]
        );
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un producto con ese código");
        }

        // Actualizar producto
        $datos = [
            "codigo" => $codigo,
            "nombre" => $nombre
        ];

        $resultado = $conexion->update("productos_flotacion", $datos, "id = ?", [$id]);

        if (!$resultado) {
            throw new Exception("Error al actualizar el producto");
        }

        $mensaje = "Producto actualizado correctamente";
        $productoId = $id;
    } else {
        // Verificar que el código no esté duplicado
        $codigoDuplicado = $conexion->selectOne("SELECT id FROM productos_flotacion WHERE codigo = ?", [$codigo]);
        if ($codigoDuplicado) {
            throw new Exception("Ya existe un producto con ese código");
        }

        // Crear nuevo producto
        $datos = [
            "codigo" => $codigo,
            "nombre" => $nombre,
            "creado_en" => date("Y-m-d H:i:s")
        ];

        $productoId = $conexion->insert("productos_flotacion", $datos);

        if (!$productoId) {
            throw new Exception("Error al crear el producto");
        }

        $mensaje = "Producto creado correctamente";
    }

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    $response = [
        "success" => true,
        "message" => $mensaje,
        "id" => $productoId
    ];

    echo json_encode($response);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Preparar respuesta de error
    $response = [
        "success" => false,
        "message" => "Error al guardar el producto: " . $e->getMessage()
    ];

    // Registrar error en log
    error_log("Error en guardar.php (productos_flotacion): " . $e->getMessage());

    echo json_encode($response);
}
