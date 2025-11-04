<?php
require_once 'config/database.php';

if (isset($conn)) {
    echo "✅ Database connected!<br>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    echo "Users: " . $stmt->fetch()['count'];
} else {
    echo "❌ Connection failed!";
}
?>