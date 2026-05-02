// Security helper functions
window.Security = {
    // Prevent XSS
    escapeHTML: function(str) {
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    },
    
    // Show notification
    showNotification: function(message, type = 'error') {
        const div = document.createElement('div');
        div.className = `alert alert-${type}`;
        div.textContent = message;
        div.style.display = 'block';
        
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(div, container.firstChild);
            setTimeout(() => div.remove(), 5000);
        }
    }
};

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
});
