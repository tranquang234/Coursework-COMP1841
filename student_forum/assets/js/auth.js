// Ensure logout function is always defined immediately
if (typeof window.logout === 'undefined') {
    window.logout = async function() {
        if (!confirm('Are you sure you want to logout?')) {
            return;
        }
        
        try {
            const response = await fetch('api/auth/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'login.php';
            } else {
                alert(data.message || 'An error occurred while logging out');
            }
        } catch (error) {
            console.error('Error:', error);
            // Still redirect to login page even if there's an error
            window.location.href = 'login.php';
        }
    };
}

// Wait for DOM to be ready before executing
(function() {
    'use strict';
    
    // Ensure document exists
    if (typeof document === 'undefined') {
        console.error('Document is not available');
        return;
    }
    
    // Handle login form
    function initLoginForm() {
        const loginForm = document.getElementById('loginForm');
        if (!loginForm) return;
        
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const errorMessage = document.getElementById('error-message');
            
            if (!username || !password) return;
            
            const usernameValue = username.value.trim();
            const passwordValue = password.value;
            
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
            
            if (!usernameValue || !passwordValue) {
                if (typeof showToast === 'function') {
                    showToast('Please fill in all required information', 'warning');
                } else if (errorMessage) {
                    errorMessage.textContent = 'Please fill in all required information';
                    errorMessage.style.display = 'block';
                }
                return;
            }
            
            try {
                const response = await fetch('api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        username: usernameValue, 
                        password: passwordValue 
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Login successful!', 'success');
                    }
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 500);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Login failed', 'error');
                    } else if (errorMessage) {
                        errorMessage.textContent = data.message || 'Login failed';
                        errorMessage.style.display = 'block';
                    }
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast('An error occurred while logging in. Please try again later.', 'error');
                } else if (errorMessage) {
                    errorMessage.textContent = 'An error occurred while logging in';
                    errorMessage.style.display = 'block';
                }
                console.error('Error:', error);
            }
        });
    }

    // Handle registration form
    function initRegisterForm() {
        const registerForm = document.getElementById('registerForm');
        if (!registerForm) return;
        
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const full_name = document.getElementById('full_name');
            const password = document.getElementById('password');
            const confirm_password = document.getElementById('confirm_password');
            const errorMessage = document.getElementById('error-message');
            
            if (!username || !email || !password || !confirm_password) return;
            
            const usernameValue = username.value.trim();
            const emailValue = email.value.trim();
            const full_nameValue = full_name ? full_name.value.trim() : '';
            const passwordValue = password.value;
            const confirm_passwordValue = confirm_password.value;
            
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
            
            // Validation
            if (!usernameValue || !emailValue || !passwordValue) {
                if (typeof showToast === 'function') {
                    showToast('Please fill in all required information', 'warning');
                } else if (errorMessage) {
                    errorMessage.textContent = 'Please fill in all required information';
                    errorMessage.style.display = 'block';
                }
                return;
            }
            
            if (passwordValue.length < 6) {
                if (typeof showToast === 'function') {
                    showToast('Password must be at least 6 characters', 'warning');
                } else if (errorMessage) {
                    errorMessage.textContent = 'Password must be at least 6 characters';
                    errorMessage.style.display = 'block';
                }
                return;
            }
            
            if (passwordValue !== confirm_passwordValue) {
                if (typeof showToast === 'function') {
                    showToast('Confirm password does not match', 'warning');
                } else if (errorMessage) {
                    errorMessage.textContent = 'Confirm password does not match';
                    errorMessage.style.display = 'block';
                }
                return;
            }
            
            try {
                const response = await fetch('api/auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        username: usernameValue, 
                        email: emailValue, 
                        full_name: full_nameValue, 
                        password: passwordValue 
                    })
                });
                
                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    const text = await response.text();
                    console.error('Response text:', text);
                    if (typeof showToast === 'function') {
                        showToast('Server response error. Please try again later.', 'error');
                    } else if (errorMessage) {
                        errorMessage.textContent = 'Server response error. Please check console for details.';
                        errorMessage.style.display = 'block';
                    }
                    return;
                }
                
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Registration successful! You will be redirected to the homepage...', 'success');
                    } else {
                        alert('Registration successful!');
                    }
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Registration failed', 'error');
                    } else if (errorMessage) {
                        errorMessage.textContent = data.message || 'Registration failed';
                        errorMessage.style.display = 'block';
                    }
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast('An error occurred while registering. Please try again later.', 'error');
                } else if (errorMessage) {
                    errorMessage.textContent = 'An error occurred while registering: ' + error.message;
                    errorMessage.style.display = 'block';
                }
                console.error('Error:', error);
            }
        });
    }
    
    // Initialize when DOM is ready
    function initialize() {
        try {
            initLoginForm();
            initRegisterForm();
        } catch (error) {
            console.error('Error initializing auth forms:', error);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        // DOM is already ready
        initialize();
    }
})();
