<?php
require_once 'includes/header.php'; // Incluye db_connection y categorías

$categoria_id_actual = null;
$nombre_categoria_actual = "Todos los Productos";

if (isset($_GET['categoria_id'])) {
    $categoria_id_actual = filter_input(INPUT_GET, 'categoria_id', FILTER_VALIDATE_INT);
    if ($categoria_id_actual) {
        $stmt_cat_info = $pdo->prepare("SELECT nombre FROM categorias WHERE id = ?");
        $stmt_cat_info->execute([$categoria_id_actual]);
        $cat_info = $stmt_cat_info->fetch();
        if ($cat_info) {
            $nombre_categoria_actual = htmlspecialchars($cat_info['nombre']);
        } else {
            $categoria_id_actual = null; // Categoría no válida
            $nombre_categoria_actual = "Categoría no encontrada";
        }
    }
}

$sql_productos = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.stock_disponible = 1";
$params = [];

if ($categoria_id_actual) {
    $sql_productos .= " AND p.categoria_id = :categoria_id";
    $params[':categoria_id'] = $categoria_id_actual;
}
$sql_productos .= " ORDER BY p.nombre ASC";

$stmt_productos_cat = $pdo->prepare($sql_productos);
$stmt_productos_cat->execute($params);
$productos_categoria = $stmt_productos_cat->fetchAll();

// Mapeo para mostrar nombres amigables en el título si es necesario
$menu_items_display = [
    'lenceria' => 'Lencería',
    'juguetes' => 'Juguetes',
    'articulos varios' => 'Accesorios',
    'otros' => 'Otros'
];
$display_title = $nombre_categoria_actual;
if (isset($menu_items_display[strtolower($nombre_categoria_actual)])){
    $display_title = $menu_items_display[strtolower($nombre_categoria_actual)];
}


?>

<h2><?php echo ucfirst($display_title); ?></h2>
<div class="product-grid">
    <?php if (count($productos_categoria) > 0): ?>
        <?php foreach ($productos_categoria as $producto): ?>
            <div class="product-card" 
                 data-product-id="<?php echo $producto['id']; ?>"
                 data-price="<?php echo htmlspecialchars($producto['venta_unitario_calculado']); ?>">
                <a href="producto.php?id=<?php echo $producto['id']; ?>">
                    <?php if (!empty($producto['imagen_nombre'])): ?>
                        <img src="admin/uploads/<?php echo htmlspecialchars($producto['imagen_nombre']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <?php else: ?>
                        <img src="img/placeholder.png" alt="Sin imagen">
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                </a>
                <p class="price">$<?php echo number_format($producto['venta_unitario_calculado'], 2); ?></p>
                 <?php if ($producto['costo_mayorista'] > 0 && $producto['venta_mayorista_calculado'] > 0 && $producto['venta_mayorista_calculado'] < $producto['venta_unitario_calculado']): ?>
                    <p class="price-mayorista">Mayorista: $<?php echo number_format($producto['venta_mayorista_calculado'], 2); ?></p>
                <?php endif; ?>

                <?php if ($producto['stock_disponible']): ?>
                    <button class="add-to-cart-btn">Añadir al Carrito</button>
                <?php else: ?>
                    <p class="out-of-stock">No Disponible</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay productos disponibles en esta categoría en este momento.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>