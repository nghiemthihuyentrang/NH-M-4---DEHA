// Safe share modal handler with null checks
document.addEventListener('DOMContentLoaded', function() {
    // Safe initialization with null checks
    try {
        const shareModal = document.getElementById('shareModal');
        if (shareModal) {
            shareModal.addEventListener('click', function(e) {
                if (e.target === shareModal) {
                    shareModal.style.display = 'none';
                }
            });
            console.log('✓ Share modal initialized');
        } else {
            console.log('ℹ Share modal not found - skipping initialization');
        }
        
        // Safe close button handler
        const closeButtons = document.querySelectorAll('.close-modal, .modal-close');
        if (closeButtons.length > 0) {
            closeButtons.forEach(button => {
                if (button) {
                    button.addEventListener('click', function() {
                        const modal = button.closest('.modal');
                        if (modal) {
                            modal.style.display = 'none';
                        }
                    });
                }
            });
            console.log('✓ Modal close buttons initialized');
        }
        
        // Safe share buttons handler
        const shareButtons = document.querySelectorAll('.share-btn');
        if (shareButtons.length > 0) {
            shareButtons.forEach(button => {
                if (button) {
                    button.addEventListener('click', function() {
                        const shareModal = document.getElementById('shareModal');
                        if (shareModal) {
                            shareModal.style.display = 'block';
                        }
                    });
                }
            });
            console.log('✓ Share buttons initialized');
        }
        
    } catch (error) {
        console.warn('Share modal initialization error:', error);
    }
});

// Export for module systems if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {};
}