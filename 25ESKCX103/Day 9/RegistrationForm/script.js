document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const themeToggle = document.getElementById('themeToggle');
    const submitBtn = document.getElementById('submitBtn');
    const formMessage = document.getElementById('formMessage');

    // Form inputs
    const fullname = document.getElementById('fullname');
    const email = document.getElementById('email');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');

    // Error span elements
    const fullnameError = document.getElementById('fullnameError');
    const emailError = document.getElementById('emailError');
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    // Password strength elements
    const strengthBarContainer = document.querySelector('.password-strength-container');
    const strengthBarFill = document.getElementById('strengthBarFill');
    const strengthText = document.getElementById('strengthText');

    // 1. Dark/Light Theme Switching with Persistence
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);

    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });

    // 2. Validation Helper Functions
    const showError = (inputElement, errorElement, message) => {
        inputElement.classList.add('invalid');
        inputElement.classList.remove('valid');
        errorElement.textContent = message;
        errorElement.classList.add('show');
    };

    const clearError = (inputElement, errorElement) => {
        inputElement.classList.remove('invalid');
        inputElement.classList.add('valid');
        errorElement.textContent = '';
        errorElement.classList.remove('show');
    };

    // 3. Specific Validators
    const validateFullName = () => {
        const value = fullname.value.trim();
        if (value === '') {
            showError(fullname, fullnameError, 'Full name is required.');
            return false;
        } else if (value.length < 3) {
            showError(fullname, fullnameError, 'Full name must be at least 3 characters.');
            return false;
        } else if (!/^[a-zA-Z\s]+$/.test(value)) {
            showError(fullname, fullnameError, 'Full name must contain letters and spaces only.');
            return false;
        }
        clearError(fullname, fullnameError);
        return true;
    };

    const validateEmail = () => {
        const value = email.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value === '') {
            showError(email, emailError, 'Email address is required.');
            return false;
        } else if (!emailRegex.test(value)) {
            showError(email, emailError, 'Please enter a valid email address.');
            return false;
        }
        clearError(email, emailError);
        return true;
    };

    const validateUsername = () => {
        const value = username.value.trim();
        const usernameRegex = /^[a-zA-Z0-9_]+$/;
        if (value === '') {
            showError(username, usernameError, 'Username is required.');
            return false;
        } else if (value.length < 3) {
            showError(username, usernameError, 'Username must be at least 3 characters.');
            return false;
        } else if (value.length > 30) {
            showError(username, usernameError, 'Username must not exceed 30 characters.');
            return false;
        } else if (!usernameRegex.test(value)) {
            showError(username, usernameError, 'Username can only contain letters, numbers, and underscores.');
            return false;
        }
        clearError(username, usernameError);
        return true;
    };

    const getPasswordStrength = (pass) => {
        let score = 0;
        if (!pass) return score;

        // Length criteria
        if (pass.length >= 8) score++;
        if (pass.length >= 12) score++;
        
        // Character variety criteria
        if (/[A-Z]/.test(pass)) score++;
        if (/[0-9]/.test(pass)) score++;
        if (/[^a-zA-Z0-9]/.test(pass)) score++;

        return score;
    };

    const validatePassword = () => {
        const value = password.value;
        if (value === '') {
            strengthBarContainer.style.display = 'none';
            showError(password, passwordError, 'Password is required.');
            return false;
        }

        strengthBarContainer.style.display = 'flex';
        const strength = getPasswordStrength(value);

        // Update strength bar color and text
        if (strength <= 1) {
            strengthBarFill.style.width = '20%';
            strengthBarFill.style.backgroundColor = '#ef4444'; // Red
            strengthText.textContent = 'Weak';
            strengthText.style.color = '#ef4444';
        } else if (strength <= 3) {
            strengthBarFill.style.width = '60%';
            strengthBarFill.style.backgroundColor = '#f59e0b'; // Amber
            strengthText.textContent = 'Medium';
            strengthText.style.color = '#f59e0b';
        } else {
            strengthBarFill.style.width = '100%';
            strengthBarFill.style.backgroundColor = '#10b981'; // Green
            strengthText.textContent = 'Strong';
            strengthText.style.color = '#10b981';
        }

        if (value.length < 8) {
            showError(password, passwordError, 'Password must be at least 8 characters long.');
            return false;
        }

        clearError(password, passwordError);
        return true;
    };

    const validateConfirmPassword = () => {
        const value = confirmPassword.value;
        const passValue = password.value;
        if (value === '') {
            showError(confirmPassword, confirmPasswordError, 'Please confirm your password.');
            return false;
        } else if (value !== passValue) {
            showError(confirmPassword, confirmPasswordError, 'Passwords do not match.');
            return false;
        }
        clearError(confirmPassword, confirmPasswordError);
        return true;
    };

    // 4. Bind Real-time Input Listeners
    fullname.addEventListener('input', validateFullName);
    fullname.addEventListener('blur', validateFullName);

    email.addEventListener('input', validateEmail);
    email.addEventListener('blur', validateEmail);

    username.addEventListener('input', validateUsername);
    username.addEventListener('blur', validateUsername);

    password.addEventListener('input', () => {
        validatePassword();
        if (confirmPassword.value) validateConfirmPassword();
    });
    password.addEventListener('blur', validatePassword);

    confirmPassword.addEventListener('input', validateConfirmPassword);
    confirmPassword.addEventListener('blur', validateConfirmPassword);

    // 5. AJAX Form Submission
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Perform final check on all fields
        const isNameValid = validateFullName();
        const isEmailValid = validateEmail();
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        const isConfirmValid = validateConfirmPassword();

        if (!isNameValid || !isEmailValid || !isUsernameValid || !isPasswordValid || !isConfirmValid) {
            // Find first error and scroll to/focus it
            const firstError = document.querySelector('input.invalid');
            if (firstError) {
                firstError.focus();
            }
            return;
        }

        // Prepare request
        submitBtn.classList.add('loading');
        formMessage.style.display = 'none';
        formMessage.className = '';

        const formData = new FormData(registerForm);

        try {
            const response = await fetch(registerForm.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Success response
                formMessage.textContent = data.message;
                formMessage.classList.add('success');
                
                // Clear the form fields
                registerForm.reset();
                
                // Clear validation styles
                document.querySelectorAll('.form-group input').forEach(input => {
                    input.classList.remove('valid', 'invalid');
                });
                strengthBarContainer.style.display = 'none';
            } else {
                // Server validation or duplicate check failed
                formMessage.textContent = data.message || 'Registration failed. Please try again.';
                formMessage.classList.add('error');
            }
        } catch (error) {
            // Network or parsing error
            console.error('Submission error:', error);
            formMessage.textContent = 'A network error occurred. Please check your connection and try again.';
            formMessage.classList.add('error');
        } finally {
            submitBtn.classList.remove('loading');
        }
    });
});
