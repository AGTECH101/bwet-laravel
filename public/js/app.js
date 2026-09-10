// Auto-remove notifications after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('#notification-container > div, .fixed.top-4.right-4');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    });
});

// HTMX configuration
document.addEventListener('htmx:beforeRequest', function(e) {
    const target = e.target;
    if (target.tagName === 'FORM' || target.hasAttribute('hx-post') || target.hasAttribute('hx-get')) {
        const button = target.tagName === 'BUTTON' ? target : target.querySelector('button[type="submit"]');
        if (button && !button.querySelector('.spinner')) {
            const originalText = button.innerHTML;
            button.innerHTML = `
                <div class="spinner inline-block mr-2"></div>
                <span>${button.textContent}</span>
            `;
            button.setAttribute('data-original-html', originalText);
            button.disabled = true;
        }
    }
});

document.addEventListener('htmx:afterRequest', function(e) {
    const button = e.target.tagName === 'BUTTON' ? e.target : e.target.querySelector('button[type="submit"]');
    if (button && button.hasAttribute('data-original-html')) {
        setTimeout(() => {
            button.innerHTML = button.getAttribute('data-original-html');
            button.disabled = false;
            button.removeAttribute('data-original-html');
        }, 300);
    }
});

// Initialize Bootstrap tooltips (if used)
document.addEventListener('DOMContentLoaded', function() {
    // If you use Bootstrap, uncomment:
    // const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    // tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
});