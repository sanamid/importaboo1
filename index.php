<?php
require_once 'includes/header.php'; // Incluye db_connection y categorías

// Obtener algunos productos para mostrar en la página de inicio (ej. los más recientes o aleatorios)
$stmt_productos_inicio = $pdo->query("SELECT p.*, c.nombre as categoria_nombre 
                                     FROM productos p 
                                     LEFT JOIN categorias c ON p.categoria_id = c.id 
                                     WHERE p.stock_disponible = 1 
                                     ORDER BY p.id DESC LIMIT 12"); // Muestra 12 productos
$productos_inicio = $stmt_productos_inicio->fetchAll();
?>

<h2>Productos Destacados</h2>
<div class="product-grid">
    <?php if (count($productos_inicio) > 0): ?>
        <?php foreach ($productos_inicio as $producto): ?>
            <div class="product-card" 
                 data-product-id="<?php echo $producto['id']; ?>"
                 data-price="<?php echo htmlspecialchars($producto['venta_unitario_calculado']); ?>">
                <a href="producto.php?id=<?php echo $producto['id']; ?>"> <!-- Enlace a página de producto individual -->
                    <?php if (!empty($producto['imagen_nombre'])): ?>
                        <img src="admin/uploads/<?php echo htmlspecialchars($producto['imagen_nombre']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <?php else: ?>
                        <img src="img/placeholder.png" alt="Sin imagen"> <!-- Imagen placeholder -->
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
        <p>No hay productos destacados disponibles en este momento.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>