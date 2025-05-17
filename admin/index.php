<?php
require_once '../includes/db_connection.php';

// Paginación
$productos_por_pagina = 25;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Contar total de productos
$total_productos_stmt = $pdo->query("SELECT COUNT(*) FROM productos");
$total_productos = $total_productos_stmt->fetchColumn();
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Obtener productos para la página actual
$stmt = $pdo->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.orden ASC, p.id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $productos_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll();

// Obtener categorías (ya están en $categorias_global de db_connection.php)
// Obtener porcentajes (ya están en $config_global de db_connection.php)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="container">
        <h1>Panel de Administración de Productos</h1>

        <div class="config-section">
            <h2>Configuración Global de Porcentajes de Venta</h2>
            <div>
                <label for="porcentaje_unitario_global">% Ganancia Unitario:</label>
                <input type="number" id="porcentaje_unitario_global" value="<?php echo htmlspecialchars($config_global['porcentaje_unitario']); ?>" step="0.01">
            </div>
            <div>
                <label for="porcentaje_mayorista_global">% Ganancia Mayorista:</label>
                <input type="number" id="porcentaje_mayorista_global" value="<?php echo htmlspecialchars($config_global['porcentaje_mayorista']); ?>" step="0.01">
            </div>
            <button id="guardar_porcentajes_globales">Guardar Porcentajes y Recalcular Precios</button>
        </div>
        
        <div class="upload-section">
            <h2>Subir Nuevas Imágenes Masivamente</h2>
            <form id="uploadForm" action="php_scripts/subir_imagenes.php" method="post" enctype="multipart/form-data">
                <label for="imagenes_nuevas">Seleccionar imágenes (se crearán productos básicos):</label>
                <input type="file" name="imagenes_nuevas[]" id="imagenes_nuevas" multiple accept="image/*">
                <button type="submit" id="upload_images_button">Subir Imágenes y Crear Productos</button>
            </form>
            <div id="uploadStatus"></div>
        </div>

        <h2>Listado de Productos</h2>
        <table>
            <thead>
                <tr>
                    <th>Producto (ID/Img)</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Costo Uni. ($)</th>
                    <th>Costo May. ($)</th>
                    <th>% Uni.</th>
                    <th>% May.</th>
                    <th>Venta Uni. ($)</th>
                    <th>Venta May. ($)</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php foreach ($productos as $producto): ?>
                <tr data-product-id="<?php echo $producto['id']; ?>">
                    <td>
                        ID: <?php echo $producto['id']; ?><br>
                        <?php if (!empty($producto['imagen_nombre'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($producto['imagen_nombre']); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="thumbnail-admin"
                                 data-fullimage="uploads/<?php echo htmlspecialchars($producto['imagen_nombre']); ?>">
                        <?php else: ?>
                            Sin imagen
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="display-field" data-field-display="nombre"><?php echo htmlspecialchars($producto['nombre']); ?></span>
                        <input type="text" class="editable-field" data-field="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    </td>
                    <td>
                        <span class="display-field" data-field-display="descripcion"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></span>
                        <textarea class="editable-field" data-field="descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </td>
                    <td>
                        <span class="display-field" data-field-display="categoria_id" data-value="<?php echo $producto['categoria_id']; ?>"><?php echo htmlspecialchars($producto['categoria_nombre'] ?: 'N/A'); ?></span>
                        <select class="editable-field" data-field="categoria_id">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias_global as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo ($producto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <span class="display-field" data-field-display="costo_unitario"><?php echo number_format($producto['costo_unitario'], 2); ?></span>
                        <input type="number" class="editable-field" data-field="costo_unitario" value="<?php echo $producto['costo_unitario']; ?>" step="0.01">
                    </td>
                     <td>
                        <span class="display-field" data-field-display="costo_mayorista"><?php echo number_format($producto['costo_mayorista'], 2); ?></span>
                        <input type="number" class="editable-field" data-field="costo_mayorista" value="<?php echo $producto['costo_mayorista']; ?>" step="0.01">
                    </td>
                    <td><?php echo htmlspecialchars($config_global['porcentaje_unitario']); ?>%</td>
                    <td><?php echo htmlspecialchars($config_global['porcentaje_mayorista']); ?>%</td>
                    <td>
                        <span data-field-display="venta_unitario_calculado"><?php echo number_format($producto['venta_unitario_calculado'], 2); ?></span>
                    </td>
                    <td>
                        <span data-field-display="venta_mayorista_calculado"><?php echo number_format($producto['venta_mayorista_calculado'], 2); ?></span>
                    </td>
                    <td>
                        <button class="stock-toggle <?php echo $producto['stock_disponible'] ? '' : 'not-available'; ?>" 
                                data-product-id="<?php echo $producto['id']; ?>" 
                                data-current-stock="<?php echo $producto['stock_disponible'] ? 'true' : 'false'; ?>">
                            <?php echo $producto['stock_disponible'] ? 'Disponible' : 'No Disponible'; ?>
                        </button>
                    </td>
                    <td class="actions">
                        <button class="edit-product icon-button"><i class="fas fa-edit"></i> Editar</button>
                        <button class="delete-product icon-button"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=<?php echo $pagina_actual - 1; ?>">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina_actual): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_actual + 1; ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close-modal">×</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script src="js/admin_scripts.js"></script>
    <script>
        // Script for image upload form
        const uploadForm = document.getElementById('uploadForm');
        const uploadStatus = document.getElementById('uploadStatus');

        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                uploadStatus.textContent = 'Subiendo imágenes y creando productos...';

                fetch('php_scripts/subir_imagenes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        uploadStatus.textContent = data.message + ' Refrescando página...';
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        uploadStatus.textContent = 'Error: ' + (data.message || 'Error desconocido.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    uploadStatus.textContent = 'Error de conexión al subir imágenes.';
                });
            });
        }
    </script>
</body>
</html>