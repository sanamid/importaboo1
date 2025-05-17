document.addEventListener('DOMContentLoaded', () => {
    const cartCountElement = document.getElementById('cart-count');
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');
    const orderForm = document.getElementById('order-form');

    let cart = JSON.parse(localStorage.getItem('tiendaOnlineCart')) || [];

    function updateCartCount() {
        if (cartCountElement) {
            cartCountElement.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
        }
    }

    function saveCart() {
        localStorage.setItem('tiendaOnlineCart', JSON.stringify(cart));
        updateCartCount();
    }

    function addToCart(productId, productName, productPrice, productImage) {
        const existingItem = cart.find(item => item.id === productId);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({ id: productId, name: productName, price: parseFloat(productPrice), quantity: 1, image: productImage });
        }
        saveCart();
        alert(productName + ' añadido al carrito!');
    }

    if (addToCartButtons) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', () => {
                const card = button.closest('.product-card');
                const productId = card.dataset.productId;
                const productName = card.querySelector('h4').textContent;
                const productPrice = card.dataset.price; // Assuming price is in a data attribute
                const productImage = card.querySelector('img') ? card.querySelector('img').src : '';
                
                addToCart(productId, productName, productPrice, productImage);
            });
        });
    }

    function displayCartItems() {
        if (!cartItemsContainer || !cartTotalElement) return;
        cartItemsContainer.innerHTML = ''; 
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<tr><td colspan="5">Tu carrito está vacío.</td></tr>';
            cartTotalElement.textContent = 'Total: $0.00';
            return;
        }

        let total = 0;
        const table = document.createElement('table');
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Producto</th>
                    <th></th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;
        const tbody = table.querySelector('tbody');

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            const row = tbody.insertRow();
            row.innerHTML = `
                <td><img src="${item.image}" alt="${item.name}" class="cart-item-image"></td>
                <td>${item.name}</td>
                <td>$${item.price.toFixed(2)}</td>
                <td><input type="number" value="${item.quantity}" min="1" data-index="${index}" class="cart-item-quantity"></td>
                <td>$${itemTotal.toFixed(2)}</td>
                <td><button class="remove-from-cart-btn" data-index="${index}">Eliminar</button></td>
            `;
        });
        
        cartItemsContainer.appendChild(table);
        cartTotalElement.textContent = `Total: $${total.toFixed(2)}`;

        // Add event listeners for quantity changes and removal
        document.querySelectorAll('.cart-item-quantity').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const newQuantity = parseInt(e.target.value);
                if (newQuantity > 0) {
                    cart[index].quantity = newQuantity;
                    saveCart();
                    displayCartItems(); // Re-render cart
                } else {
                    // If quantity is 0 or less, revert to 1 or remove (here, just prevent bad values)
                    e.target.value = cart[index].quantity;
                }
            });
        });

        document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                cart.splice(index, 1);
                saveCart();
                displayCartItems(); // Re-render cart
            });
        });
    }

    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (cart.length === 0) {
                alert('Tu carrito está vacío. Añade productos antes de realizar un pedido.');
                return;
            }

            const nombre = document.getElementById('nombre_cliente').value.trim();
            const cedula = document.getElementById('cedula_cliente').value.trim();
            const direccion = document.getElementById('direccion_cliente').value.trim();
            const estado = document.getElementById('estado_cliente').value;
            const nota = document.getElementById('nota_cliente').value.trim();

            if (!nombre || !cedula || !direccion || !estado) {
                alert('Por favor, completa todos los campos obligatorios del cliente.');
                return;
            }

            let mensaje = "¡Hola! Quisiera hacer el siguiente pedido:\n\n";
            mensaje += "*PRODUCTOS:*\n";
            let totalPedido = 0;
            cart.forEach(item => {
                mensaje += `- ${item.name} (x${item.quantity}) - $${(item.price * item.quantity).toFixed(2)}\n`;
                totalPedido += item.price * item.quantity;
            });
            mensaje += `\n*TOTAL DEL PEDIDO: $${totalPedido.toFixed(2)}*\n\n`;
            mensaje += "*DATOS DEL CLIENTE:*\n";
            mensaje += `Nombre: ${nombre}\n`;
            mensaje += `Cédula: ${cedula}\n`;
            mensaje += `Dirección: ${direccion}\n`;
            mensaje += `Estado: ${estado}\n`;
            if (nota) {
                mensaje += `Nota Adicional: ${nota}\n`;
            }
            mensaje += "\n¡Gracias!";

            const telefono = "584241788222"; // Tu número de WhatsApp
            const urlWhatsApp = `https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}`;

            window.open(urlWhatsApp, '_blank');

            // Opcional: Limpiar carrito y formulario después de enviar
            // cart = [];
            // saveCart();
            // displayCartItems();
            // orderForm.reset();
            // alert('Serás redirigido a WhatsApp para enviar tu pedido. Si deseas, puedes limpiar tu carrito después.');
        });
    }

    // Initial calls
    updateCartCount();
    if (cartItemsContainer) { // Only run displayCartItems if on the cart page
        displayCartItems();
    }
});