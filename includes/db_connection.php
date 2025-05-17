<?php
$db_host = 'sql300.infinityfree.com'; // De "MYSQL HOSTNAME"
$db_name = 'if0_39003381_importaboot'; // De "DATABASE NAME" en la lista
$db_user = 'if0_39003381';         // De "MYSQL USERNAME"
$db_pass = 'T0CZFSeQj29aaB';         // De "MYSQL PASSWORD"
// $db_port = 3306; // El puerto 3306 es el estándar para MySQL y usualmente no es necesario especificarlo en el DSN si es este.

try {
    // El DSN (Data Source Name) para PDO
    // Si la conexión falla sin el puerto, puedes intentar añadirlo:
    // $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Para que PDO lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Para que los resultados se devuelvan como arrays asociativos
} catch (PDOException $e) {
    // En un entorno de producción, podrías querer registrar este error en un archivo en lugar de mostrarlo.
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// El resto del código de db_connection.php sigue igual,
// asumiendo que las tablas 'categorias' y 'configuracion_tienda'
// han sido creadas en la base de datos 'if0_39003381_importaboot'.

// Obtener categorías para selects
$categorias_global = []; // Inicializar para evitar errores si la consulta falla o no devuelve nada
try {
    $stmt_categorias_global = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    if ($stmt_categorias_global) {
        $categorias_global = $stmt_categorias_global->fetchAll();
    }
} catch (PDOException $e) {
    // Podrías registrar este error específico si es necesario
    // error_log("Error al obtener categorías: " . $e->getMessage());
    // $categorias_global permanecerá vacío, lo cual podría ser manejado en el frontend/backend.
}


// Obtener porcentajes globales
// Establecer valores por defecto en caso de que la tabla o la fila no existan
$config_global = ['porcentaje_unitario' => 20.00, 'porcentaje_mayorista' => 10.00]; 
try {
    $stmt_config = $pdo->query("SELECT porcentaje_unitario, porcentaje_mayorista FROM configuracion_tienda WHERE id = 1");
    if ($stmt_config) {
        $fetched_config = $stmt_config->fetch();
        if ($fetched_config) {
            $config_global = $fetched_config;
        } else {
            // Si la fila no existe, intentamos insertarla con valores por defecto
            // Esto es útil si es la primera vez que se ejecuta y la tabla está vacía pero existe.
            try {
                $pdo->query("INSERT INTO configuracion_tienda (id, porcentaje_unitario, porcentaje_mayorista) VALUES (1, 20.00, 10.00) ON DUPLICATE KEY UPDATE id=1");
                // No es necesario re-fetch, $config_global ya tiene los defaults.
            } catch (PDOException $insert_e) {
                // error_log("Error al insertar configuración por defecto: " . $insert_e->getMessage());
            }
        }
    }
} catch (PDOException $e) {
    // error_log("Error al obtener configuración de la tienda: " . $e->getMessage());
    // $config_global mantendrá los valores por defecto definidos arriba.
}


function calcularPreciosVenta($costo_unitario, $costo_mayorista, $porcentaje_unitario, $porcentaje_mayorista) {
    $venta_unitario = floatval($costo_unitario) * (1 + (floatval($porcentaje_unitario) / 100));
    $venta_mayorista = floatval($costo_mayorista) * (1 + (floatval($porcentaje_mayorista) / 100));
    return [
        'venta_unitario' => round($venta_unitario, 2),
        'venta_mayorista' => round($venta_mayorista, 2)
    ];
}
?>
