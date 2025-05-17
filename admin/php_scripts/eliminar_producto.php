<?php
require_once '../../includes/db_connection.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'ID de producto no proporcionado.'];

if (isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id === false) {
        $response['message'] = 'ID de producto inválido.';
        echo json_encode($response);
        exit;
    }

    try {
        // Opcional: eliminar la imagen del servidor
        // $stmt_img = $pdo->prepare("SELECT imagen_nombre FROM productos WHERE id = ?");
        // $stmt_img->execute([$id]);
        // $img_data = $stmt_img->fetch();
        // if ($img_data && !empty($img_data['imagen_nombre'])) {
        //     $image_path = '../uploads/' . $img_data['imagen_nombre'];
        //     if (file_exists($image_path)) {
        //         unlink($image_path);
        //     }
        // }

        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            $response['success'] = true;
            $response['message'] = 'Producto eliminado correctamente.';
        } else {
            $response['message'] = 'Producto no encontrado o ya eliminado.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>