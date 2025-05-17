<?php
require_once 'includes/header.php'; // Incluye db_connection

$producto = null;
if (isset($_GET['id'])) {
    $producto_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($producto_id) {
        $stmt = $pdo->prepare("SELECT p.*, c.nombre as categoria_nombre 
                               FROM productos p 
                               LEFT JOIN categorias c ON p.categoria_id = c.id 
                               WHERE p.id = ? AND p.stock_disponible = 1");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();
    }
}

if (!$producto) {
    echo "<h2>Producto no encontrado o no disponible.</h2>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="product-detail-container" style="display:flex; gap: 20px; margin-top:20px;">
    <div class="product-detail-image" style="flex:1;">
        <?php if (!empty($producto['imagen_nombre'])): ?>
            <img src="admin/uploads/<?php echo htmlspecialchars($producto['imagen_nombre']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="max-width:100%; border-radius: 5px; border:1px solid #444;">
        <?php else: ?>
            <img src="img/placeholder.png" alt="Sin imagen" style="max-width:100%; border-radius: 5px;">
        <?php endif; ?>
    </div>
    <div class="product-detail-info" style="flex:1;">
        <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
        <p><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['categoria_nombre'] ?: 'N/A'); ?></p>
        
        <p class="price" style="font-size: 1.5em; color:var(--color-acento); margin: 15px 0;">
            Precio Unitario: $<?php echo number_format($producto['venta_unitario_calculado'], 2); ?>
        </p>
        <?php if ($producto['costo_mayorista'] > 0 && $producto['venta_mayorista_calculado'] > 0 && $producto['venta_mayorista_calculado'] < $producto['venta_unitario_calculado']): ?>
            <p class="price-mayorista" style="font-size: 1.1em; color: #ccc; margin-bottom: 15px;">
                Precio Mayorista: $<?php echo number_format($producto['venta_mayorista_calculado'], 2); ?>
            </p>
        <?php endif; ?>

        <p><strong>Descripción:</strong></p>
        <div style="background-color: #2a2a2a; padding:10px; border-radius:4px; margin-bottom:20px;">
            <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
        </div>

        <?php if ($producto['stock_disponible']): ?>
            <div class="product-card" data-product-id="<?php echo $producto['id']; ?>" data-price="<?php echo htmlspecialchars($producto['venta_unitario_calculado']); ?>" style="background:none; border:none; padding:0; text-align:left;">
                 <!-- El h4 no es necesario aquí ya que el título está arriba -->
                 <button class="add-to-cart-btn" style="padding: 12px 25px; font-size:1.1em;">Añadir al Carrito</button>
            </div>
        <?php else: ?>
            <p class="out-of-stock" style="font-size:1.2em; color: #ff6b6b;">Producto Actualmente No Disponible</p>
        <?php endif; ?>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>