/**
 * Authentication JavaScript
 * Handles form validation, password strength, and user interactions
 */

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    const feedback = {
        level: 'weak',
        message: ''
    };

    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    if (strength <= 2) {
        feedback.level = 'weak';
        feedback.message = 'Weak password';
    } else if (strength <= 4) {
        feedback.level = 'medium';
        feedback.message = 'Medium password';
    } else {
        feedback.level = 'strong';
        feedback.message = 'Strong password';
    }

    return feedback;
}

// Toggle password visibility
function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);

    if (input && button) {
        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            input.type = 'password';
            button.innerHTML = '<i class="bi bi-eye"></i>';
        }
    }
}

// Validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate username
function validateUsername(username) {
    // Username: 3-20 characters, alphanumeric and underscore only
    const re = /^[a-zA-Z0-9_]{3,20}$/;
    return re.test(username);
}

// Real-time form validation
document.addEventListener('DOMContentLoaded', function () {

    // Password strength meter (for registration page)
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-bar-fill');
    const strengthText = document.querySelector('.strength-text');

    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function () {
            const password = this.value;
            const strength = checkPasswordStrength(password);

            // Update strength bar
            strengthBar.className = 'strength-bar-fill ' + strength.level;

            // Update text
            if (strengthText) {
                strengthText.textContent = strength.message;
                strengthText.style.color =
                    strength.level === 'weak' ? '#e74c3c' :
                        strength.level === 'medium' ? '#f39c12' : '#2ecc71';
            }
        });
    }

    // Confirm password validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            const feedback = this.nextElementSibling;
            if (this.value !== passwordInput.value) {
                this.classList.add('error');
                this.classList.remove('success');
                if (feedback) {
                    feedback.textContent = 'Passwords do not match';
                    feedback.classList.add('error');
                }
            } else if (this.value.length > 0) {
                this.classList.add('success');
                this.classList.remove('error');
                if (feedback) {
                    feedback.textContent = 'Passwords match';
                    feedback.classList.remove('error');
                    feedback.classList.add('success');
                }
            }
        });
    }

    // Email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function () {
            const feedback = this.nextElementSibling;
            if (this.value && !validateEmail(this.value)) {
                this.classList.add('error');
                this.classList.remove('success');
                if (feedback) {
                    feedback.textContent = 'Please enter a valid email address';
                    feedback.classList.add('error');
                }
            } else if (this.value) {
                this.classList.add('success');
                this.classList.remove('error');
                if (feedback) {
                    feedback.textContent = '';
                    feedback.classList.remove('error');
                }
            }
        });
    }

    // Username validation
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('blur', function () {
            const feedback = this.nextElementSibling;
            if (this.value && !validateUsername(this.value)) {
                this.classList.add('error');
                this.classList.remove('success');
                if (feedback) {
                    feedback.textContent = 'Username must be 3-20 characters (letters, numbers, underscore)';
                    feedback.classList.add('error');
                }
            } else if (this.value) {
                this.classList.add('success');
                this.classList.remove('error');
                if (feedback) {
                    feedback.textContent = '';
                    feedback.classList.remove('error');
                }
            }
        });
    }

    // Form submission with loading state
    const authForms = document.querySelectorAll('.auth-form');
    authForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('.btn-submit');
            if (submitBtn && !submitBtn.classList.contains('loading')) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

});

// Show/hide password toggle buttons
function setupPasswordToggles() {
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        const wrapper = field.parentElement;
        if (wrapper.classList.contains('password-wrapper')) {
            const toggleBtn = wrapper.querySelector('.password-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const input = this.previousElementSibling;
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.innerHTML = '<i class="bi bi-eye-slash"></i>';
                    } else {
                        input.type = 'password';
                        this.innerHTML = '<i class="bi bi-eye"></i>';
                    }
                });
            }
        }
    });
}

// Initialize password toggles when DOM is ready
document.addEventListener('DOMContentLoaded', setupPasswordToggles);
