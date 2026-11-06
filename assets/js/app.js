(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;

        // Menú lateral en móviles
        document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                body.classList.toggle('sidebar-open');
            });
        });

        document.querySelectorAll('[data-sidebar-close]').forEach(function (element) {
            element.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                body.classList.remove('sidebar-open');
            }
        });

        // Confirmación antes de eliminar
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const message = form.getAttribute('data-confirm') || '¿Deseas continuar?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        // Cerrar alertas
        document.querySelectorAll('.alert__close').forEach(function (button) {
            button.addEventListener('click', function () {
                const alert = button.closest('.alert');
                if (alert) {
                    alert.remove();
                }
            });
        });

        // Auto-ocultar mensajes de éxito o informativos
        document.querySelectorAll('.alert--success, .alert--info').forEach(function (alert) {
            window.setTimeout(function () {
                alert.classList.add('is-hiding');
                window.setTimeout(function () {
                    alert.remove();
                }, 300);
            }, 6000);
        });

        // Botón de impresión
        document.querySelectorAll('[data-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });

        // Texto dinámico de los switches
        document.querySelectorAll('.switch input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const text = checkbox.closest('.switch')?.querySelector('.switch__text');
                if (text) {
                    text.textContent = checkbox.checked ? 'Sí' : 'No';
                }
            });
        });
    });
})();
