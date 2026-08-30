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

    // Close the responsive navigation after selecting a destination.
    const navigation = document.querySelector('#primary-navigation');
    if (navigation) {
        const toggle = document.querySelector('.nav-toggle');
        navigation.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.matchMedia('(max-width: 768px)').matches) {
                    if (window.bootstrap) {
                        bootstrap.Collapse.getOrCreateInstance(navigation).hide();
                    } else {
                        navigation.classList.remove('show');
                        toggle?.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });
        if (toggle && !window.bootstrap) {
            toggle.addEventListener('click', () => {
                const open = navigation.classList.toggle('show');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }
    }
});
