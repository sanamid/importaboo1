<?php
require_once '../../includes/db_connection.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Datos inválidos.'];

if (isset($_POST['porcentaje_unitario']) && isset($_POST['porcentaje_mayorista'])) {
    $porcentaje_unitario = filter_input(INPUT_POST, 'porcentaje_unitario', FILTER_VALIDATE_FLOAT);
    $porcentaje_mayorista = filter_input(INPUT_POST, 'porcentaje_mayorista', FILTER_VALIDATE_FLOAT);

    if ($porcentaje_unitario === false || $porcentaje_mayorista === false || $porcentaje_unitario < 0 || $porcentaje_mayorista < 0) {
        $response['message'] = 'Valores de porcentaje inválidos.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Actualizar configuración
        $stmt_config = $pdo->prepare("UPDATE configuracion_tienda SET porcentaje_unitario = ?, porcentaje_mayorista = ? WHERE id = 1");
        $stmt_config->execute([$porcentaje_unitario, $porcentaje_mayorista]);

        // Recalcular precios para todos los productos
        $stmt_productos = $pdo->query("SELECT id, costo_unitario, costo_mayorista FROM productos");
        $productos = $stmt_productos->fetchAll();

        $stmt_update_precio = $pdo->prepare("UPDATE productos SET venta_unitario_calculado = ?, venta_mayorista_calculado = ? WHERE id = ?");

        foreach ($productos as $producto) {
            $preciosVenta = calcularPreciosVenta(
                $producto['costo_unitario'],
                $producto['costo_mayorista'],
                $porcentaje_unitario,
                $porcentaje_mayorista
            );
            $stmt_update_precio->execute([
                $preciosVenta['venta_unitario'],
                $preciosVenta['venta_mayorista'],
                $producto['id']
            ]);
        }

        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Porcentajes guardados y precios recalculados.';

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>