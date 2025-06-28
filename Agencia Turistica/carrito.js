
function agregarAlCarrito(producto_id, nombre) {
    fetch('agregar_al_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `producto_id=${producto_id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${nombre} agregado al carrito`);
            actualizarContadorCarrito();
        } else {
            alert(data.message || 'Error al agregar al carrito');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function actualizarContadorCarrito() {
    fetch('obtener_contador_carrito.php')
    .then(response => response.json())
    .then(data => {
        const contador = document.getElementById('contador-carrito');
        if (contador) contador.textContent = data.count || '0';
    });
}


document.addEventListener('DOMContentLoaded', actualizarContadorCarrito);