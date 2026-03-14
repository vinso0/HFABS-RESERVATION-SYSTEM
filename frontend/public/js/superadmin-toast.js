function showToast(message, type = 'success', title = '') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const config = {
        success: {
            title: title || 'Success',
            icon: 'fa-check',
            className: 'toast-success'
        },
        info: {
            title: title || 'Info',
            icon: 'fa-info',
            className: 'toast-info'
        },
        warning: {
            title: title || 'Warning',
            icon: 'fa-exclamation',
            className: 'toast-warning'
        },
        error: {
            title: title || 'Error',
            icon: 'fa-times',
            className: 'toast-error'
        }
    };

    const toastConfig = config[type] || config.success;

    const toast = document.createElement('div');
    toast.className = `toast ${toastConfig.className}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${toastConfig.icon}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${toastConfig.title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" type="button" aria-label="Close notification">
            <i class="fas fa-times"></i>
        </button>
    `;

    const removeToast = () => {
        toast.classList.add('toast-hide');
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 240);
    };

    toast.querySelector('.toast-close').addEventListener('click', removeToast);

    container.appendChild(toast);
    setTimeout(removeToast, 3200);
}
