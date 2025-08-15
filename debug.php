<?php
// Minimal debug test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Test</h1>";
echo "PHP is working!<br>";

// Test 1: Basic file existence
echo "<h3>File Existence Check:</h3>";
$files = [
    'config/config.php',
    'config/database.php', 
    'classes/Database.php',
    'classes/User.php',
    'includes/functions.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} exists<br>";
    } else {
        echo "❌ {$file} MISSING<br>";
    }
}

// Test 2: Try including config
echo "<h3>Config Test:</h3>";
try {
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
        echo "✅ Config loaded successfully<br>";
    } else {
        echo "❌ Config file missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 3: Database config
echo "<h3>Database Config Test:</h3>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        echo "✅ Database config loaded<br>";
        
        // Test if getDB function exists
        if (function_exists('getDB')) {
            echo "✅ getDB function exists<br>";
            $pdo = getDB();
            echo "✅ Database connection successful<br>";
        } else {
            echo "❌ getDB function not found<br>";
        }
    } else {
        echo "❌ Database config missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>PHP Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current directory: " . getcwd() . "<br>";
echo "Script path: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
?>