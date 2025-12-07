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
            
            if (username === 'admin' && password === 'admin123') {
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('username', username);
                window.location.href = 'dashboard.html';
            } else {
                alert('Invalid credentials. Use admin / admin123');
            }
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

