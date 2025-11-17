// Share Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Check if share modal elements exist before adding event listeners
    const shareButtons = document.querySelectorAll('.btn-share, .share-btn');
    const shareModal = document.getElementById('shareModal');
    const closeButtons = document.querySelectorAll('.close-share');
    
    // Only add event listeners if elements exist
    if (shareButtons.length > 0) {
        shareButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                openShareModal();
            });
        });
    }
    
    if (shareModal) {
        shareModal.addEventListener('click', function(e) {
            if (e.target === shareModal) {
                closeShareModal();
            }
        });
    }
    
    if (closeButtons.length > 0) {
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                closeShareModal();
            });
        });
    }
});

function openShareModal() {
    const shareModal = document.getElementById('shareModal');
    if (shareModal) {
        shareModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeShareModal() {
    const shareModal = document.getElementById('shareModal');
    if (shareModal) {
        shareModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function shareToFacebook() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareToTwitter() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
}

function copyToClipboard() {
    const url = window.location.href;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Đã sao chép liên kết!', 'success');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('Đã sao chép liên kết!', 'success');
        } catch (err) {
            showNotification('Không thể sao chép liên kết', 'error');
        }
        document.body.removeChild(textArea);
    }
}

// Notification function if not already defined
if (typeof showNotification === 'undefined') {
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 2001;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            min-width: 300px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        `;
        
        if (type === 'success') {
            notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        } else if (type === 'error') {
            notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #e83e8c 100%)';
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}