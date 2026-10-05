/**
 * Update all cart badges.
 *
 * Can be called from storefront pages after
 * successful AJAX cart operations.
 */
window.updateCartBadge = function (count) {
    const badges = document.querySelectorAll('#cart-count-badge, .store-mobile-bottom .store-badge');

    badges.forEach(function (badge) {
        const value = Number(count) || 0;
        badge.textContent = value;
        if (value > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    });
};


/**
 * Simple storefront notification helper.
 */
window.showStoreNotification = function (message, type = 'success') {
    const existing = document.getElementById('store-notification-container');
    if (!existing) {
        const container = document.createElement('div');

        container.id = 'store-notification-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '2000';

        document.body.appendChild(container);
    }

    const container = document.getElementById('store-notification-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show shadow`;
    alert.setAttribute('role', 'alert');
    alert.innerHTML = `
        ${message}
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>
    `;
    container.appendChild(alert);
    setTimeout(function () {
        if (alert && alert.parentNode) {
            alert.remove();
        }
    }, 4000);
};
