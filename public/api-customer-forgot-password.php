<?php
// Workaround endpoint for forgot password
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/../vendor/autoload.php';

// Load .env file
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\Controllers\Customer\AuthController;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    error_log("[ForgotPassword API] Creating controller...");
    $controller = new AuthController();

    error_log("[ForgotPassword API] Calling forgotPassword method...");
    $controller->forgotPassword();

    error_log("[ForgotPassword API] Method completed successfully");
} catch (Exception $e) {
    error_log("[ForgotPassword API] Exception caught: " . $e->getMessage());
    error_log("[ForgotPassword API] Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ]);
} catch (Error $e) {
    error_log("[ForgotPassword API] Fatal error: " . $e->getMessage());
    error_log("[ForgotPassword API] Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
