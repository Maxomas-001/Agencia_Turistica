document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toast-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.remove();
        });
    });
    
    setTimeout(() => {
        document.querySelectorAll('.toast').forEach(toast => {
            toast.style.animation = 'fadeOut 0.5s forwards';
            setTimeout(() => toast.remove(), 500);
        });
    }, 5000);
    
    const btnCancelar = document.getElementById('btn-cancelar-pedido');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Estás seguro que deseas cancelar este pedido?')) {
                window.location.href = `cancelar_pedido.php?id=${pedidoId}`;
            }
        });
    }
    
    const btnEntregado = document.getElementById('btn-marcar-entregado');
    if (btnEntregado) {
        btnEntregado.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Marcar este pedido como entregado?')) {
                document.querySelector('select[name="nuevo_estado"]').value = 3; // 3 = entregado
                document.querySelector('button[name="cambiar_estado"]').click();
            }
        });
    }
    
    const searchInput = document.getElementById('search-users');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.admin-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

document.querySelectorAll('.stat-card').forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
    card.classList.add('fade-in');
});

function checkSession() {
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(error => console.error('Error:', error));
}

setInterval(checkSession, 300000);

document.addEventListener('DOMContentLoaded', checkSession);