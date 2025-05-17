<?php
require_once '../../includes/db_connection.php';
header('Content-Type: application/json'); // Asegurar que la respuesta sea JSON

$response = ['success' => false, 'message' => 'No se subieron archivos.', 'productos_creados' => []];
$upload_dir = '../uploads/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_FILES['imagenes_nuevas'])) {
    $files = $_FILES['imagenes_nuevas'];
    $count_files = count($files['name']);
    $productos_creados_temp = [];

    if ($count_files == 0 || empty($files['name'][0])) {
        echo json_encode($response);
        exit;
    }

    $default_nombre = "Nuevo Producto";
    $default_descripcion = "Descripción pendiente.";
    $default_categoria_id = null; // O un ID de categoría por defecto
    $default_costo_unitario = 0.00;
    $default_costo_mayorista = 0.00;
    $default_stock = 1;

    // Obtener el ID de la primera categoría (ej. "otros") como default si no hay una mejor opción
    $stmt_cat_default = $pdo->query("SELECT id FROM categorias ORDER BY id ASC LIMIT 1");
    $cat_default_row = $stmt_cat_default->fetch();
    if ($cat_default_row) {
        $default_categoria_id = $cat_default_row['id'];
    }


    $orden = 0; // Para el orden de subida
    // Obtener el máximo orden actual para continuar la secuencia
    $stmt_max_orden = $pdo->query("SELECT MAX(orden) as max_orden FROM productos");
    $max_orden_row = $stmt_max_orden->fetch();
    if ($max_orden_row && $max_orden_row['max_orden'] !== null) {
        $orden = $max_orden_row['max_orden'] + 1;
    }


    try {
        $pdo->beginTransaction();

        $stmt_insert = $pdo->prepare("INSERT INTO productos 
            (imagen_nombre, nombre, descripcion, categoria_id, costo_unitario, costo_mayorista, venta_unitario_calculado, venta_mayorista_calculado, stock_disponible, orden) 
            VALUES (:imagen_nombre, :nombre, :descripcion, :categoria_id, :costo_unitario, :costo_mayorista, :venta_unitario_calculado, :venta_mayorista_calculado, :stock_disponible, :orden)");

        for ($i = 0; $i < $count_files; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $original_name = basename($files['name'][$i]);
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    $response['message'] = "Archivo '$original_name' tiene una extensión no permitida.";
                    $pdo->rollBack();
                    echo json_encode($response);
                    exit;
                }
                
                $safe_filename = uniqid('prod_') . '.' . $file_extension;
                $destination = $upload_dir . $safe_filename;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $nombre_producto_inicial = pathinfo($original_name, PATHINFO_FILENAME); // Nombre sin extensión
                    
                    $preciosVenta = calcularPreciosVenta(
                        $default_costo_unitario,
                        $default_costo_mayorista,
                        $config_global['porcentaje_unitario'],
                        $config_global['porcentaje_mayorista']
                    );

                    $params = [
                        ':imagen_nombre' => $safe_filename,
                        ':nombre' => $nombre_producto_inicial ?: $default_nombre,
                        ':descripcion' => $default_descripcion,
                        ':categoria_id' => $default_categoria_id,
                        ':costo_unitario' => $default_costo_unitario,
                        ':costo_mayorista' => $default_costo_mayorista,
                        ':venta_unitario_calculado' => $preciosVenta['venta_unitario'],
                        ':venta_mayorista_calculado' => $preciosVenta['venta_mayorista'],
                        ':stock_disponible' => $default_stock,
                        ':orden' => $orden + $i 
                    ];
                    
                    $stmt_insert->execute($params);
                    $productos_creados_temp[] = ['id' => $pdo->lastInsertId(), 'nombre_imagen' => $safe_filename, 'nombre_inicial' => $params[':nombre']];
                } else {
                    $response['message'] = "Error al mover el archivo '$original_name'.";
                    $pdo->rollBack();
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['message'] = "Error al subir el archivo '" . $files['name'][$i] . "': " . $files['error'][$i];
                $pdo->rollBack();
                echo json_encode($response);
                exit;
            }
        }
        
        $pdo->commit();
        $response['success'] = true;
        $response['message'] = count($productos_creados_temp) . ' producto(s) creado(s) exitosamente con las imágenes subidas.';
        $response['productos_creados'] = $productos_creados_temp;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>