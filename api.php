<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow local development / AJAX calls from static html files
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle CORS Preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

// Check action parameter from GET or POST payload
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Parse JSON input if content type is application/json or method is POST
$inputData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $parsedJson = json_decode($rawInput, true);
    if (is_array($parsedJson)) {
        $inputData = $parsedJson;
    } else {
        $inputData = $_POST;
    }
    if (isset($inputData['action'])) {
        $action = $inputData['action'];
    }
}

try {
    if ($action === 'categories') {
        $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
        exit;
    } elseif ($action === 'products') {
        $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        if ($categoryId > 0) {
            $stmt = $db->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY name ASC");
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $db->query("SELECT * FROM products ORDER BY name ASC");
        }
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        exit;
    } elseif ($action === 'inquiry') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Only POST requests are allowed for this action'
            ]);
            exit;
        }

        $name = isset($inputData['name']) ? trim($inputData['name']) : '';
        $email = isset($inputData['email']) ? trim($inputData['email']) : '';
        $phone = isset($inputData['phone']) ? trim($inputData['phone']) : '';
        $subject = isset($inputData['subject']) ? trim($inputData['subject']) : '';
        $message = isset($inputData['message']) ? trim($inputData['message']) : '';

        if (empty($name) || empty($email) || empty($message)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please fill in all required fields (Name, Email, Message)'
            ]);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please provide a valid email address'
            ]);
            exit;
        }

        // Save inquiry in database
        $stmt = $db->prepare("INSERT INTO inquiries (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $subject, $message]);

        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! We will get in touch with you shortly.'
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or missing action parameter'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>
