<?php
require_once '../../includes/db_connection.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Datos inválidos.'];

if (isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id === false) {
        $response['message'] = 'ID de producto inválido.';
        echo json_encode($response);
        exit;
    }
    
    // Fetch existing product to only update provided fields
    $stmt_current = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt_current->execute([$id]);
    $current_product = $stmt_current->fetch();

    if (!$current_product) {
        $response['message'] = 'Producto no encontrado.';
        echo json_encode($response);
        exit;
    }

    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : $current_product['nombre'];
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : $current_product['descripcion'];
    $categoria_id = isset($_POST['categoria_id']) ? filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT) : $current_product['categoria_id'];
    $costo_unitario = isset($_POST['costo_unitario']) ? filter_input(INPUT_POST, 'costo_unitario', FILTER_VALIDATE_FLOAT) : $current_product['costo_unitario'];
    $costo_mayorista = isset($_POST['costo_mayorista']) ? filter_input(INPUT_POST, 'costo_mayorista', FILTER_VALIDATE_FLOAT) : $current_product['costo_mayorista'];
    $stock_disponible = isset($_POST['stock_disponible']) ? filter_input(INPUT_POST, 'stock_disponible', FILTER_VALIDATE_INT) : $current_product['stock_disponible']; // 0 or 1

    if (empty($nombre)) {
        $response['message'] = 'El nombre es obligatorio.';
        echo json_encode($response);
        exit;
    }
    if ($costo_unitario === false || $costo_unitario < 0) $costo_unitario = $current_product['costo_unitario'];
    if ($costo_mayorista === false || $costo_mayorista < 0) $costo_mayorista = $current_product['costo_mayorista'];
    if ($categoria_id === false) $categoria_id = $current_product['categoria_id'];
    if ($stock_disponible === false || !in_array($stock_disponible, [0,1])) $stock_disponible = $current_product['stock_disponible'];


    $preciosVenta = calcularPreciosVenta(
        $costo_unitario,
        $costo_mayorista,
        $config_global['porcentaje_unitario'],
        $config_global['porcentaje_mayorista']
    );
    $venta_unitario_calculado = $preciosVenta['venta_unitario'];
    $venta_mayorista_calculado = $preciosVenta['venta_mayorista'];

    try {
        $sql = "UPDATE productos SET 
                nombre = :nombre, 
                descripcion = :descripcion, 
                categoria_id = :categoria_id, 
                costo_unitario = :costo_unitario, 
                costo_mayorista = :costo_mayorista, 
                venta_unitario_calculado = :venta_unitario_calculado, 
                venta_mayorista_calculado = :venta_mayorista_calculado, 
                stock_disponible = :stock_disponible 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':categoria_id' => $categoria_id,
            ':costo_unitario' => $costo_unitario,
            ':costo_mayorista' => $costo_mayorista,
            ':venta_unitario_calculado' => $venta_unitario_calculado,
            ':venta_mayorista_calculado' => $venta_mayorista_calculado,
            ':stock_disponible' => $stock_disponible,
            ':id' => $id
        ]);

        if ($stmt->rowCount()) {
            $response['success'] = true;
            $response['message'] = 'Producto actualizado correctamente.';
            // Return updated product data
            $stmt_updated = $pdo->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ?");
            $stmt_updated->execute([$id]);
            $response['producto'] = $stmt_updated->fetch();
        } else {
            $response['message'] = 'No se realizaron cambios o error al actualizar.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>