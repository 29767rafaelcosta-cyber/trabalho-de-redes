/**
 * AutoPeças Pro - Frontend Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // Menu mobile toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Auto-ocultar mensagens flash após 5 segundos
    const flashAlerts = document.querySelectorAll('.flash-alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirmação para botões com a classe .confirm-action
    document.querySelectorAll('.confirm-action').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const msg = btn.getAttribute('data-confirm') || 'Tem a certeza que deseja realizar esta ação?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Interação de simulação de pagamentos no checkout
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.payment-instructions').forEach(el => el.classList.add('hidden'));
            const selectedInfo = document.getElementById('info-' + radio.value);
            if (selectedInfo) {
                selectedInfo.classList.remove('hidden');
            }
        });
    });
});

/**
 * Função para mostrar notificação rápida em toast
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-600' : (type === 'danger' ? 'bg-red-600' : 'bg-blue-600');
    toast.className = `fixed bottom-5 right-5 z-50 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center space-x-3 transition-transform duration-300 transform translate-y-10 ${bgColor}`;
    toast.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span class="text-sm font-medium">${message}</span>
    `;
    document.body.appendChild(toast);
    
    // Animar entrada
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-10');
        toast.classList.add('translate-y-0');
    });

    // Remover após 3 segundos
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-4');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

