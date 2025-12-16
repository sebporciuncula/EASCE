<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(10); // Max 10 seconds

echo "Step 1: PHP Working ✅<br>";
flush();

echo "Step 2: Testing Session...<br>";
flush();
session_start();
echo "Session Started ✅<br>";
flush();

echo "Step 3: Testing Database Connection...<br>";
flush();

try {
    $conn = new PDO("mysql:host=localhost;dbname=easce_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "Database Connected ✅<br>";
    flush();
} catch (PDOException $e) {
    echo "Database Error ❌: " . $e->getMessage() . "<br>";
    flush();
    exit;
}

echo "Step 4: Checking if 'users' table exists...<br>";
flush();

try {
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "'users' table exists ✅<br>";
        flush();
    } else {
        echo "'users' table NOT found ❌<br>";
        flush();
        exit;
    }
} catch (PDOException $e) {
    echo "Query Error ❌: " . $e->getMessage() . "<br>";
    flush();
    exit;
}

echo "Step 5: Counting users...<br>";
flush();

try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "Total users: <strong>$count</strong> ✅<br>";
    flush();
} catch (PDOException $e) {
    echo "Count Error ❌: " . $e->getMessage() . "<br>";
    flush();
    exit;
}

echo "<hr>";
echo "<h2>All tests passed! ✅</h2>";
echo "<p><a href='simple-login.php'>Try Simple Login</a></p>";
?>