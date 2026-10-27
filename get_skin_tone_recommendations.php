<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$skin_tone = isset($_GET['skin_tone']) ? $_GET['skin_tone'] : '';
$gender = isset($_GET['gender']) ? strtolower($_GET['gender']) : '';

// Skin tone to color mapping/logic
$color_filters = [
    'Fair' => ['Blue', 'Pink', 'Lavender', 'Silver', 'Pastel'],
    'Light' => ['Peach', 'Teal', 'Gold', 'Sage', 'Coral'],
    'Medium' => ['Olive', 'Orange', 'Mustard', 'Gold', 'Rust', 'Metallic'],
    'Tan' => ['White', 'Turquoise', 'Yellow', 'Coral', 'Amber'],
    'Deep' => ['Fuchsia', 'Silver', 'Yellow', 'Royal Blue', 'Purple', 'Bright Red']
];

function fetch_products($conn, $gender, $skin_tone, $color_filters, $type = 'clothing') {
    $sql = "SELECT p.Products_ID, p.Products_Name, p.price as Price, p.image as Image_URL, c.Category_Group, c.Categories_name 
            FROM products p 
            JOIN categories c ON p.Categories_ID = c.Categories_ID 
            WHERE p.Status = 1";

    // Gender Logic
    if ($gender) {
        if (in_array($gender, ['female', 'women', 'girl', 'girls'])) {
            $sql .= " AND c.Category_Group IN ('Women', 'Girls')";
            
            if ($type === 'clothing') {
                $sql .= " AND c.Categories_name IN ('Western Wear', 'Indian & Fusion wear', 'Girls Clothing')";
            } else {
                $sql .= " AND c.Categories_name IN ('Accessories', 'Footwear', 'Fashion Accessories')";
            }
        } elseif (in_array($gender, ['male', 'men', 'boy', 'boys'])) {
            $sql .= " AND c.Category_Group IN ('Men', 'Boys')";
            
            if ($type === 'clothing') {
                $sql .= " AND c.Categories_name IN ('Topwear', 'Bottomwear', 'Boys Clothing')";
            } else {
                $sql .= " AND c.Categories_name IN ('Fashion Accessories', 'Footwear', 'Boys Accessories')";
            }
        }
    }

    // Color Logic
    if ($skin_tone && isset($color_filters[$skin_tone])) {
        $filters = $color_filters[$skin_tone];
        $color_conditions = [];
        foreach ($filters as $color) {
            $color_conditions[] = "p.Products_Name LIKE '%$color%'";
            $color_conditions[] = "p.Description LIKE '%$color%'";
        }
        $sql .= " AND (" . implode(" OR ", $color_conditions) . ")";
    }

    $sql .= " ORDER BY RAND() LIMIT 15";
    
    $result = $conn->query($sql);
    $items = [];
    $ids = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
            $ids[] = $row['Products_ID'];
        }
    }

    // Fallback for clothing if matches are too few
    if ($type === 'clothing' && count($items) < 6) {
        $needed = 6 - count($items);
        $fallback_sql = "SELECT p.Products_ID, p.Products_Name, p.price as Price, p.image as Image_URL, c.Category_Group, c.Categories_name 
            FROM products p 
            JOIN categories c ON p.Categories_ID = c.Categories_ID 
            WHERE p.Status = 1";
        
        // Apply gender/category filter again
        if ($gender) {
            if (in_array($gender, ['female', 'women', 'girl', 'girls'])) {
                $fallback_sql .= " AND c.Category_Group IN ('Women', 'Girls') AND c.Categories_name IN ('Western Wear', 'Indian & Fusion wear', 'Girls Clothing')";
            } elseif (in_array($gender, ['male', 'men', 'boy', 'boys'])) {
                $fallback_sql .= " AND c.Category_Group IN ('Men', 'Boys') AND c.Categories_name IN ('Topwear', 'Bottomwear', 'Boys Clothing')";
            }
        }

        // Exclude already found
        if (!empty($ids)) {
            $fallback_sql .= " AND p.Products_ID NOT IN (" . implode(',', $ids) . ")";
        }

        $fallback_sql .= " ORDER BY RAND() LIMIT $needed";
        $fb_result = $conn->query($fallback_sql);
        if ($fb_result) {
            while ($row = $fb_result->fetch_assoc()) {
                $items[] = $row;
            }
        }
    }

    return $items;
}

$clothing = fetch_products($conn, $gender, $skin_tone, $color_filters, 'clothing');
$accessories = fetch_products($conn, $gender, $skin_tone, $color_filters, 'accessories');

// Generate Outfits (Combinations)
$outfits = [];
$gender_lower = strtolower($gender);
$is_female = in_array($gender_lower, ['female', 'women', 'girl', 'girls']);

if ($is_female) {
    // Outfit 1: Dress/Top + Accessory
    $main = null;
    foreach ($clothing as $c) {
        if (strpos($c['Categories_name'], 'Western') !== false || strpos($c['Categories_name'], 'Indian') !== false) {
            $main = $c; break;
        }
    }
    $acc = null;
    foreach ($accessories as $a) {
        if (strpos($a['Categories_name'], 'Accessories') !== false) {
            $acc = $a; break;
        }
    }
    if ($main && $acc) {
        $outfits[] = [
            'title' => 'The Elegant Look',
            'items' => [$main, $acc]
        ];
    }

    // Outfit 2: Clothing + Footwear
    $main2 = null;
    foreach ($clothing as $c) {
        if ($c !== $main) { $main2 = $c; break; }
    }
    $foot = null;
    foreach ($accessories as $a) {
        if (strpos($a['Categories_name'], 'Footwear') !== false) {
            $foot = $a; break;
        }
    }
    if ($main2 && $foot) {
        $outfits[] = [
            'title' => 'Chic & Comfortable',
            'items' => [$main2, $foot]
        ];
    }
} else {
    // Outfit 1: Topwear + Bottomwear
    $top = null;
    foreach ($clothing as $c) {
        if (strpos($c['Categories_name'], 'Topwear') !== false) {
            $top = $c; break;
        }
    }
    $bottom = null;
    foreach ($clothing as $c) {
        if (strpos($c['Categories_name'], 'Bottomwear') !== false) {
            $bottom = $c; break;
        }
    }
    if ($top && $bottom) {
        $outfits[] = [
            'title' => 'Perfect Coordination',
            'items' => [$top, $bottom]
        ];
    }

    // Outfit 2: Topwear + Footwear
    $top2 = null;
    foreach ($clothing as $c) {
        if (strpos($c['Categories_name'], 'Topwear') !== false && $c !== $top) {
            $top2 = $c; break;
        }
    }
    if (!$top2) $top2 = $top;

    $foot = null;
    foreach ($accessories as $a) {
        if (strpos($a['Categories_name'], 'Footwear') !== false) {
            $foot = $a; break;
        }
    }
    if ($top2 && $foot) {
        $outfits[] = [
            'title' => 'Casual Smart',
            'items' => [$top2, $foot]
        ];
    }
}

// Merge and Shuffle for individual picks
$recommendations = array_merge($clothing, $accessories);
shuffle($recommendations);

echo json_encode([
    'success' => true, 
    'recommendations' => $recommendations,
    'outfits' => $outfits
]);
?>
