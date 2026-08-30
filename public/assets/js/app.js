/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Minimal Client-side Helpers
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss alerts or provide click-to-dismiss
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.addEventListener('click', (e) => {
            if (e.target.classList.contains('alert-close') || alert.dataset.dismissable === 'true') {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 200);
            }
        });
    });
});
