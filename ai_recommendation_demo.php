<?php
session_start();
require_once 'db_connect.php';

// Default product to show recommendation for (e.g., Product ID 5)
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 5;

// 1. Fetch all products for the AI to analyze
$sql = "SELECT Products_ID, Products_Name, Categories_ID, Subcategories_ID, price, image FROM products WHERE Status = 1";
$result = $conn->query($sql);
$all_products = [];
while ($row = $result->fetch_assoc()) {
    $all_products[] = $row;
}

// 2. Call Python Script for AI Recommendations
$products_json = json_encode($all_products);
$command = "python3 recommend.py " . escapeshellarg($products_json) . " " . $target_id;
$output = shell_exec($command);
$recommended_ids = json_decode($output, true);

// 3. Fetch details for the target product
$target_stmt = $conn->prepare("SELECT * FROM products WHERE Products_ID = ?");
$target_stmt->bind_param("i", $target_id);
$target_stmt->execute();
$target_product = $target_stmt->get_result()->fetch_assoc();

// 4. Fetch details for recommended products
$recommended_products = [];
if (!empty($recommended_ids)) {
    $ids_placeholders = implode(',', array_fill(0, count($recommended_ids), '?'));
    $rec_stmt = $conn->prepare("SELECT * FROM products WHERE Products_ID IN ($ids_placeholders)");
    $rec_stmt->bind_param(str_repeat('i', count($recommended_ids)), ...$recommended_ids);
    $rec_stmt->execute();
    $rec_res = $rec_stmt->get_result();
    while ($row = $rec_res->fetch_assoc()) {
        $recommended_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Recommendation Demo | StyleMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f9; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; }
        .demo-header { text-align: center; margin-bottom: 40px; }
        .demo-header h1 { color: #232f3e; margin-bottom: 10px; }
        .ai-badge { background: #4b6cb7; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px; font-weight: bold; }
        
        .main-product { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; gap: 40px; margin-bottom: 50px; }
        .product-img { width: 300px; height: 300px; object-fit: contain; background: #f9f9f9; border-radius: 8px; }
        .product-info h2 { margin-top: 0; color: #232f3e; }
        .price { font-size: 24px; color: #B12704; font-weight: bold; margin: 15px 0; }
        
        .recommendation-title { font-size: 20px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: #232f3e; }
        .recommendation-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        
        .rec-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; transition: transform 0.2s; border: 1px solid transparent; }
        .rec-card:hover { transform: translateY(-5px); border-color: #4b6cb7; }
        .rec-img { width: 100%; height: 180px; object-fit: contain; margin-bottom: 10px; }
        .rec-name { font-size: 14px; font-weight: 600; color: #333; height: 40px; overflow: hidden; }
        .rec-price { color: #B12704; font-weight: bold; margin-top: 10px; }
        .pair-icon { color: #4b6cb7; margin: 0 10px; }
        
        .ai-logic { background: #eef2ff; padding: 20px; border-radius: 8px; margin-top: 40px; border-left: 5px solid #4b6cb7; }
        .ai-logic h3 { margin-top: 0; font-size: 16px; color: #4b6cb7; }
        .ai-logic p { font-size: 14px; color: #555; margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="demo-header">
            <span class="ai-badge"><i class="fas fa-robot"></i> AI POWERED</span>
            <h1>Product Recommendation Demo</h1>
            <p>Our Python AI analyzed your catalog to find these matching pairs</p>
        </div>

        <?php if ($target_product): ?>
        <div class="main-product">
            <img src="images/products/<?php echo $target_product['image']; ?>" class="product-img" alt="Main Product" onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
            <div class="product-info">
                <span style="color: #666; text-transform: uppercase; font-size: 12px;">Currently Viewing</span>
                <h2><?php echo htmlspecialchars($target_product['Products_Name']); ?></h2>
                <div class="price">₹<?php echo number_format($target_product['price'], 2); ?></div>
                <p style="color: #555;"><?php echo htmlspecialchars($target_product['Description']); ?></p>
                <button style="background: #f0c14b; border: 1px solid #a88734; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add to Cart</button>
            </div>
        </div>

        <div class="recommendation-title">
            <i class="fas fa-magic" style="color: #4b6cb7;"></i>
            AI Recommended Pairs
        </div>

        <div class="recommendation-grid">
            <?php if (!empty($recommended_products)): ?>
                <?php foreach ($recommended_products as $rec): ?>
                <div class="rec-card">
                    <img src="images/products/<?php echo $rec['image']; ?>" class="rec-img" alt="Rec" onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                    <div class="rec-name"><?php echo htmlspecialchars($rec['Products_Name']); ?></div>
                    <div class="rec-price">₹<?php echo number_format($rec['price'], 2); ?></div>
                    <div style="margin-top: 15px;">
                        <span style="font-size: 12px; color: #4b6cb7; font-weight: bold;">
                            <i class="fas fa-link"></i> PERFECT MATCH
                        </span>
                    </div>
                    <a href="ai_recommendation_demo.php?id=<?php echo $rec['Products_ID']; ?>" style="display: block; margin-top: 10px; font-size: 12px; color: #0066c0; text-decoration: none;">View Details</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recommendations found for this item.</p>
            <?php endif; ?>
        </div>

        <div class="ai-logic">
            <h3><i class="fas fa-brain"></i> How the AI works (Python Logic)</h3>
            <p>The Python backend uses <strong>Content-Based Filtering</strong>. It analyzes the target product's Category ID and Name keywords, then scans the entire database to calculate a "Similarity Score". The items with the highest scores are suggested as pairs to increase your average order value.</p>
        </div>
        <?php else: ?>
            <p>Product not found.</p>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 40px;">
            <a href="index.php" style="color: #232f3e; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Store</a>
        </div>
    </div>
</body>
</html>
