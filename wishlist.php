<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Dynamic column detection
$cols = [];
$rc = $conn->query("SHOW COLUMNS FROM products");
if ($rc) {
    while ($r = $rc->fetch_assoc()) { $cols[$r['Field']] = true; }
}
$idCol = null;
foreach (['products_id','product_id','id','Product_ID','Products_ID'] as $c) { if (isset($cols[$c])) { $idCol = $c; break; } }
$imgCol = null;
foreach (['image_url','product_image','image','img','image_path'] as $c) { if (isset($cols[$c])) { $imgCol = $c; break; } }
$priceCol = null;
foreach (['price','product_price','Price','Cost','cost','rate','amount'] as $c) { if (isset($cols[$c])) { $priceCol = $c; break; } }
$nameCol = null;
foreach (['name', 'Name', 'product_name', 'Products_Name', 'Products_name', 'title', 'Title'] as $c) { 
    if (isset($cols[$c])) { $nameCol = $c; break; } 
}

// Get wishlist items
$sql = "SELECT p.*, c.Categories_name, c.Category_Group,
               AVG(pr.Rating_Value) as avg_rating, 
               COUNT(pr.Products_ID) as total_reviews 
        FROM products p 
        JOIN wishlist w ON p.Products_ID = w.product_id 
        LEFT JOIN categories c ON p.Categories_ID = c.Categories_ID
        LEFT JOIN Products_Ratings pr ON p.Products_ID = pr.Products_ID
        WHERE w.user_id = ?
        GROUP BY p.Products_ID";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Update idCol for the rest of the page
$idCol = 'Products_ID';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($lang['my_wishlist']) ? $lang['my_wishlist'] : 'My Wishlist'); ?> - StyleMate</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        /* Product Card Sync with Women's Page */
        .product-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f2f2f2;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.12);
            border-color: #e0e0e0;
        }

        .product-image {
            position: relative;
            aspect-ratio: 2/3;
            background: #fff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image a {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 1.2s cubic-bezier(0.2, 0, 0, 1);
            padding: 10px;
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }

        /* Keep remove button specifically for wishlist */
        .remove-btn { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border: none; 
            color: #000; 
            cursor: pointer; 
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .remove-btn:hover { 
            background: #000;
            color: white;
            transform: rotate(90deg) scale(1.1);
        }

        .remove-btn i {
            font-size: 20px;
        }

        /* Sync details alignment */
        .product-details {
            padding: 20px 15px;
            text-align: left;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            background: #fff;
        }

        .product-details h3 {
            font-size: 14px;
            font-weight: 600;
            color: #222;
            margin: 0 0 6px;
            min-height: 40px;
            line-height: 1.4;
            transition: color 0.3s ease;
        }

        .product-card:hover .product-details h3 {
            color: #000;
        }

        /* Sync add to cart button to be always visible like women's page */
        .add-to-cart {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            background: #000;
            color: #fff;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: auto;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart:hover {
            background: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        /* Page Header Sync */
        .page-header {
            background: #fff;
            padding: 60px 0;
            border-bottom: 1px solid #f2f2f2;
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 42px;
            font-weight: 800;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            letter-spacing: -0.02em;
        }

        /* Empty state */
        .empty-wishlist {
            text-align: center;
            padding: 120px 40px;
            background: #fff;
            border-radius: 20px;
            margin: 40px auto;
            max-width: 600px;
        }

        .go-shopping-btn {
            display: inline-block;
            padding: 18px 45px;
            background: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            margin-top: 30px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 13px;
        }

        .go-shopping-btn:hover {
            background: #333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>
    
    <main>
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">
                    <?php echo (isset($lang['my_wishlist']) ? $lang['my_wishlist'] : 'My Wishlist'); ?>
                    <ion-icon name="heart" style="color: #000; font-size: 42px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.1));"></ion-icon>
                </h1>
            </div>
        </div>

        <div class="container" style="margin-top: 0;">
            <?php
            if ($result->num_rows > 0): ?>
            <div class="products-grid">
                <?php while($row = $result->fetch_assoc()): 
                    $idVal = $row['Products_ID'];
                    $priceVal = ($priceCol && isset($row[$priceCol])) ? (float)$row[$priceCol] : 0.0;
                    $imgVal = $imgCol ? ($row[$imgCol] ?? '') : '';
                    $imgSrc = 'images/products/' . ($imgVal ?: 'placeholder.svg');
                    $nameVal = $nameCol ? $row[$nameCol] : 'Product';
                    $href = "product_detail.php?id=" . intval($idVal);

                    // Determine if size is required for add to cart logic
                    $show_sizes = true;
                    $prod_name = strtolower($nameVal);
                    $cat_name = isset($row['Categories_name']) ? strtolower($row['Categories_name']) : "";
                    $group_name = isset($row['Category_Group']) ? strtolower($row['Category_Group']) : "";
                    
                    $no_size_keywords = [
                        'watch', 'bag', 'jewelry', 'jewellery', 'earring', 'necklace', 'anklet', 'ring', 
                        'sunglass', 'eyewear', 'belt', 'wallet', 'perfume', 'fragrance', 'hair', 'clip', 
                        'band', 'crown', 'scarf', 'tie', 'bowtie', 'socks', 'cap', 'hat', 'clutch', 'saree', 
                        'accessory', 'accessories', 'accesories'
                    ];

                    foreach ($no_size_keywords as $kw) {
                        if (strpos($prod_name, $kw) !== false || 
                            strpos($cat_name, $kw) !== false || 
                            strpos($group_name, $kw) !== false) {
                            $show_sizes = false;
                            break;
                        }
                    }
                ?>
                    <div class="product-card" id="wishlist-item-<?php echo $idVal; ?>" onclick="if(!event.target.closest('button') && !event.target.closest('a')) window.location.href='<?php echo $href; ?>'">
                        <div class="product-image">
                            <button class="remove-btn" onclick="event.stopPropagation(); removeFromWishlist(<?php echo $idVal; ?>)" title="<?php echo (isset($lang['remove']) ? $lang['remove'] : 'Remove'); ?>">
                                <i class='bx bx-x'></i>
                            </button>
                            
                            <?php if(isset($row['Stock']) && (int)$row['Stock'] < 10 && (int)$row['Stock'] > 0) { ?>
                                <span class="stock-status low-stock"><?php echo $lang['only']; ?> <?php echo (int)$row['Stock']; ?> <?php echo $lang['left']; ?></span>
                            <?php } elseif(isset($row['Stock']) && (int)$row['Stock'] <= 0) { ?>
                                <span class="stock-status out-of-stock"><?php echo $lang['out_of_stock']; ?></span>
                            <?php } ?>

                            <a href="<?php echo htmlspecialchars($href); ?>">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($nameVal); ?>" onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                                <span class="quick-view"><?php echo (isset($lang['view_details']) ? $lang['view_details'] : 'View Details'); ?></span>
                            </a>
                        </div>
                        <div class="product-details">
                            <div class="product-rating">
                                <?php
                                $avg_rating = round($row['avg_rating'] ?? 0, 1);
                                $total_reviews = $row['total_reviews'] ?? 0;
                                ?>
                                <span class="rating-value"><?php echo number_format($avg_rating, 1); ?></span>
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
                                <span class="review-count">(<?php echo $total_reviews; ?>)</span>
                            </div>
                            <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration: none;">
                                <h3><?php echo htmlspecialchars($nameVal); ?></h3>
                            </a>
                            <div class="product-price">₹<?php echo number_format($priceVal, 2); ?></div>
                            
                            <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?php echo intval($idVal); ?>, '<?php echo addslashes($nameVal); ?>', <?php echo $priceVal; ?>, <?php echo $show_sizes ? 'true' : 'false'; ?>)">
                                <?php echo $lang['add_to_cart']; ?>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <ion-icon name="heart-outline" style="color: #000; font-size: 80px; margin-bottom: 25px;"></ion-icon>
                <h3><?php echo (isset($lang['your_wishlist_is_empty']) ? $lang['your_wishlist_is_empty'] : 'Your wishlist is empty'); ?></h3>
                <p><?php echo (isset($lang['wishlist_empty_desc']) ? $lang['wishlist_empty_desc'] : 'Browse our products and add your favorites here!'); ?></p>
                <a href="index.php" class="go-shopping-btn"><?php echo (isset($lang['go_shopping']) ? $lang['go_shopping'] : 'Go Shopping'); ?></a>
            </div>
        <?php endif; ?>
    </div>
    </main>

    <script src="script.js"></script>
    <script>
    function removeFromWishlist(productId) {
        if (!confirm('<?php echo (isset($lang['remove_from_wishlist_confirm']) ? $lang['remove_from_wishlist_confirm'] : 'Remove this item from your wishlist?'); ?>')) return;
        
        fetch('toggle_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const item = document.getElementById(`wishlist-item-${productId}`);
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.remove();
                        if (document.querySelectorAll('.product-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            }
        });
    }
    </script>
    <?php include_once 'footer.php'; ?>
</body>
</html>