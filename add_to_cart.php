<?php
ob_start(); // Start buffering immediately to catch any unwanted output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'cart_debug.log');

function log_cart($msg) {
    error_log("[Cart Debug] " . $msg);
}

// Clear buffer before sending JSON
ob_end_clean();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception($lang['please_login_alert']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $name = $_POST['name'] ?? $lang['unknown_product'];
        $price = $_POST['price'] ?? 0;
        $size = isset($_POST['size']) ? $_POST['size'] : '';

        if ($product_id <= 0) {
            throw new Exception("Invalid product ID: $product_id");
        }

        // Detect columns dynamically
        $cols = [];
        $rc = $conn->query("SHOW COLUMNS FROM products");
        if ($rc) { while ($r = $rc->fetch_assoc()) { $cols[$r['Field']] = true; } }

        $idCol = 'Products_ID'; // Default
        foreach (['Products_ID','products_id','product_id','id','Product_ID'] as $c) { if (isset($cols[$c])) { $idCol = $c; break; } }

        $stockCol = 'Stock'; // Default
        foreach (['Stock','stock','quantity','qty','Inventory','inventory','Status'] as $c) { if (isset($cols[$c])) { $stockCol = $c; break; } }

        // Check stock availability
        $stmt = $conn->prepare("SELECT $stockCol FROM products WHERE $idCol = ?");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception($lang['product_not_found_db']);
        }

        $product = $result->fetch_assoc();
        // If stock is NULL, assume unlimited (or high number)
        $available_stock = isset($product[$stockCol]) ? (int)$product[$stockCol] : 1000;

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        // Calculate current quantity in cart for this product (across all sizes)
        $current_qty_in_cart = 0;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['product_id'] == $product_id) {
                $current_qty_in_cart += $item['quantity'];
            }
        }

        // Only enforce stock limit if stock is tracked (e.g., < 900 to exclude dummy 1000)
        if ($available_stock < 900 && $current_qty_in_cart + 1 > $available_stock) {
            $msg = isset($lang['stock_limit_exceeded']) ? sprintf($lang['stock_limit_exceeded'], $available_stock) : "Sorry, only $available_stock items available in stock";
            throw new Exception($msg);
        }

        $cart_key = $product_id . ($size ? '_' . $size : '');

        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity']++;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'product_id' => $product_id,
                'name' => $name,
                'price' => $price,
                'size' => $size,
                'quantity' => 1
            ];
        }

        $cart_count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'cartCount' => $cart_count,
            'message' => $lang['added_to_cart_success']
        ]);
        exit;
    }

    throw new Exception($lang['invalid_request_method']);

} catch (Exception $e) {
    log_cart("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>