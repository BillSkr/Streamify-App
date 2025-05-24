// Form validation functionality
document.addEventListener('DOMContentLoaded', function() {
    // Registration form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!validateRegistrationForm()) {
                e.preventDefault();
            }
        });

        // Real-time validation
        const inputs = registerForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            input.addEventListener('input', function() {
                clearError(this);
            });
        });
    }

    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
                e.preventDefault();
            }
        });

        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            input.addEventListener('input', function() {
                clearError(this);
            });
        });
    }

    // Generic form validation for other forms
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateGenericForm(this)) {
                e.preventDefault();
            }
        });
    });
});

function validateRegistrationForm() {
    let isValid = true;
    const form = document.getElementById('registerForm');
    
    // Get form fields
    const firstName = form.querySelector('#first_name');
    const lastName = form.querySelector('#last_name');
    const username = form.querySelector('#username');
    const email = form.querySelector('#email');
    const password = form.querySelector('#password');
    const confirmPassword = form.querySelector('#confirm_password');
    
    // Validate first name
    if (!validateRequired(firstName, 'Το όνομα είναι υποχρεωτικό.')) {
        isValid = false;
    }
    
    // Validate last name
    if (!validateRequired(lastName, 'Το επώνυμο είναι υποχρεωτικό.')) {
        isValid = false;
    }
    
    // Validate username
    if (!validateRequired(username, 'Το username είναι υποχρεωτικό.')) {
        isValid = false;
    } else if (!validateUsername(username)) {
        isValid = false;
    }
    
    // Validate email
    if (!validateRequired(email, 'Το email είναι υποχρεωτικό.')) {
        isValid = false;
    } else if (!validateEmail(email)) {
        isValid = false;
    }
    
    // Validate password
    if (!validateRequired(password, 'Ο κωδικός πρόσβασης είναι υποχρεωτικός.')) {
        isValid = false;
    } else if (!validatePassword(password)) {
        isValid = false;
    }
    
    // Validate confirm password
    if (!validateRequired(confirmPassword, 'Η επιβεβαίωση κωδικού είναι υποχρεωτική.')) {
        isValid = false;
    } else if (!validatePasswordMatch(password, confirmPassword)) {
        isValid = false;
    }
    
    return isValid;
}

function validateLoginForm() {
    let isValid = true;
    const form = document.getElementById('loginForm');
    
    const usernameEmail = form.querySelector('#username_email');
    const password = form.querySelector('#password');
    
    if (!validateRequired(usernameEmail, 'Το username ή email είναι υποχρεωτικό.')) {
        isValid = false;
    }
    
    if (!validateRequired(password, 'Ο κωδικός πρόσβασης είναι υποχρεωτικός.')) {
        isValid = false;
    }
    
    return isValid;
}

function validateField(field) {
    const fieldType = field.type;
    const fieldName = field.name;
    
    switch (fieldName) {
        case 'first_name':
        case 'last_name':
            return validateRequired(field, 'Αυτό το πεδίο είναι υποχρεωτικό.');
        case 'username':
            return validateRequired(field, 'Το username είναι υποχρεωτικό.') && validateUsername(field);
        case 'email':
            return validateRequired(field, 'Το email είναι υποχρεωτικό.') && validateEmail(field);
        case 'password':
            return validateRequired(field, 'Ο κωδικός πρόσβασης είναι υποχρεωτικός.') && validatePassword(field);
        case 'confirm_password':
            const password = document.querySelector('#password');
            return validateRequired(field, 'Η επιβεβαίωση κωδικού είναι υποχρεωτική.') && 
                   validatePasswordMatch(password, field);
        default:
            return validateRequired(field, 'Αυτό το πεδίο είναι υποχρεωτικό.');
    }
}

function validateRequired(field, message) {
    if (!field.value.trim()) {
        showFieldError(field, message);
        return false;
    }
    clearFieldError(field);
    return true;
}

function validateUsername(field) {
    const username = field.value.trim();
    if (username.length < 3) {
        showFieldError(field, 'Το username πρέπει να έχει τουλάχιστον 3 χαρακτήρες.');
        return false;
    }
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showFieldError(field, 'Το username μπορεί να περιέχει μόνο γράμματα, αριθμούς και κάτω παύλα.');
        return false;
    }
    clearFieldError(field);
    return true;
}

function validateEmail(field) {
    const email = field.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showFieldError(field, 'Παρακαλώ εισάγετε έγκυρο email.');
        return false;
    }
    clearFieldError(field);
    return true;
}

function validatePassword(field) {
    const password = field.value;
    if (password.length < 6) {
        showFieldError(field, 'Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 6 χαρακτήρες.');
        return false;
    }
    clearFieldError(field);
    return true;
}

function validatePasswordMatch(passwordField, confirmField) {
    if (passwordField.value !== confirmField.value) {
        showFieldError(confirmField, 'Οι κωδικοί πρόσβασης δεν ταιριάζουν.');
        return false;
    }
    clearFieldError(confirmField);
    return true;
}

function showFieldError(field, message) {
    const errorDiv = document.getElementById(field.name + '_error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    field.classList.add('error');
}

function clearFieldError(field) {
    const errorDiv = document.getElementById(field.name + '_error');
    if (errorDiv) {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
    }
    field.classList.remove('error');
}

function clearError(field) {
    clearFieldError(field);
}

function validateGenericForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Real-time character counter for textareas
function addCharacterCounter(textarea, maxLength) {
    const counter = document.createElement('div');
    counter.className = 'character-counter';
    counter.style.textAlign = 'right';
    counter.style.fontSize = '0.8rem';
    counter.style.color = 'var(--text-secondary)';
    counter.style.marginTop = '0.25rem';
    
    function updateCounter() {
        const remaining = maxLength - textarea.value.length;
        counter.textContent = `${textarea.value.length}/${maxLength}`;
        
        if (remaining < 0) {
            counter.style.color = '#dc3545';
            textarea.classList.add('error');
        } else if (remaining < 50) {
            counter.style.color = '#ffc107';
            textarea.classList.remove('error');
        } else {
            counter.style.color = 'var(--text-secondary)';
            textarea.classList.remove('error');
        }
    }
    
    textarea.addEventListener('input', updateCounter);
    textarea.parentNode.appendChild(counter);
    updateCounter();
}

// Initialize character counters for textareas with maxlength
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.getAttribute('maxlength'));
        addCharacterCounter(textarea, maxLength);
    });
});

// Form submission with loading state
function submitFormWithLoading(form, submitButton) {
    const originalText = submitButton.textContent;
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.style.width = '16px';
    spinner.style.height = '16px';
    spinner.style.display = 'inline-block';
    spinner.style.marginRight = '8px';
    
    submitButton.disabled = true;
    submitButton.innerHTML = '';
    submitButton.appendChild(spinner);
    submitButton.appendChild(document.createTextNode('Επεξεργασία...'));
    
    // Re-enable button after 10 seconds (fallback)
    setTimeout(() => {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }, 10000);
}

// Add loading state to forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.disabled) {
                // Only add loading state if form validation passes
                setTimeout(() => {
                    submitFormWithLoading(form, submitButton);
                }, 100);
            }
        });
    });
});

// Prevent double submission
let isSubmitting = false;
document.addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    
    // Reset after 3 seconds
    setTimeout(() => {
        isSubmitting = false;
    }, 3000);
});