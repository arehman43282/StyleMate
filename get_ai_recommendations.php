<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$log_file = 'debug_ai_log.txt';
function log_debug($msg) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
log_debug("Request for Product ID: $product_id");

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Missing product ID']);
    exit;
}

// 1. Fetch target product info
$stmt = $conn->prepare("SELECT p.Products_Name, p.Description, c.Categories_name, c.Category_Group, s.Subcategories_name 
                        FROM products p 
                        JOIN categories c ON p.Categories_ID = c.Categories_ID 
                        LEFT JOIN subcategories s ON p.Subcategories_ID = s.Subcategories_ID 
                        WHERE p.Products_ID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$target_product = $result->fetch_assoc();
$group = $target_product['Category_Group']; // e.g. "Men", "Women"
$category = strtolower($target_product['Categories_name']);
$subcategory = strtolower($target_product['Subcategories_name'] ?? '');
$name = strtolower($target_product['Products_Name']);
$is_men = (strtolower(trim($group)) === 'men');

log_debug("Category: $category, Subcategory: $subcategory, Name: $name, Group: $group");

$recommendations = [];
$exclude_ids = [$product_id];
$required_types = [];
$clothes_only_mode = false;

// ---------------------------------------------------------
// RULE-BASED LOGIC (Ported from Python to PHP)
// ---------------------------------------------------------

if ($is_men) {
    if (strpos($category, 'watch') !== false || strpos($name, 'watch') !== false) {
        $required_types = ['t-shirt', 'jeans', 'sport', 'jacket'];
    } elseif (strpos($category, 'sport') !== false || strpos($name, 'sport') !== false || strpos($name, 'sneaker') !== false) {
        $required_types = ['jeans', 't-shirt', 'jacket', 'watch'];
    } elseif (strpos($category, 'belt') !== false || strpos($name, 'belt') !== false) {
        $required_types = ['trouser', 'shirt', 'watch', 'jacket'];
    } elseif (strpos($category, 'jacket') !== false || strpos($name, 'jacket') !== false) {
        $required_types = ['t-shirt', 'jeans', 'sport', 'watch'];
    } elseif (strpos($category, 't-shirt') !== false || strpos($name, 't-shirt') !== false || strpos($name, 'tee') !== false) {
        $required_types = ['jeans', 'sport', 'belt', 'watch'];
    } elseif ((strpos($name, 'formal') !== false && strpos($name, 'shirt') !== false) || (strpos($name, 'shirt') !== false && strpos($name, 't-shirt') === false && strpos($name, 'tee') === false && strpos($category, 'topwear') !== false)) {
        // HIT FORMAL SHIRT
        // Recommend: 1 Formal Trouser, 1 Watch, 1 Belt, 1 Formal Shoe
        $required_types = ['trouser', 'watch', 'belt', 'formal shoe'];
    } elseif (strpos($category, 'jeans') !== false || strpos($name, 'jeans') !== false || strpos($name, 'denim') !== false) {
        // HIT JEANS
        // Recommend: 1 T-shirt, 1 Watch, 1 Jacket, 1 Sport Shoes
        $required_types = ['t-shirt', 'watch', 'jacket', 'sport'];
    } else {
        $required_types = ['jeans', 't-shirt', 'watch', 'jacket'];
    }
} else {
    // Women's Logic
    if (strpos($category, 'jacket') !== false || strpos($name, 'jacket') !== false) {
        $required_types = ['t-shirt', 'jeans', 'accessory', 'sport'];
    } elseif (strpos($category, 'saree') !== false || strpos($name, 'saree') !== false) {
        // HIT SAREE
        $required_types = ['anklet', 'bracelet', 'earring', 'heels'];
    } elseif (
        strpos($category, 'dress') !== false || 
        strpos($subcategory, 'dress') !== false || 
        strpos($name, 'dress') !== false || 
        strpos($name, 'gown') !== false ||
        strpos($name, 'one piece') !== false ||
        (strpos($category, 'western') !== false && (strpos($subcategory, 'dress') !== false || strpos($name, 'dress') !== false))
    ) {
        // HIT DRESSES / WESTERN DRESSES (User Request: 1 Anklet, 1 Bracelet, 1 Earring, 1 Heels)
        log_debug("Hit Dress Rule");
        $required_types = ['anklet', 'bracelet', 'earring', 'heels'];
    } elseif (strpos($category, 'kurta') !== false || strpos($name, 'kurta') !== false || strpos($category, 'suit') !== false || strpos($name, 'suit') !== false) {
        // HIT KURTA
        $required_types = ['anklet', 'bracelet', 'earring', 'heels'];
    } elseif (strpos($category, 'footwear') !== false || strpos($name, 'shoe') !== false || strpos($name, 'heel') !== false || strpos($name, 'sandal') !== false || strpos($name, 'flat') !== false || strpos($name, 'wedge') !== false) {
        // HIT FOOTWEAR (Heels, Flats, Sports Shoes, etc.)
        // Recommend CLOTHES (Saree, Kurta, Dresses, Jeans) - Not accessories
        $required_types = ['saree', 'kurta', 'dresses', 'jeans', 't-shirt', 'jacket'];
        $clothes_only_mode = true;
    } elseif (strpos($category, 'accessory') !== false || strpos($category, 'jewellery') !== false || strpos($name, 'earring') !== false || strpos($name, 'bracelet') !== false || strpos($name, 'anklet') !== false) {
        // HIT ACCESSORY
        if (strpos($name, 'earring') !== false) {
             $required_types = ['saree', 'kurta', 'bracelet', 'heels'];
        } elseif (strpos($name, 'bracelet') !== false) {
             $required_types = ['saree', 'earring', 'anklet', 'heels'];
        } elseif (strpos($name, 'anklet') !== false) {
             $required_types = ['saree', 'heels', 'earring', 'kurta'];
        } else {
             $required_types = ['saree', 'heels', 'earring', 'bracelet'];
        }
    } else {
        // Default women's fallback (can be expanded later)
        log_debug("Hit Default Fallback");
        $required_types = ['jeans', 't-shirt', 'accessory', 'sport'];
    }
}

log_debug("Required types: " . implode(', ', $required_types));

// Process required types
foreach ($required_types as $type) {
    if (count($recommendations) >= 4) break;

    $sql = "SELECT p.Products_ID, p.Products_Name, p.price, p.image 
            FROM products p 
            JOIN categories c ON p.Categories_ID = c.Categories_ID 
            LEFT JOIN subcategories s ON p.Subcategories_ID = s.Subcategories_ID 
            WHERE p.Status = 1 
            AND c.Category_Group = ? 
            AND p.Products_ID NOT IN (" . implode(',', $exclude_ids) . ") ";
    
    // Add specific filters
    if ($type == 't-shirt') {
        $sql .= "AND (p.Products_Name LIKE '%t-shirt%' OR p.Products_Name LIKE '%tee%' OR c.Categories_name LIKE '%t-shirt%') ";
    } elseif ($type == 'shirt') {
        $sql .= "AND (p.Products_Name LIKE '%shirt%' OR c.Categories_name LIKE '%shirt%') 
                 AND p.Products_Name NOT LIKE '%t-shirt%' AND p.Products_Name NOT LIKE '%tee%' ";
    }

    // STRICT EXCLUSION FOR FOOTWEAR MODE
    if ($clothes_only_mode) {
        $sql .= "AND c.Categories_name NOT LIKE '%Accessory%' 
                 AND c.Categories_name NOT LIKE '%Footwear%' 
                 AND p.Products_Name NOT LIKE '%shoe%' 
                 AND p.Products_Name NOT LIKE '%heel%' 
                 AND p.Products_Name NOT LIKE '%sandal%' 
                 AND p.Products_Name NOT LIKE '%flat%' 
                 AND p.Products_Name NOT LIKE '%wedge%' 
                 AND p.Products_Name NOT LIKE '%earring%' 
                 AND p.Products_Name NOT LIKE '%bracelet%' 
                 AND p.Products_Name NOT LIKE '%anklet%' 
                 AND p.Products_Name NOT LIKE '%watch%' 
                 AND p.Products_Name NOT LIKE '%belt%' ";
    }
    
    if ($type == 'jeans') {
        $sql .= "AND (p.Products_Name LIKE '%jeans%' OR c.Categories_name LIKE '%jeans%' OR p.Products_Name LIKE '%denim%') ";
    } elseif ($type == 'sport') {
        $sql .= "AND (p.Products_Name LIKE '%sport%' OR p.Products_Name LIKE '%sneaker%' OR p.Products_Name LIKE '%running%') ";
    } elseif ($type == 'jacket') {
        $sql .= "AND (p.Products_Name LIKE '%jacket%' OR c.Categories_name LIKE '%jacket%' OR p.Products_Name LIKE '%blazer%') ";
    } elseif ($type == 'watch') {
        $sql .= "AND (p.Products_Name LIKE '%watch%' OR c.Categories_name LIKE '%watch%') ";
    } elseif ($type == 'belt') {
        $sql .= "AND (p.Products_Name LIKE '%belt%' OR c.Categories_name LIKE '%belt%') ";
    } elseif ($type == 'trouser') {
        $sql .= "AND (p.Products_Name LIKE '%trouser%' OR p.Products_Name LIKE '%pant%' OR c.Categories_name LIKE '%trouser%') ";
    } elseif ($type == 'formal shoe') {
        $sql .= "AND (p.Products_Name LIKE '%formal%' AND (p.Products_Name LIKE '%shoe%' OR c.Categories_name LIKE '%footwear%')) ";
    } elseif ($type == 'accessory') {
        $sql .= "AND (p.Products_Name LIKE '%earring%' OR c.Categories_name LIKE '%earring%' 
                  OR p.Products_Name LIKE '%bracelet%' OR c.Categories_name LIKE '%bracelet%' 
                  OR p.Products_Name LIKE '%anklet%' OR c.Categories_name LIKE '%anklet%' 
                  OR p.Products_Name LIKE '%watch%' OR c.Categories_name LIKE '%watch%'
                  OR p.Products_Name LIKE '%belt%' OR c.Categories_name LIKE '%belt%'
                  OR c.Categories_name LIKE '%accessories%') ";
    } elseif ($type == 'anklet') {
        $sql .= "AND (p.Products_Name LIKE '%anklet%' OR c.Categories_name LIKE '%anklet%' OR s.Subcategories_name LIKE '%anklet%') ";
    } elseif ($type == 'bracelet') {
        $sql .= "AND (p.Products_Name LIKE '%bracelet%' OR c.Categories_name LIKE '%bracelet%' OR s.Subcategories_name LIKE '%bracelet%' OR p.Products_Name LIKE '%bangle%') ";
    } elseif ($type == 'earring') {
        $sql .= "AND (p.Products_Name LIKE '%earring%' OR c.Categories_name LIKE '%earring%' OR s.Subcategories_name LIKE '%earring%' OR p.Products_Name LIKE '%jhumka%') ";
    } elseif ($type == 'heels') {
        $sql .= "AND (p.Products_Name LIKE '%heels%' OR s.Subcategories_name LIKE '%heels%' OR c.Categories_name LIKE '%heels%' OR p.Products_Name LIKE '%wedge%' OR p.Products_Name LIKE '%sandal%') ";
    } elseif ($type == 'saree') {
        $sql .= "AND (p.Products_Name LIKE '%saree%' OR c.Categories_name LIKE '%saree%' OR s.Subcategories_name LIKE '%saree%') ";
    } elseif ($type == 'kurta') {
        $sql .= "AND (p.Products_Name LIKE '%kurta%' OR c.Categories_name LIKE '%kurta%' OR s.Subcategories_name LIKE '%kurta%' OR p.Products_Name LIKE '%suit%') ";
    } elseif ($type == 'dresses') {
        $sql .= "AND (p.Products_Name LIKE '%dress%' OR c.Categories_name LIKE '%dress%' OR s.Subcategories_name LIKE '%dress%') ";
    }

    $sql .= "ORDER BY RAND() LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($item = $res->fetch_assoc()) {
        log_debug("Found match for $type: " . $item['Products_Name']);
        $recommendations[] = [
            'id' => $item['Products_ID'],
            'name' => $item['Products_Name'],
            'price' => number_format($item['price'], 2),
            'image' => 'images/products/' . ($item['image'] ?: 'placeholder.svg'),
            'href' => 'product_detail.php?id=' . $item['Products_ID']
        ];
        $exclude_ids[] = $item['Products_ID'];
    } else {
        log_debug("No match for $type");
    }
}

// Fill remaining slots with random items from same group
while (count($recommendations) < 4) {
    log_debug("Filling fallback slot");
    $sql = "SELECT p.Products_ID, p.Products_Name, p.price, p.image 
            FROM products p 
            JOIN categories c ON p.Categories_ID = c.Categories_ID 
            WHERE p.Status = 1 
            AND c.Category_Group = ? 
            AND p.Products_ID NOT IN (" . implode(',', $exclude_ids) . ") ";

    if ($clothes_only_mode) {
        $sql .= "AND c.Categories_name NOT LIKE '%Accessory%' 
                 AND c.Categories_name NOT LIKE '%Footwear%' 
                 AND p.Products_Name NOT LIKE '%shoe%' 
                 AND p.Products_Name NOT LIKE '%heel%' 
                 AND p.Products_Name NOT LIKE '%sandal%' 
                 AND p.Products_Name NOT LIKE '%flat%' 
                 AND p.Products_Name NOT LIKE '%wedge%' 
                 AND p.Products_Name NOT LIKE '%earring%' 
                 AND p.Products_Name NOT LIKE '%bracelet%' 
                 AND p.Products_Name NOT LIKE '%anklet%' 
                 AND p.Products_Name NOT LIKE '%watch%' 
                 AND p.Products_Name NOT LIKE '%belt%' ";
    }
            
    $sql .= "ORDER BY RAND() LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    
    if (!$item) break;
    
    $image_url = $item['image'];
    if (strpos($image_url, 'http') !== 0) {
        $image_url = 'images/products/' . ($image_url ?: 'placeholder.svg');
    }

    $recommendations[] = [
        'id' => $item['Products_ID'],
        'name' => $item['Products_Name'],
        'price' => number_format($item['price'], 2),
        'image' => $image_url,
        'stock' => (int)($item['stock'] ?? 1000), // Default to 1000 if not found
        'href' => 'product_detail.php?id=' . $item['Products_ID']
    ];
    $exclude_ids[] = $item['Products_ID'];
}

echo json_encode(['success' => true, 'recommendations' => $recommendations]);
?>