document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('main-container');
    const toRegisterBtns = document.querySelectorAll('.to-register');
    const toLoginBtns = document.querySelectorAll('.to-login');
    
    // Toggle Views
    toRegisterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.add('active');
            clearAlerts();
        });
    });

    toLoginBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove('active');
            clearAlerts();
        });
    });

    // Toggle Password Visibility
    const togglePasswords = document.querySelectorAll('.toggle-password');
    togglePasswords.forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    });

    // Password Strength
    const regPassword = document.getElementById('reg-password');
    const strengthMeter = document.getElementById('strength-meter-fill');
    
    if(regPassword && strengthMeter) {
        regPassword.addEventListener('input', () => {
            const val = regPassword.value;
            let strength = 0;
            
            if(val.length >= 8) strength += 25;
            if(val.match(/[a-z]+/)) strength += 25;
            if(val.match(/[A-Z]+/)) strength += 25;
            if(val.match(/[0-9]+/) || val.match(/[\W]+/)) strength += 25;
            
            strengthMeter.style.width = strength + '%';
            
            if(strength <= 25) strengthMeter.style.backgroundColor = '#EF4444'; // Red
            else if(strength <= 50) strengthMeter.style.backgroundColor = '#F59E0B'; // Orange
            else if(strength <= 75) strengthMeter.style.backgroundColor = '#3B82F6'; // Blue
            else strengthMeter.style.backgroundColor = '#10B981'; // Green
        });
    }

    // --- Toast Notification System ---
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
        toast.innerHTML = `${icon} <span>${message}</span>`;
        
        toastContainer.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    // --- Validation Helpers ---
    function setError(input, message) {
        input.classList.add('input-error');
        const errorSpan = document.getElementById(`error-${input.id}`);
        if (errorSpan) {
            errorSpan.textContent = message;
            errorSpan.classList.add('active');
        }
        
        // Remove animation class after it plays so it can play again
        setTimeout(() => {
            input.classList.remove('input-error');
            // We only remove the animation part, we can keep a static border class if needed
            // But requirement asks for red border AND shake. Let's add a static error border.
            input.style.borderColor = '#EF4444';
        }, 400);
    }

    function clearError(input) {
        input.classList.remove('input-error');
        input.style.borderColor = ''; // reset inline style
        const errorSpan = document.getElementById(`error-${input.id}`);
        if (errorSpan) {
            errorSpan.classList.remove('active');
        }
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function validatePhone(phone) {
        // Basic phone validation (digits, plus, spaces, dashes)
        return /^[\d\s\+\-]+$/.test(phone) && phone.length >= 7;
    }

    // --- Real-time Validation ---
    const allInputs = document.querySelectorAll('input, select');
    allInputs.forEach(input => {
        input.addEventListener('input', () => clearError(input));
    });

    // --- Forms submission handlers ---
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if(loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate Login
            let isValid = true;
            const emailInput = document.getElementById('login-email');
            const passwordInput = document.getElementById('login-password');
            const roleInput = document.getElementById('login-role');

            if(!emailInput.value.trim()) { setError(emailInput, 'Please fill out this field'); isValid = false; }
            else if(!validateEmail(emailInput.value)) { setError(emailInput, 'Please enter a valid email address'); isValid = false; }
            
            if(!passwordInput.value) { setError(passwordInput, 'Please fill out this field'); isValid = false; }
            if(!roleInput.value) { setError(roleInput, 'Please fill out this field'); isValid = false; }

            if(!isValid) return;

            const btn = loginForm.querySelector('.submit-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            btn.disabled = true;

            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('../app/api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showToast(result.message, 'success');
                    // Login Success Flow
                    btn.innerHTML = '<i class="fas fa-check"></i> Success!';
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 800);
                } else {
                    // Login Validation
                    showToast('Invalid email or password', 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                showToast('Network error. Please try again.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

    if(registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate Register
            let isValid = true;
            const nameInput = document.getElementById('reg-name');
            const emailInput = document.getElementById('reg-email');
            const phoneInput = document.getElementById('reg-phone');
            const passwordInput = document.getElementById('reg-password');
            const confirmInput = document.getElementById('reg-confirm-password');
            const roleInput = document.getElementById('reg-role');

            if(!nameInput.value.trim()) { setError(nameInput, 'Please fill out this field'); isValid = false; }
            
            if(!emailInput.value.trim()) { setError(emailInput, 'Please fill out this field'); isValid = false; }
            else if(!validateEmail(emailInput.value)) { setError(emailInput, 'Please enter a valid email address'); isValid = false; }
            
            if(!phoneInput.value.trim()) { setError(phoneInput, 'Please fill out this field'); isValid = false; }
            else if(!validatePhone(phoneInput.value)) { setError(phoneInput, 'Please enter a valid phone number'); isValid = false; }
            
            if(!passwordInput.value) { setError(passwordInput, 'Please fill out this field'); isValid = false; }
            else if(passwordInput.value.length < 8) { setError(passwordInput, 'Password must be at least 8 characters'); isValid = false; }
            
            if(!confirmInput.value) { setError(confirmInput, 'Please fill out this field'); isValid = false; }
            else if(passwordInput.value !== confirmInput.value) { 
                setError(confirmInput, 'Passwords do not match'); 
                isValid = false; 
            }
            
            if(!roleInput.value) { setError(roleInput, 'Please fill out this field'); isValid = false; }

            if(!isValid) return;

            const btn = registerForm.querySelector('.submit-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
            btn.disabled = true;

            const formData = new FormData(registerForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('../app/api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showToast(result.message, 'success');
                    
                    // Registration Success Flow
                    setTimeout(() => {
                        // Switch to login form
                        container.classList.remove('active');
                        
                        // Auto-fill email and focus password
                        const loginEmail = document.getElementById('login-email');
                        const loginPassword = document.getElementById('login-password');
                        
                        loginEmail.value = emailInput.value;
                        
                        // Reset register form
                        registerForm.reset();
                        strengthMeter.style.width = '0%';
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        // Focus password after transition
                        setTimeout(() => loginPassword.focus(), 600);
                        
                    }, 1500);
                    
                } else {
                    showToast(result.message, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                showToast('Network error. Please try again.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }
});
