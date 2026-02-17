// Admin Login JavaScript
const API_BASE = '../api';

document.addEventListener('DOMContentLoaded', function() {
    // Check if already logged in
    const token = localStorage.getItem('adminToken');
    if (token) {
        // Verify token is still valid
        verifyToken(token);
        return;
    }

    // Set up login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Auto-focus username field
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.focus();
    }
});

async function handleLogin(e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!username || !password) {
        showMessage('Please enter both username and password', 'error');
        return;
    }

    // Show loading state
    const submitBtn = document.querySelector('.login-btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
    submitBtn.disabled = true;

    try {
        const response = await fetch(`${API_BASE}/admin.php?action=login&t=${Date.now()}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                password: password
            })
        });

        const data = await response.json();

        if (data.success) {
            // Store token
            localStorage.setItem('adminToken', data.token);

            // Show success message
            showMessage('Login successful! Redirecting...', 'success');

            // Redirect to dashboard after short delay
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1000);
        } else {
            showMessage(data.message || 'Login failed', 'error');
        }

    } catch (error) {
        console.error('Login error:', error);
        showMessage('Network error occurred. Please try again.', 'error');
    } finally {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function verifyToken(token) {
    try {
        const response = await fetch(`${API_BASE}/admin.php?action=dashboard-stats&t=${Date.now()}`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            // Token is valid, redirect to dashboard
            window.location.href = 'dashboard.html';
        } else {
            // Token is invalid, remove it
            localStorage.removeItem('adminToken');
        }
    } catch (error) {
        console.error('Token verification error:', error);
        localStorage.removeItem('adminToken');
    }
}

function showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('message');

    // Clear any existing message classes
    messageDiv.className = 'message';

    // Add the appropriate class
    messageDiv.classList.add(type);

    // Set message content
    messageDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
    `;

    // Show the message
    messageDiv.style.display = 'block';

    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}

// Handle Enter key in password field
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const loginForm = document.getElementById('loginForm');
                if (loginForm) {
                    loginForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    }
});

// Demo credentials hint (remove in production)
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        // Add demo credentials hint
        const hint = document.createElement('div');
        hint.style.cssText = `
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e1e8ed;
        `;
        hint.innerHTML = `
            <strong>Demo Credentials:</strong><br>
            Username: <code>admin</code> | Password: <code>admin123</code><br>
            Username: <code>shady</code> | Password: <code>shady123</code>
        `;

        loginForm.appendChild(hint);
    }
});</contents>
</xai:function_call">Create analytics API for dashboard analytics data