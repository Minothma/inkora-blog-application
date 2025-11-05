// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initTooltips();
    initPopovers();
    initFormValidation();
    initCharacterCounters();
    initImagePreviews();
});

/**
 * Initialize Bootstrap Tooltips
 * Enables tooltips on all elements with data-bs-toggle="tooltip"
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize Bootstrap Popovers
 * Enables popovers on all elements with data-bs-toggle="popover"
 */
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Initialize Form Validation
 * Adds real-time validation to forms
 */
function initFormValidation() {
    // Get all forms with needs-validation class
    const forms = document.querySelectorAll('.needs-validation');
    
    // Loop over forms and prevent submission if invalid
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Initialize Character Counters
 * Shows character count for textareas with data-max-length attribute
 */
function initCharacterCounters() {
    const textareas = document.querySelectorAll('textarea[data-max-length]');
    
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('data-max-length');
        const counterId = textarea.id + '-counter';
        
        // Create counter element if it doesn't exist
        let counter = document.getElementById(counterId);
        if (!counter) {
            counter = document.createElement('small');
            counter.id = counterId;
            counter.className = 'text-muted';
            textarea.parentNode.appendChild(counter);
        }
        
        // Update counter on input
        const updateCounter = () => {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${remaining} characters remaining`;
            
            if (remaining < 0) {
                counter.classList.remove('text-muted');
                counter.classList.add('text-danger');
            } else {
                counter.classList.remove('text-danger');
                counter.classList.add('text-muted');
            }
        };
        
        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial update
    });
}

/**
 * Initialize Image Previews
 * Shows preview of selected image files
 */
function initImagePreviews() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Find or create preview element
                    let preview = document.getElementById(input.id + '-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = input.id + '-preview';
                        preview.className = 'img-thumbnail mt-2';
                        preview.style.maxWidth = '300px';
                        input.parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            }
        });
    });
}

/**
 * Show Loading Spinner
 * Displays a loading overlay
 */
function showLoading() {
    const loadingHTML = `
        <div id="loading-overlay" class="spinner-overlay">
            <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    if (!document.getElementById('loading-overlay')) {
        document.body.insertAdjacentHTML('beforeend', loadingHTML);
    }
}

/**
 * Hide Loading Spinner
 * Removes the loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Show Toast Notification
 * Displays a Bootstrap toast message
 * 
 * @param {string} message - Message to display
 * @param {string} type - Toast type (success, danger, warning, info)
 */
function showToast(message, type = 'info') {
    const toastColors = {
        'success': 'bg-success',
        'danger': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    };
    
    const toastHTML = `
        <div class="toast align-items-center text-white ${toastColors[type]} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    // Add toast to container
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    // Show toast
    const toastElement = container.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast from DOM after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Confirm Action
 * Shows a confirmation dialog with custom message
 * 
 * @param {string} message - Confirmation message
 * @param {Function} callback - Function to call if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Debounce Function
 * Delays execution of a function until after specified time
 * 
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format Date
 * Formats a date string to readable format
 * 
 * @param {string} dateString - Date string to format
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Time Ago
 * Converts date to "time ago" format (e.g., "2 hours ago")
 * 
 * @param {string} dateString - Date string
 * @returns {string} Time ago string
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60,
        second: 1
    };
    
    for (const [key, value] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / value);
        if (interval >= 1) {
            return interval === 1 ? `1 ${key} ago` : `${interval} ${key}s ago`;
        }
    }
    
    return 'just now';
}

/**
 * Truncate Text
 * Truncates text to specified length
 * 
 * @param {string} text - Text to truncate
 * @param {number} length - Maximum length
 * @returns {string} Truncated text
 */
function truncateText(text, length) {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}

/**
 * Validate Email
 * Checks if email is valid
 * 
 * @param {string} email - Email to validate
 * @returns {boolean} True if valid
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate Password Strength
 * Checks password strength
 * 
 * @param {string} password - Password to validate
 * @returns {object} Validation result with score and feedback
 */
function validatePasswordStrength(password) {
    let score = 0;
    const feedback = [];
    
    // Check length
    if (password.length >= 8) score++;
    else feedback.push('Password must be at least 8 characters');
    
    // Check for lowercase
    if (/[a-z]/.test(password)) score++;
    else feedback.push('Add lowercase letters');
    
    // Check for uppercase
    if (/[A-Z]/.test(password)) score++;
    else feedback.push('Add uppercase letters');
    
    // Check for numbers
    if (/\d/.test(password)) score++;
    else feedback.push('Add numbers');
    
    // Check for special characters
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
    else feedback.push('Add special characters');
    
    const strength = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    
    return {
        score: score,
        strength: strength[Math.min(score, 4)],
        feedback: feedback,
        isValid: score >= 3
    };
}

/**
 * Copy to Clipboard
 * Copies text to clipboard
 * 
 * @param {string} text - Text to copy
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy to clipboard', 'danger');
    });
}

/**
 * Sanitize HTML
 * Basic HTML sanitization to prevent XSS
 * 
 * @param {string} html - HTML string to sanitize
 * @returns {string} Sanitized HTML
 */
function sanitizeHTML(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}

/**
 * Scroll to Element
 * Smoothly scrolls to an element
 * 
 * @param {string} elementId - ID of element to scroll to
 */
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * AJAX Helper Function
 * Makes AJAX requests easier
 * 
 * @param {string} url - URL to send request to
 * @param {object} options - Request options (method, data, headers)
 * @returns {Promise} Promise with response
 */
async function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const config = { ...defaults, ...options };
    
    // Convert data to JSON if it's an object
    if (config.data && typeof config.data === 'object') {
        config.body = JSON.stringify(config.data);
    }
    
    try {
        showLoading();
        const response = await fetch(url, config);
        const data = await response.json();
        hideLoading();
        
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }
        
        return data;
    } catch (error) {
        hideLoading();
        showToast(error.message, 'danger');
        throw error;
    }
}

/**
 * Form Data to Object
 * Converts FormData to plain object
 * 
 * @param {FormData} formData - FormData to convert
 * @returns {object} Plain object
 */
function formDataToObject(formData) {
    const object = {};
    formData.forEach((value, key) => {
        object[key] = value;
    });
    return object;
}

/**
 * Local Storage Helper
 * Safely get/set localStorage with JSON support
 */
const storage = {
    set: function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    },
    
    get: function(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.error('Storage error:', e);
            return defaultValue;
        }
    },
    
    remove: function(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    }
};

// Export functions for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showLoading,
        hideLoading,
        showToast,
        confirmAction,
        debounce,
        formatDate,
        timeAgo,
        truncateText,
        validateEmail,
        validatePasswordStrength,
        copyToClipboard,
        sanitizeHTML,
        scrollToElement,
        ajaxRequest,
        formDataToObject,
        storage
    };
}