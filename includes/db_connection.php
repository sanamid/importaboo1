<?php
$db_host = 'localhost';
$db_name = 'tienda_online';
$db_user = 'root'; // Cambia por tu usuario de BD
$db_pass = '';     // Cambia por tu contraseña de BD

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener categorías para selects
$stmt_categorias_global = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
$categorias_global = $stmt_categorias_global->fetchAll();

// Obtener porcentajes globales
$stmt_config = $pdo->query("SELECT porcentaje_unitario, porcentaje_mayorista FROM configuracion_tienda WHERE id = 1");
$config_global = $stmt_config->fetch();
if (!$config_global) {
    // Insertar valores por defecto si no existen
    $pdo->query("INSERT INTO configuracion_tienda (id, porcentaje_unitario, porcentaje_mayorista) VALUES (1, 20.00, 10.00) ON DUPLICATE KEY UPDATE id=1");
    $config_global = ['porcentaje_unitario' => 20.00, 'porcentaje_mayorista' => 10.00];
}

function calcularPreciosVenta($costo_unitario, $costo_mayorista, $porcentaje_unitario, $porcentaje_mayorista) {
    $venta_unitario = $costo_unitario * (1 + ($porcentaje_unitario / 100));
    $venta_mayorista = $costo_mayorista * (1 + ($porcentaje_mayorista / 100));
    return [
        'venta_unitario' => round($venta_unitario, 2),
        'venta_mayorista' => round($venta_mayorista, 2)
    ];
}
?>