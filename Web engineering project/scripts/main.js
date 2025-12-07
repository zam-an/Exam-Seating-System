// ============================================
// MAIN.JS - Exam Seating System
// Version: 2.0 - Fresh Start (No test connection)
// ============================================

// Base URL for all API calls - relative path works with any folder name
const API_BASE = 'api';

// Log to confirm new file is loaded
console.log('✅ NEW main.js v2.0 loaded successfully!');

// ============================================
// LOGIN FUNCTIONALITY
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Validate credentials against database
            fetch(`${API_BASE}/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store user info in localStorage
                    localStorage.setItem('isLoggedIn', 'true');
                    localStorage.setItem('username', data.user.username);
                    localStorage.setItem('userId', data.user.id);
                    localStorage.setItem('userEmail', data.user.email);
                    window.location.href = 'dashboard.html';
                } else {
                    alert(data.error || 'Invalid username or password');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                alert('An error occurred during login. Please try again.');
            });
        });
    }
    
    // Signup form handling
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return;
            }
            
            // Create user via API
            fetch(`${API_BASE}/users.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Account created successfully! Please login.');
                    window.location.href = 'index.html';
                } else {
                    alert('Error: ' + (data.error || 'Failed to create account'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

function logout() {
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('username');
    window.location.href = 'index.html';
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    } else {
        alert(message);
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// ============================================
// MODAL INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    document.querySelectorAll('.form-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modal with close button
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.form-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
});

// ============================================
// AUTHENTICATION CHECK
// ============================================
function checkAuth() {
    if (localStorage.getItem('isLoggedIn') !== 'true') {
        window.location.href = 'index.html';
        return false;
    }
    return true;
}

// ============================================
// END OF FILE - No test connection function
// ============================================

