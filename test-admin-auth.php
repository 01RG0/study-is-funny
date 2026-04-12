<?php
/**
 * Test Admin Authentication Fix
 * Verifies that admin login sets proper session variables
 */

session_start();

echo "<h1>Admin Authentication Test</h1>";

// Simulate admin login
echo "<h2>1. Simulating Admin Login</h2>";

// Load admin.php to get the login function
require_once __DIR__ . '/api/admin.php';

// Mock POST data for login
$_POST['username'] = 'admin';
$_POST['password'] = 'admin123';

// Capture output
ob_start();
adminLogin();
$output = ob_get_clean();

echo "<pre>$output</pre>";

// Check session variables
echo "<h2>2. Session Variables After Login</h2>";
echo "<table border='1'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";

$sessionVars = [
    'admin_authenticated',
    'admin_token', 
    'admin_username',
    'admin_login_time',
    'admin_id',
    'role'
];

foreach ($sessionVars as $var) {
    $value = $_SESSION[$var] ?? 'NOT SET';
    echo "<tr><td>$var</td><td>$value</td></tr>";
}
echo "</table>";

// Test payment review authentication
echo "<h2>3. Payment Review Authentication Test</h2>";

// Check if payment review would allow access
$hasAdminId = isset($_SESSION['admin_id']);
$hasRole = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

echo "<p><strong>Has admin_id:</strong> " . ($hasAdminId ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Has role=admin:</strong> " . ($hasRole ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Payment Review Access:</strong> " . ($hasAdminId && $hasRole ? 'GRANTED' : 'DENIED') . "</p>";

// Test payment API authentication
echo "<h2>4. Payment API Authentication Test</h2>";

// Simulate payment API check
$paymentAuth = !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin';
echo "<p><strong>Payment API Check:</strong> " . ($paymentAuth ? 'DENIED' : 'GRANTED') . "</p>";

echo "<h2>5. Test Results</h2>";
if ($hasAdminId && $hasRole) {
    echo "<p style='color: green; font-weight: bold;'>SUCCESS: Admin authentication fixed!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>FAILED: Admin authentication still broken</p>";
}

echo "<p><a href='admin/login.html'>Go to Admin Login</a></p>";
?>
