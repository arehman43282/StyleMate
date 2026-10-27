<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Detect columns dynamically
$cols = [];
$rc = $conn->query("SHOW COLUMNS FROM products");
if ($rc) { while ($r = $rc->fetch_assoc()) { $cols[$r['Field']] = true; } }

$idCol = null;
foreach (['Products_ID','products_id','product_id','id','Product_ID'] as $c) { if (isset($cols[$c])) { $idCol = $c; break; } }
$nameCol = null;
foreach (['Products_Name','name','Name','product_name'] as $c) { if (isset($cols[$c])) { $nameCol = $c; break; } }
$priceCol = null;
foreach (['Price','price','product_price','amount','rate','Cost','cost'] as $c) { if (isset($cols[$c])) { $priceCol = $c; break; } }
$imgCol = null;
foreach (['image','image_url','product_image','img','image_path','Image'] as $c) { if (isset($cols[$c])) { $imgCol = $c; break; } }
$stockCol = null;
foreach (['Stock','stock','quantity','qty','Inventory','inventory','Status'] as $c) { if (isset($cols[$c])) { $stockCol = $c; break; } }
$groupCol = null;
foreach (['Category_Group','category_group','product_group','group'] as $c) { if (isset($cols[$c])) { $groupCol = $c; break; } }
$categoryCol = null;
foreach (['Categories_ID','category','Category','Product_Category'] as $c) { if (isset($cols[$c])) { $categoryCol = $c; break; } }
$descCol = null;
foreach (['Description','description','product_desc','detail','Detail'] as $c) { if (isset($cols[$c])) { $descCol = $c; break; } }
$brandCol = null;
foreach (['Brand','brand','Company','company','Manufacturer','manufacturer'] as $c) { if (isset($cols[$c])) { $brandCol = $c; break; } }
$colorCol = null;
foreach (['Color','color','colour','Colour'] as $c) { if (isset($cols[$c])) { $colorCol = $c; break; } }
$materialCol = null;
foreach (['Material','material','fabric','Fabric'] as $c) { if (isset($cols[$c])) { $materialCol = $c; break; } }
$patternCol = null;
foreach (['Pattern','pattern','design','Design'] as $c) { if (isset($cols[$c])) { $patternCol = $c; break; } }
$careCol = null;
foreach (['Care_Instructions','care_instructions','wash_care','Wash_Care'] as $c) { if (isset($cols[$c])) { $careCol = $c; break; } }
$sizeSpecCol = null;
foreach (['Sizes','sizes','Size','size','dimensions','Dimensions'] as $c) { if (isset($cols[$c])) { $sizeSpecCol = $c; break; } }
$fitCol = null;
foreach (['Fit_Type','fit_type','Fit','fit'] as $c) { if (isset($cols[$c])) { $fitCol = $c; break; } }

$where = [];
if ($idCol) { $where[] = "p.$idCol = $product_id"; }
$sql = "SELECT p.*, c.Categories_name, c.Category_Group 
        FROM products p 
        LEFT JOIN categories c ON p.Categories_ID = c.Categories_ID" . 
        (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
$result = mysqli_query($conn, $sql);
$product = $result ? mysqli_fetch_assoc($result) : null;

if (!$product) {
    require_once 'header.php';
    echo "<div class='product-not-found-container'>
            <h1 class='product-not-found-title'>Product not found</h1>
            <p class='product-not-found-text'>The product you are looking for does not exist or has been removed.</p>
            <a href='index.php' class='product-not-found-btn'>Back to Home</a>
          </div>";
    require_once 'footer.php';
    exit;
}

// Normalize keys
if ($product) {
    if ($nameCol && !isset($product['name'])) { $product['name'] = $product[$nameCol]; }
    if ($priceCol && !isset($product['price'])) { $product['price'] = $product[$priceCol]; }
    if ($imgCol && !isset($product['image_url'])) { $product['image_url'] = $product[$imgCol]; }
    if ($idCol && !isset($product['products_id'])) { $product['products_id'] = $product[$idCol]; }
    if ($stockCol && !isset($product['stock'])) { $product['stock'] = $product[$stockCol]; }
    if ($descCol && !isset($product['description'])) { $product['description'] = $product[$descCol]; }
    if ($brandCol && !isset($product['brand'])) { $product['brand'] = $product[$brandCol]; }
    
    // Size normalization - check multiple possible columns
    $size_val = '';
    foreach (['Sizes','sizes','Size','size','dimensions','Dimensions'] as $c) {
        if (!empty($product[$c])) {
            $size_val = $product[$c];
            break;
        }
    }
    $product['size'] = $size_val;
    
    if (isset($product['Categories_name'])) {
        $product['category'] = $product['Categories_name'];
    } elseif ($categoryCol && !isset($product['category'])) {
        $product['category'] = $product[$categoryCol];
    }
}

// Ratings and Reviews
$rating_sql = "SELECT AVG(Rating_Value) as avg_rating, COUNT(*) as total_reviews FROM Products_Ratings WHERE Products_ID = " . $product_id;
$rating_result = mysqli_query($conn, $rating_sql);
$rating_data = mysqli_fetch_assoc($rating_result);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;

$reviews_sql = "SELECT r.*, u.Username as user_name FROM Products_Ratings r JOIN users u ON r.User_ID = u.User_ID WHERE r.Products_ID = $product_id ORDER BY r.Date DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);

// Check if in wishlist
$isInWishlist = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $check_w = $conn->query("SELECT * FROM wishlist WHERE user_id = '$uid' AND product_id = $product_id");
    if ($check_w && $check_w->num_rows > 0) {
        $isInWishlist = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | StyleMate</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>&r=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <div class="breadcrumb-container">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <i class='bx bx-chevron-right'></i>
                <?php 
                $group = $product['Category_Group'] ?? 'Women';
                $group_link = strtolower($group) . "_fashion.php";
                ?>
                <a href="<?php echo $group_link; ?>"><?php echo htmlspecialchars($group); ?></a>
                <i class='bx bx-chevron-right'></i>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
        </div>
    </div>

    <?php
    $image_urls = array_filter(array_unique([
        $product['image_url'],
        $product['image1'] ?? '',
        $product['image2'] ?? '',
        $product['image3'] ?? '',
        $product['image4'] ?? '',
        $product['image5'] ?? ''
    ]));
    ?>
    <main class="product-detail-container">
        <div class="product-detail-layout <?php echo count($image_urls) > 1 ? 'has-multiple-images' : ''; ?>">
            <!-- Gallery -->
            <div class="detail-gallery">
                <div class="detail-thumbnails">
                    <?php
                    foreach($image_urls as $index => $img) {
                        if (strpos($img, 'http') === 0) {
                            $src = $img;
                        } else {
                            $src = 'images/products/' . $img;
                        }
                        $active = $index === 0 ? 'active' : '';
                        echo "<img src='$src' class='detail-thumb $active' onclick='changeMainImage(this.src, this)' onerror=\"this.onerror=null;this.src='images/products/placeholder.svg'\">";
                    }
                    ?>
                </div>
                <div class="detail-main-image">
                    <?php
                    $mainImgUrl = $product['image_url'];
                    if (strpos($mainImgUrl, 'http') !== 0) {
                        $mainImgUrl = 'images/products/' . $mainImgUrl;
                    }
                    ?>
                    <img id="mainImage" src="<?php echo htmlspecialchars($mainImgUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                </div>
            </div>

            <!-- Info -->
            <div class="detail-info">
                <div class="detail-category"><?php echo htmlspecialchars($product['category']); ?> / <?php echo htmlspecialchars($product['Category_Group'] ?? 'Fashion'); ?></div>
                <h1 class="detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="detail-rating-summary">
                    <div class="stars">
                        <?php 
                        for($i=1; $i<=5; $i++) {
                            echo $i <= $avg_rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span>(<?php echo $total_reviews; ?> Reviews)</span>
                </div>

                <div class="detail-price-row">
                    <div class="detail-price">₹<?php echo number_format($product['price'], 2); ?></div>
                    <?php 
                    $stock = (int)($product['stock'] ?? 0);
                    if ($stock > 0): 
                        if ($stock < 10): ?>
                            <span class="stock-status-detail low-stock">
                                <i class='bx bx-alarm-exclamation'></i> Only <?php echo $stock; ?> units left
                            </span>
                        <?php else: ?>
                            <span class="stock-status-detail in-stock">
                                <i class='bx bx-check-circle'></i> In Stock (<?php echo $stock; ?> available)
                            </span>
                        <?php endif;
                    else: ?>
                        <span class="stock-status-detail out-of-stock">
                            <i class='bx bx-x-circle'></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <div class="detail-description">
                    <?php 
                    $desc = $product['description'] ?? 'No description available for this product.';
                    // Split by pipe | or newline to create bullet points
                    $desc_items = preg_split('/[|\n]/', $desc);
                    
                    if (count($desc_items) > 1) {
                        echo '<ul class="desc-list">';
                        foreach ($desc_items as $item) {
                            $item = trim($item);
                            if (!empty($item)) {
                                echo '<li class="desc-list-item">' . htmlspecialchars($item) . '</li>';
                            }
                        }
                        echo '</ul>';
                    } else {
                        echo nl2br(htmlspecialchars($desc));
                    }
                    ?>
                </div>

                <!-- Product Specifications -->
                <div class="product-specs specs-container">
                    <span class="specs-title specs-title-text">Product Specifications</span>
                    <?php
                    $found_specs = [];
                    
                    // 1. Fetch from 'Specifications' column if it exists and has data
                    if (!empty($product['Specifications'])) {
                        $lines = explode("\n", $product['Specifications']);
                        foreach ($lines as $line) {
                            if (strpos($line, ':') !== false) {
                                list($key, $val) = explode(':', $line, 2);
                                $found_specs[trim($key)] = trim($val);
                            }
                        }
                    }

                    // 2. Fallback to individual columns if specific specs are still missing
                    $spec_fields = [
                        'Brand' => ['brand', 'Brand'],
                        'Material' => ['material', 'Material'],
                        'Color' => ['color', 'Color'],
                        'Pattern' => ['pattern', 'Pattern'],
                        'Fit' => ['fit', 'Fit_Type'],
                        'Care' => ['care', 'Care_Instructions']
                    ];

                    foreach ($spec_fields as $label => $keys) {
                        if (!isset($found_specs[$label])) {
                            $val = null;
                            foreach ($keys as $k) {
                                if (!empty($product[$k])) { $val = $product[$k]; break; }
                            }
                            if ($val) {
                                $found_specs[$label] = $val;
                            }
                        }
                    }
                    ?>

                    <table class="specs-table">
                        <?php if (count($found_specs) > 0): ?>
                            <?php foreach ($found_specs as $label => $val): ?>
                                <tr class="specs-row">
                                    <th class="specs-header"><?php echo htmlspecialchars($label); ?></th>
                                    <td class="specs-cell"><?php echo htmlspecialchars($val); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="specs-row">
                                <th class="specs-header">Quality</th>
                                <td class="specs-cell">Premium Quality</td>
                            </tr>
                            <tr class="specs-row">
                                <th class="specs-header">Style</th>
                                <td class="specs-cell">Modern Authentic</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <?php 
                $show_sizes = true;
                $cat_group = strtolower($product['Category_Group'] ?? '');
                $cat_name = strtolower($product['category'] ?? '');
                $prod_name = strtolower($product['name'] ?? '');
                
                $no_size_keywords = [
                    'watch', 'bag', 'jewelry', 'jewellery', 'earring', 'necklace', 'anklet', 'ring', 
                    'sunglass', 'eyewear', 'belt', 'wallet', 'perfume', 'fragrance', 'hair', 'clip', 
                    'band', 'crown', 'scarf', 'tie', 'bowtie', 'socks', 'cap', 'hat', 'clutch', 'saree', 
                    'accessory', 'accessories', 'accesories'
                ];


                foreach ($no_size_keywords as $kw) {
                    if (strpos($prod_name, $kw) !== false || 
                        strpos($cat_name, $kw) !== false || 
                        strpos($cat_group, $kw) !== false) {
                        $show_sizes = false;
                        break;
                    }
                }

                if ($show_sizes): ?>
                    <div class="selection-group">
                        <label class="selection-label">Select Size</label>
                        <div class="size-grid">
                            <?php
                            $db_sizes = isset($product['size']) ? $product['size'] : (isset($product['sizes']) ? $product['sizes'] : (isset($product['Sizes']) ? $product['Sizes'] : ''));
                            $sizes = !empty($db_sizes) ? preg_split('/[,\s]+/', $db_sizes) : ['S', 'M', 'L', 'XL'];
                            foreach(array_filter(array_map('trim', $sizes)) as $size): ?>
                                <div class="size-option" onclick="selectSize(this, '<?php echo htmlspecialchars($size); ?>')">
                                    <?php echo htmlspecialchars($size); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-actions">
                    <?php if ($stock > 0): ?>
                        <button class="btn-primary" id="addToCartBtn" 
                                data-product-id="<?php echo $product_id; ?>"
                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-price="<?php echo $product['price']; ?>"
                                data-image="<?php echo htmlspecialchars($mainImgUrl); ?>"
                                data-stock="<?php echo $stock; ?>"
                                onclick="handleAddToCart()">
                            <i class='bx bx-shopping-bag'></i> Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="btn-primary disabled" disabled>
                            <i class='bx bx-x-circle'></i> Out of Stock
                        </button>
                    <?php endif; ?>
                    <button class="btn-secondary wishlist-btn <?php echo $isInWishlist ? 'active' : ''; ?>" 
                            onclick="toggleWishlistDetail(<?php echo $product_id; ?>)"
                            data-product-id="<?php echo $product_id; ?>">
                        <i class='bx <?php echo $isInWishlist ? 'bxs-heart' : 'bx-heart'; ?>'></i> Wishlist
                    </button>
                </div>

                <div class="detail-features">
                    <div class="feature-item">
                        <i class='bx bx-refresh'></i>
                        <span>7 Days Return</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bx-package'></i>
                        <span>Free Shipping</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bx-shield-quarter'></i>
                        <span>Secure Payment</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI-Powered Smart Recommendations -->
        <div class="ai-recommendations-section ai-section">
            <div class="container">
                <div class="ai-header-wrapper">
                    <div class="ai-badge">
                        <i class='bx bxs-magic-wand'></i>
                        <span>AI-Powered Personal Stylist</span>
                    </div>
                    <h2 class="ai-title">Complete Your Perfect Look</h2>
                    <p style="color: #666; font-size: 15px; max-width: 600px; margin: 0 auto; line-height: 1.6; margin-bottom: 25px;">Our AI stylist suggests these items to perfectly complement your current selection.</p>
                </div>

                <div id="ai-recommendation-rows">
                    <?php
                    // Logic to determine what rows to show based on current product
                    $set_types = ['AI-1', 'AI-2', 'AI-3', 'AI-4'];
                    ?>

                    <!-- Context Aware Row -->
                    <div class="recommendation-bundle-row" data-row="1" style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
                        <?php foreach ($set_types as $index => $type): ?>
                            <?php if ($index > 0): ?>
                                <div class="bundle-plus-icon">
                                    <i class='bx bx-plus'></i>
                                </div>
                            <?php endif; ?>
                            <div class="recommendation-item-wrapper" id="container-1-<?php echo $index + 1; ?>">
                                <div class="ai-item-card">
                                    <div class="loading-shimmer" style="height: 250px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="bundle-actions" style="display: flex; flex-direction: column; gap: 10px; margin-left: 10px;">
                            <button type="button" class="shuffle-btn" onclick="shuffleRow(1, <?php echo htmlspecialchars(json_encode($set_types)); ?>)" title="Shuffle recommendations">
                                <i class='bx bx-refresh'></i>
                            </button>
                            <button type="button" class="combine-btn" onclick="combineToCart(1)" title="Add bundle to cart">
                                <i class='bx bx-cart-add'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Suggestion Modal -->
        <div id="aiSuggestionModal" class="ai-modal" style="display: none; position: fixed; z-index: 20000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(15px); align-items: center; justify-content: center; padding: 20px;">
            <div class="ai-modal-content" style="background-color: #fff; padding: 30px; border-radius: 28px; width: 100%; max-width: 750px; position: relative; box-shadow: 0 50px 100px rgba(0,0,0,0.5); animation: modalFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); max-height: 85vh; display: flex; flex-direction: column;">
                <!-- Modern Close Button -->
                <button onclick="closeAiModal()" style="position: absolute; right: -15px; top: -15px; width: 45px; height: 45px; background: #000; color: #fff; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); z-index: 1000; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.1) rotate(90deg)'" onmouseout="this.style.transform='scale(1) rotate(0deg)'">
                    <i class='bx bx-x'></i>
                </button>
                
                <div style="text-align: center; margin-bottom: 25px; flex-shrink: 0;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #f0fdf4; color: #166534; padding: 6px 12px; border-radius: 20px; margin-bottom: 10px; border: 1px solid #bbf7d0;">
                        <i class='bx bxs-check-circle' style="font-size: 16px;"></i>
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Added to Cart</span>
                    </div>
                    <h2 style="font-size: 24px; font-weight: 900; margin-bottom: 5px; color: #000;">Complete Your Perfect Look</h2>
                    <p style="color: #666; font-size: 14px;">Our AI stylist hand-picked these to match your selection.</p>
                </div>

                <div class="modal-grid-container" id="modal-recommendation-row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; overflow-y: auto; padding: 5px; flex-grow: 1;">
                    <!-- Items will be injected here -->
                </div>

                <div style="display: flex; gap: 12px; justify-content: center; border-top: 1px solid #f3f4f6; padding-top: 20px; flex-shrink: 0;">
                    <button onclick="closeAiModal()" style="padding: 14px 25px; border: 2px solid #000; background: #fff; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; color: #000; flex: 1; max-width: 200px;">Continue Shopping</button>
                    <button onclick="window.location.href='cart.php'" style="padding: 14px 25px; border: none; background: #000; color: #fff; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; flex: 1; max-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">View Cart</button>
                </div>
            </div>
        </div>



        <style>
            @keyframes modalFadeUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .ai-modal { display: none; }
            .ai-modal.show { display: flex !important; }
        </style>

        <style>
            .ai-item-card {
                background: #fff;
                border-radius: 16px;
                padding: 15px;
                width: 200px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.05);
                border: 1px solid #f0f0f0;
                transition: all 0.3s ease;
                position: relative;
            }
            .ai-item-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            }
            .ai-item-img {
                width: 100%;
                height: 200px;
                object-fit: contain;
                border-radius: 10px;
                margin-bottom: 12px;
                background: #fcfcfc;
            }
            .ai-item-name {
                font-size: 13px;
                font-weight: 700;
                color: #000;
                margin-bottom: 5px;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-decoration: none;
            }
            .ai-item-price {
                font-size: 14px;
                font-weight: 800;
                color: #000;
            }
            .shuffle-btn, .combine-btn {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 20px;
            }
            .shuffle-btn {
                background: #f0f0f0;
                color: #333;
            }
            .shuffle-btn:hover {
                background: #000;
                color: #fff;
                transform: rotate(180deg);
            }
            .combine-btn {
                background: #000;
                color: #fff;
            }
            .combine-btn:hover {
                background: #222;
                transform: scale(1.1);
            }
            .ai-tag {
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(0,0,0,0.05);
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                color: #888;
            }
            
            @media (max-width: 768px) {
                .recommendation-bundle-row {
                    flex-direction: column;
                    gap: 15px;
                }
                .bundle-plus-icon {
                    transform: rotate(90deg);
                }
                .bundle-actions {
                    flex-direction: row;
                }
            }
        </style>

        <script>
            const targetProductId = <?php echo $product_id; ?>;
            const mainProduct = {
                id: <?php echo $product_id; ?>,
                name: "<?php echo addslashes($product['name']); ?>",
                price: <?php echo floatval(str_replace(',', '', $product['price'])); ?>,
                image: "<?php echo $product['image_url']; ?>",
                stock: <?php echo isset($product['stock']) ? intval($product['stock']) : 1000; ?>
            };
            const storageKey = `ai_recs_v28_${targetProductId}`; // Bumped version to force refresh for Product 112 fixes

            function renderItem(item, containerId) {
                const container = document.getElementById(containerId);
                if (!item) {
                    container.innerHTML = `
                        <div class="ai-item-card" style="border: 1px dashed #eee; background: #fafafa; display: flex; align-items: center; justify-content: center; height: 100%;">
                            <span style="font-size: 12px; color: #ccc; font-weight: 500;">Out of Stock</span>
                        </div>
                    `;
                    return;
                }
                container.innerHTML = `
                    <div class="ai-item-card" data-id="${item.id}" data-name="${item.name.replace(/'/g, "\\'")}" data-price="${item.price.replace(/,/g, '')}" data-image="${item.image}" data-stock="${item.stock}">
                        <span class="ai-tag">AI Pick</span>
                        <a href="${item.href}">
                            <img src="${item.image}" class="ai-item-img" alt="${item.name}">
                        </a>
                        <a href="${item.href}" class="ai-item-name">${item.name}</a>
                        <div class="ai-item-price">₹${item.price}</div>
                    </div>
                `;
            }

            async function shuffleRow(rowId) {
                // Show shimmer
                for (let i = 1; i <= 4; i++) {
                    const container = document.getElementById(`container-${rowId}-${i}`);
                    if (container) {
                        container.innerHTML = '<div class="ai-item-card"><div class="loading-shimmer" style="height: 250px; background: #eee; border-radius: 12px;"></div></div>';
                    }
                }

                try {
                    const response = await fetch(`get_ai_recommendations.php?id=${targetProductId}&t=${new Date().getTime()}`);
                    const data = await response.json();
                    
                    if (data.success && data.recommendations) {
                        data.recommendations.forEach((item, index) => {
                            if (index < 4) {
                                renderItem(item, `container-${rowId}-${index + 1}`);
                            }
                        });
                        sessionStorage.setItem(storageKey, JSON.stringify(data.recommendations));
                    }
                } catch (e) {
                    console.error("Error fetching recommendations:", e);
                }
            }

            async function initRecommendations(rowId) {
                const stored = sessionStorage.getItem(storageKey);
                if (stored) {
                    try {
                        const items = JSON.parse(stored);
                        items.forEach((item, index) => {
                            if (index < 4) renderItem(item, `container-${rowId}-${index + 1}`);
                        });
                        return;
                    } catch (e) { sessionStorage.removeItem(storageKey); }
                }
                await shuffleRow(rowId);
            }

            function openAiModal() {
                const modal = document.getElementById('aiSuggestionModal');
                const modalRow = document.getElementById('modal-recommendation-row');
                const sourceRow = document.querySelector('.recommendation-bundle-row[data-row="1"]');
                
                if (modal && modalRow && sourceRow) {
                    const itemWrappers = sourceRow.querySelectorAll('.recommendation-item-wrapper');
                    modalRow.innerHTML = '';
                    
                    itemWrappers.forEach(wrapper => {
                        const card = wrapper.querySelector('.ai-item-card');
                        if (card) {
                            const clone = card.cloneNode(true);
                            
                            // Apply "Proper" Modal Styling
                            clone.style.width = '100%';
                            clone.style.maxWidth = 'none';
                            clone.style.margin = '0';
                            clone.style.padding = '12px';
                            clone.style.borderRadius = '16px';
                            clone.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
                            clone.style.border = '1px solid #f3f4f6';
                            clone.style.display = 'flex';
                            clone.style.flexDirection = 'column';
                            clone.style.alignItems = 'center';
                            clone.style.textAlign = 'center';
                            
                            // Image adjustments
                            const img = clone.querySelector('.ai-item-img');
                            if (img) {
                                img.style.height = '160px'; // Slightly smaller for 2x2 grid
                                img.style.width = '100%';
                                img.style.objectFit = 'contain';
                                img.style.marginBottom = '8px';
                                img.style.background = '#fcfcfc';
                                img.style.borderRadius = '8px';
                            }

                            // Typography adjustments
                            const name = clone.querySelector('.ai-item-name');
                            if (name) {
                                name.style.fontSize = '12px';
                                name.style.fontWeight = '700';
                                name.style.marginBottom = '4px';
                                name.style.color = '#111';
                                name.style.lineHeight = '1.3';
                            }

                            const price = clone.querySelector('.ai-item-price');
                            if (price) {
                                price.style.fontSize = '14px';
                                price.style.fontWeight = '800';
                                price.style.color = '#000';
                            }

                            // Tag adjustment
                            const tag = clone.querySelector('.ai-tag');
                            if (tag) {
                                tag.style.fontSize = '8px';
                                tag.style.padding = '2px 6px';
                                tag.style.top = '8px';
                                tag.style.right = '8px';
                            }

                            modalRow.appendChild(clone);
                        }
                    });
                    
                    modal.classList.add('show');
                }
            }

            function closeAiModal() {
                const modal = document.getElementById('aiSuggestionModal');
                if (modal) modal.classList.remove('show');
            }

            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                const modal = document.getElementById('aiSuggestionModal');
                if (e.target === modal) {
                    closeAiModal();
                }
            });

            async function toggleWishlistDetail(productId) {
                const btn = document.querySelector(`.wishlist-btn[data-product-id="${productId}"]`);
                if (!btn || btn.classList.contains('loading')) return;

                btn.classList.add('loading');
                const icon = btn.querySelector('i');
                const originalIconClass = icon.className;
                
                // Temporary loading state
                icon.className = 'bx bx-loader-alt bx-spin';

                try {
                    const response = await fetch('toggle_wishlist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `product_id=${productId}`
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        if (data.action === 'added') {
                            btn.classList.add('active');
                            icon.className = 'bx bxs-heart';
                            icon.style.animation = 'heartBeat 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                            showToast('Added to wishlist');
                        } else {
                            btn.classList.remove('active');
                            icon.className = 'bx bx-heart';
                            showToast('Removed from wishlist');
                        }
                    } else if (data.status === 'unauthorized') {
                        window.location.href = 'login.php';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    icon.className = originalIconClass;
                } finally {
                    btn.classList.remove('loading');
                    setTimeout(() => { icon.style.animation = ''; }, 400);
                }
            }

            async function combineToCart(rowId) {
                const itemCards = document.querySelectorAll(`.recommendation-bundle-row[data-row="${rowId}"] .ai-item-card[data-id]`);
                
                if (itemCards.length === 0) return;

                const items = Array.from(itemCards).map(card => ({
                    id: card.dataset.id,
                    name: card.dataset.name,
                    price: parseFloat(card.dataset.price),
                    image: card.dataset.image,
                    stock: parseInt(card.dataset.stock || 1000)
                }));
                
                // Add main product to the bundle so the user gets the complete look (5 items total)
                items.unshift(mainProduct);

                // Assuming addToCart function exists globally from script.js
                for (const item of items) {
                    await addToCart(parseInt(item.id), item.name, item.price, item.image, item.stock, false);
                }
                
                showToast("Full outfit added to cart!");
                openAiModal();
            }

            function showToast(message) {
                const toast = document.getElementById('cartToast');
                const msgSpan = document.getElementById('toastMessage');
                if (toast && msgSpan) {
                    msgSpan.innerText = message;
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);
                } else {
                    alert(message);
                }
            }

            // Initial load
            window.addEventListener('DOMContentLoaded', () => {
                initRecommendations(1);
            });
        </script>
     </div>
 </div>

 <!-- Reviews -->
 <div class="detail-reviews">
            <h2 class="section-title" style="text-align: left; padding: 0; margin-bottom: 30px; font-size: 24px;">Customer Reviews</h2>
            <div class="reviews-grid">
                <div class="review-summary-card">
                    <div class="detail-rating-summary" style="font-size: 36px; gap: 10px;">
                        <strong><?php echo number_format($avg_rating, 1); ?></strong>
                        <div style="font-size: 16px;">
                            <div class="stars">
                                <?php 
                                for($i=1; $i<=5; $i++) {
                                    if ($i <= $avg_rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $avg_rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span style="display: block; margin-top: 4px;"><?php echo $total_reviews; ?> ratings</span>
                        </div>
                    </div>
                </div>

                <div class="reviews-list">
                    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                        <?php while($review = mysqli_fetch_assoc($reviews_result)): ?>
                            <div class="review-item-minimal">
                                <div class="review-user">
                                    <div class="user-name-init"><?php echo strtoupper(substr($review['user_name'], 0, 1)); ?></div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                    <div style="margin-left: auto; color: #888; font-size: 12px;"><?php echo date('M d, Y', strtotime($review['Date'])); ?></div>
                                </div>
                                <div class="stars" style="color: #ffb400; font-size: 12px; margin-bottom: 8px;">
                                    <?php for($i=1; $i<=5; $i++) echo $i <= $review['Rating_Value'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                                <div class="review-content"><?php echo nl2br(htmlspecialchars($review['Review_Text'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #888; font-style: italic;">No reviews yet for this product.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <div id="cartToast" class="cart-toast">
        <div class="toast-content">
            <i class='bx bx-check-circle'></i>
            <span id="toastMessage"></span>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>

    <script src="script.js"></script>
    <script>
        let selectedSize = '';

        // Add to Set functionality
        document.querySelectorAll('.add-to-set-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const btn = this;
                const productId = btn.dataset.id;
                const productName = btn.dataset.name;
                const productPrice = btn.dataset.price;

                btn.style.color = '#000';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&name=${encodeURIComponent(productName)}&price=${productPrice}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btn.style.color = '#28a745';
                        btn.innerHTML = '<i class="fas fa-check"></i> Added to Set';
                        
                        // Show toast
                        const toast = document.getElementById('cartToast');
                        const toastMsg = document.getElementById('toastMessage');
                        toastMsg.textContent = 'Added to your set!';
                        toast.classList.add('show');
                        
                        // Update cart count if exists
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) cartCount.textContent = data.cartCount;

                        setTimeout(() => {
                            toast.classList.remove('show');
                        }, 3000);
                    } else {
                        btn.style.color = '#dc3545';
                        btn.innerHTML = 'Error';
                        setTimeout(() => {
                            btn.style.color = '#888';
                            btn.innerHTML = 'Add To Set +';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    btn.innerHTML = 'Error';
                });
            });

            // Hover effects
            btn.addEventListener('mouseenter', function() {
                if (!this.innerHTML.includes('Added')) {
                    this.style.color = '#000';
                    this.style.transform = 'translateY(-2px)';
                }
            });
            btn.addEventListener('mouseleave', function() {
                if (!this.innerHTML.includes('Added')) {
                    this.style.color = '#888';
                    this.style.transform = 'translateY(0)';
                }
            });
        });

        function changeMainImage(src, thumb) {
            const mainImg = document.getElementById('mainImage');
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = src;
                mainImg.style.opacity = '1';
            }, 250);
            document.querySelectorAll('.detail-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        }

        // Advanced Mouse follow zoom effect
        const container = document.querySelector('.detail-main-image');
        const img = document.getElementById('mainImage');

        container.addEventListener('mousemove', (e) => {
            const rect = container.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            img.style.transformOrigin = `${x}% ${y}%`;
            img.style.transform = "scale(2.5)";
        });

        container.addEventListener('mouseleave', () => {
            img.style.transform = "scale(1)";
            img.style.transformOrigin = "center center";
        });

        function selectSize(el, size) {
            document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            selectedSize = size;
        }

        function handleAddToCart() {
            const sizeRequired = <?php echo $show_sizes ? 'true' : 'false'; ?>;
            if (sizeRequired && !selectedSize) {
                showToast('Please select a size first');
                return;
            }

            const productId = <?php echo intval($product['products_id']); ?>;
            const name = "<?php echo addslashes($product['name']); ?>";
            const price = <?php echo (float)$product['price']; ?>;

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&name=${encodeURIComponent(name)}&price=${price}&size=${encodeURIComponent(selectedSize)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Added to cart successfully!');
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) cartCount.textContent = data.cartCount;
                    
                    // Show AI Suggestions Modal
                    setTimeout(openAiModal, 800);
                } else {
                    showToast(data.message || 'Could not add to cart');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Check for msg=select_size parameter
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'select_size') {
                setTimeout(() => {
                    if (typeof showToast === 'function') {
                        showToast('Please select a size first');
                    } else {
                        alert('Please select a size first');
                    }
                }, 500);
            }
        });
    </script>
</body>
</html>
