<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php'; // Include DB connection for stock check

// Load language
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
if (file_exists("lang/$lang_code.php")) {
    include "lang/$lang_code.php";
} else {
    include "lang/en.php";
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        // Check if product exists in cart before performing actions
        if (isset($_SESSION['cart'][$product_id])) {
            switch ($action) {
                case 'increase':
                    // --- STOCK CHECK START ---
                    $real_product_id = intval($product_id); // Extract ID from key (e.g. "123_L" -> 123)
                    
                    // Detect columns dynamically (copied from add_to_cart.php)
                    $cols = [];
                    $rc = $conn->query("SHOW COLUMNS FROM products");
                    if ($rc) { while ($r = $rc->fetch_assoc()) { $cols[$r['Field']] = true; } }

                    $idCol = 'Products_ID'; 
                    foreach (['Products_ID','products_id','product_id','id','Product_ID'] as $c) { if (isset($cols[$c])) { $idCol = $c; break; } }

                    $stockCol = 'Stock'; 
                    foreach (['Stock','stock','quantity','qty','Inventory','inventory','Status'] as $c) { if (isset($cols[$c])) { $stockCol = $c; break; } }

                    // Check stock
                    $stmt = $conn->prepare("SELECT $stockCol FROM products WHERE $idCol = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $real_product_id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($row = $res->fetch_assoc()) {
                            $available_stock = isset($row[$stockCol]) ? (int)$row[$stockCol] : 1000;
                            
                            // Calculate current total qty for this product in cart
                            $current_total_qty = 0;
                            foreach ($_SESSION['cart'] as $k => $item) {
                                // Extract ID from key $k to sum up across sizes
                                if (intval($k) == $real_product_id) {
                                    $current_total_qty += $item['quantity'];
                                }
                            }
                            
                            if ($available_stock < 900 && $current_total_qty + 1 > $available_stock) {
                                $msg = isset($lang['stock_limit_exceeded']) ? sprintf($lang['stock_limit_exceeded'], $available_stock) : "Sorry, only $available_stock items available in stock";
                                echo json_encode([
                                    'success' => false, 
                                    'message' => $msg
                                ]);
                                exit;
                            }
                        }
                    }
                    // --- STOCK CHECK END ---

                    $_SESSION['cart'][$product_id]['quantity']++;
                    break;
                
                case 'decrease':
                    if ($_SESSION['cart'][$product_id]['quantity'] > 1) {
                        $_SESSION['cart'][$product_id]['quantity']--;
                    } else {
                        unset($_SESSION['cart'][$product_id]);
                    }
                    break;
                
                case 'remove':
                    unset($_SESSION['cart'][$product_id]);
                    break;

                case 'apply_coupon':
                    $code = isset($_POST['coupon_code']) ? strtoupper(trim($_POST['coupon_code'])) : '';
                    if ($code === 'StyleMate20') {
                        // Check Membership
                        if (!isset($_SESSION['user_id'])) {
                            echo json_encode(['success' => false, 'message' => 'Please login to use this coupon']);
                            exit;
                        }
                        
                        $u_id = $_SESSION['user_id'];
                        $chk_sql = "SELECT is_premium, membership_expiry FROM users WHERE User_ID = ?";
                        $stmt = $conn->prepare($chk_sql);
                        $stmt->bind_param("i", $u_id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $is_member = false;
                        
                        if ($row = $res->fetch_assoc()) {
                            try {
                                if ($row['is_premium'] == 1 && new DateTime($row['membership_expiry']) > new DateTime()) {
                                    $is_member = true;
                                }
                            } catch (Exception $e) {
                                // Date parsing error
                            }
                        }
                        
                        if (!$is_member) {
                            echo json_encode(['success' => false, 'message' => 'This coupon is exclusive for StyleMate Club members']);
                            exit;
                        }

                        $_SESSION['coupon'] = [
                            'code' => 'StyleMate20',
                            'discount_percent' => 0.20,
                            'type' => 'percent'
                        ];
                        echo json_encode(['success' => true, 'message' => 'Coupon applied successfully!']);
                    } else {
                        unset($_SESSION['coupon']);
                        echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
                    }
                    exit;

                case 'remove_coupon':
                    unset($_SESSION['coupon']);
                    echo json_encode(['success' => true, 'message' => 'Coupon removed']);
                    exit;
            }
        } elseif ($action === 'apply_coupon') {
             // Handle coupon even if cart is empty or product_id not set
            $code = isset($_POST['coupon_code']) ? strtoupper(trim($_POST['coupon_code'])) : '';
            if ($code === 'StyleMate20') {
                // Check Membership
                if (!isset($_SESSION['user_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Please login to use this coupon']);
                    exit;
                }
                
                $u_id = $_SESSION['user_id'];
                $chk_sql = "SELECT is_premium, membership_expiry FROM users WHERE User_ID = ?";
                $stmt = $conn->prepare($chk_sql);
                $stmt->bind_param("i", $u_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $is_member = false;
                
                if ($row = $res->fetch_assoc()) {
                    try {
                        if ($row['is_premium'] == 1 && new DateTime($row['membership_expiry']) > new DateTime()) {
                            $is_member = true;
                        }
                    } catch (Exception $e) {
                        // Date parsing error
                    }
                }
                
                if (!$is_member) {
                    echo json_encode(['success' => false, 'message' => 'This coupon is exclusive for StyleMate Club members']);
                    exit;
                }

                $_SESSION['coupon'] = [
                    'code' => 'StyleMate20',
                    'discount_percent' => 0.20,
                    'type' => 'percent'
                ];
                echo json_encode(['success' => true, 'message' => 'Coupon applied successfully!']);
            } else {
                unset($_SESSION['coupon']);
                echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
            }
            exit;
        } elseif ($action === 'remove_coupon') {
            unset($_SESSION['coupon']);
            echo json_encode(['success' => true, 'message' => 'Coupon removed']);
            exit;
        }

        // Calculate new cart count
        $cart_count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'cartCount' => $cart_count,
            'message' => 'Cart updated successfully'
        ]);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => isset($lang['invalid_request_method']) ? $lang['invalid_request_method'] : 'Invalid request method'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating cart: ' . $e->getMessage()
    ]);
}
?>


