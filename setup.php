<?php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "Setting up the project...\n";

// Create storage/logs directory
$logDir = __DIR__ . '/storage/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
    echo "Created storage/logs directory.\n";
}

// Copy .env.example to .env if .env doesn't exist
$envExample = __DIR__ . '/.env.example';
$envFile = __DIR__ . '/.env';
if (file_exists($envExample) && !file_exists($envFile)) {
    copy($envExample, $envFile);
    echo "Copied .env.example to .env.\n";
} elseif (file_exists($envFile)) {
    echo ".env file already exists, skipping.\n";
} else {
    echo "Warning: .env.example not found!\n";
}

echo "Setup complete! You can now configure your .env file.\n";
echo "To run the app via PHP's built-in server, use: php -S localhost:8000 -t public\n";
echo "Or place the project in your XAMPP htdocs directory and access via http://localhost/your-base-path\n";