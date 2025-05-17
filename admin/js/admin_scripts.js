document.addEventListener('DOMContentLoaded', function() {
    const productTableBody = document.getElementById('productTableBody');
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const closeModal = document.querySelector('.close-modal');

    // Global percentage inputs
    const porcentajeUnitarioGlobalInput = document.getElementById('porcentaje_unitario_global');
    const porcentajeMayoristaGlobalInput = document.getElementById('porcentaje_mayorista_global');
    const guardarPorcentajesBtn = document.getElementById('guardar_porcentajes_globales');

    if (guardarPorcentajesBtn) {
        guardarPorcentajesBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('porcentaje_unitario', porcentajeUnitarioGlobalInput.value);
            formData.append('porcentaje_mayorista', porcentajeMayoristaGlobalInput.value);

            fetch('php_scripts/guardar_porcentajes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Porcentajes guardados y precios recalculados.');
                    location.reload(); // Reload to see changes
                } else {
                    alert('Error al guardar porcentajes: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al guardar porcentajes.');
            });
        });
    }
    
    if (productTableBody) {
        productTableBody.addEventListener('click', function(event) {
            const target = event.target;
            const row = target.closest('tr');
            if (!row) return;
            const productId = row.dataset.productId;

            // Edit product
            if (target.classList.contains('edit-product') || target.closest('.edit-product')) {
                toggleEditMode(row, true);
            }

            // Save product (inline)
            if (target.classList.contains('save-row') || target.closest('.save-row')) {
                saveRowChanges(row, productId);
            }
            
            // Cancel edit
            if (target.classList.contains('cancel-edit') || target.closest('.cancel-edit')) {
                toggleEditMode(row, false); // Revert to display mode might need fetching original data or storing it
                location.reload(); // Simple revert: reload page
            }

            // Delete product
            if (target.classList.contains('delete-product') || target.closest('.delete-product')) {
                if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                    deleteProduct(productId, row);
                }
            }

            // Toggle stock
            if (target.classList.contains('stock-toggle')) {
                toggleStock(productId, target);
            }
            
            // Open image modal
            if (target.classList.contains('thumbnail-admin')) {
                modalImage.src = target.dataset.fullimage;
                imageModal.style.display = 'block';
            }
        });
    }

    if (closeModal) {
        closeModal.onclick = function() {
            imageModal.style.display = "none";
        }
    }
    
    window.onclick = function(event) {
        if (event.target == imageModal) {
            imageModal.style.display = "none";
        }
    }

    function toggleEditMode(row, isEditing) {
        const displayFields = row.querySelectorAll('.display-field');
        const editableFields = row.querySelectorAll('.editable-field');
        const editButton = row.querySelector('.edit-product');
        const deleteButton = row.querySelector('.delete-product');
        let saveButton = row.querySelector('.save-row');
        let cancelButton = row.querySelector('.cancel-edit');

        displayFields.forEach(df => df.style.display = isEditing ? 'none' : 'inline-block');
        editableFields.forEach(ef => {
            ef.style.display = isEditing ? 'inline-block' : 'none';
            if (isEditing) {
                const displayField = row.querySelector(`[data-field-display="${ef.dataset.field}"]`);
                if (ef.tagName === 'SELECT') {
                    ef.value = displayField.dataset.value || displayField.textContent;
                } else {
                     ef.value = displayField.textContent;
                }
            }
        });

        if (isEditing) {
            if (editButton) editButton.style.display = 'none';
            if (deleteButton) deleteButton.style.display = 'none';

            if (!saveButton) {
                saveButton = document.createElement('button');
                saveButton.innerHTML = '<i class="fas fa-save"></i> Guardar';
                saveButton.className = 'save-row icon-button';
                row.querySelector('.actions').appendChild(saveButton);
            }
            if (!cancelButton) {
                cancelButton = document.createElement('button');
                cancelButton.innerHTML = '<i class="fas fa-times"></i> Cancelar';
                cancelButton.className = 'cancel-edit icon-button';
                row.querySelector('.actions').appendChild(cancelButton);
            }
            saveButton.style.display = 'inline-block';
            cancelButton.style.display = 'inline-block';
        } else {
            if (editButton) editButton.style.display = 'inline-block';
            if (deleteButton) deleteButton.style.display = 'inline-block';
            if (saveButton) saveButton.style.display = 'none';
            if (cancelButton) cancelButton.style.display = 'none';
        }
    }

    function saveRowChanges(row, productId) {
        const formData = new FormData();
        formData.append('id', productId);
        
        let hasError = false;

        const nombreInput = row.querySelector('input[data-field="nombre"]');
        const descripcionInput = row.querySelector('textarea[data-field="descripcion"]');
        const categoriaSelect = row.querySelector('select[data-field="categoria_id"]');
        const costoUnitarioInput = row.querySelector('input[data-field="costo_unitario"]');
        const costoMayoristaInput = row.querySelector('input[data-field="costo_mayorista"]');

        if (!nombreInput.value.trim()) {
            alert('El nombre no puede estar vacío.');
            nombreInput.focus();
            hasError = true;
        }
        if (isNaN(parseFloat(costoUnitarioInput.value)) || parseFloat(costoUnitarioInput.value) < 0) {
            alert('Costo Unitario inválido.');
            costoUnitarioInput.focus();
            hasError = true;
        }
         if (isNaN(parseFloat(costoMayoristaInput.value)) || parseFloat(costoMayoristaInput.value) < 0) {
            alert('Costo Mayorista inválido.');
            costoMayoristaInput.focus();
            hasError = true;
        }

        if(hasError) return;

        formData.append('nombre', nombreInput.value);
        formData.append('descripcion', descripcionInput.value);
        formData.append('categoria_id', categoriaSelect.value);
        formData.append('costo_unitario', costoUnitarioInput.value);
        formData.append('costo_mayorista', costoMayoristaInput.value);
        
        // For image, we are not changing it in inline edit for simplicity here
        // formData.append('imagen_nombre', row.querySelector('.thumbnail-admin').alt); 

        fetch('php_scripts/actualizar_producto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Producto actualizado');
                // Update display fields with new data from server
                row.querySelector('[data-field-display="nombre"]').textContent = data.producto.nombre;
                row.querySelector('[data-field-display="descripcion"]').textContent = data.producto.descripcion;
                
                const categoriaDisplay = row.querySelector('[data-field-display="categoria_id"]');
                const selectedOption = categoriaSelect.options[categoriaSelect.selectedIndex];
                categoriaDisplay.textContent = selectedOption ? selectedOption.text : data.producto.categoria_nombre;
                categoriaDisplay.dataset.value = data.producto.categoria_id;

                row.querySelector('[data-field-display="costo_unitario"]').textContent = parseFloat(data.producto.costo_unitario).toFixed(2);
                row.querySelector('[data-field-display="costo_mayorista"]').textContent = parseFloat(data.producto.costo_mayorista).toFixed(2);
                row.querySelector('[data-field-display="venta_unitario_calculado"]').textContent = parseFloat(data.producto.venta_unitario_calculado).toFixed(2);
                row.querySelector('[data-field-display="venta_mayorista_calculado"]').textContent = parseFloat(data.producto.venta_mayorista_calculado).toFixed(2);
                
                toggleEditMode(row, false);
            } else {
                alert('Error al actualizar: ' + (data.message || 'Error desconocido.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al actualizar.');
        });
    }

    function deleteProduct(productId, row) {
        const formData = new FormData();
        formData.append('id', productId);

        fetch('php_scripts/eliminar_producto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Producto eliminado');
                row.remove();
            } else {
                alert('Error al eliminar: ' + (data.message || 'Error desconocido.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al eliminar.');
        });
    }

    function toggleStock(productId, button) {
        const currentStockState = button.dataset.currentStock === 'true';
        const newStockState = !currentStockState;

        const formData = new FormData();
        formData.append('id', productId);
        formData.append('stock_disponible', newStockState ? 1 : 0);

        fetch('php_scripts/actualizar_producto.php', { // Can reuse a more general update script
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.dataset.currentStock = newStockState.toString();
                button.textContent = newStockState ? 'Disponible' : 'No Disponible';
                button.classList.toggle('not-available', !newStockState);
                alert('Stock actualizado.');
            } else {
                alert('Error al actualizar stock: ' + (data.message || 'Error desconocido.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al actualizar stock.');
        });
    }
});