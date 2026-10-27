<?php
session_start();
require_once 'db_connect.php';

// Mock product ID that definitely exists and has stock
// I'll pick a product ID from wishlist.php search results if I could, but I'll assume ID 1 exists or I'll find one.
// Let's first find a product and its stock.

$result = $conn->query("SELECT Products_ID, Stock FROM products LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $pid = $row['Products_ID'];
    $stock = $row['Stock'];
    echo "Product ID: $pid, Stock: $stock<br>";
    
    // Clear cart for this test
    unset($_SESSION['cart']);
    
    // Try to add stock + 1 times
    for ($i = 0; $i <= $stock + 1; $i++) {
        // Logic from add_to_cart.php
        $current_qty = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if ($item['product_id'] == $pid) {
                    $current_qty += $item['quantity'];
                }
            }
        }
        
        echo "Attempt " . ($i+1) . ": Current Qty in Cart: $current_qty. ";
        
        if ($current_qty + 1 > $stock) {
            echo "BLOCKED. Limit reached.<br>";
        } else {
            echo "ALLOWED. Adding to cart.<br>";
            // Simulate adding
            $cart_key = $pid . '_M';
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['quantity']++;
            } else {
                $_SESSION['cart'][$cart_key] = ['product_id' => $pid, 'quantity' => 1];
            }
        }
    }
} else {
    echo "No products found.";
}
?>