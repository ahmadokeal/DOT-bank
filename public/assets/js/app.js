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

    const navigation = document.querySelector('#primary-navigation');
    const navToggle = document.querySelector('.nav-toggle');
    if (navigation && navToggle) {
        const closeNavigation = () => {
            navigation.classList.remove('nav-open');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.setAttribute('aria-label', 'Open navigation');
        };

        navToggle.addEventListener('click', () => {
            const isOpen = navigation.classList.toggle('nav-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            navToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
        });

        navigation.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', closeNavigation));
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) closeNavigation();
        });
    }
});
