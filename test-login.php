<?php
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Test Admin Login System</h2>";

// Test database connection
try {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $row = $result->fetch_assoc();
    echo "<p>✅ Database connected. Admin users found: " . $row['count'] . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test functions
echo "<p>isLoggedIn(): " . (isLoggedIn() ? "Yes" : "No") . "</p>";
echo "<p>isAdmin(): " . (isAdmin() ? "Yes" : "No") . "</p>";

echo "<hr>";
echo "<p><strong>To test admin login:</strong></p>";
echo "<p>1. Go to <a href='login.php'>login.php</a></p>";
echo "<p>2. Use: admin@badminton.local / admin123</p>";
echo "<p>3. Should redirect to admin dashboard</p>";
?>