<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}
require_once 'db_connect.php';

// Handle search and filters
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$sub_category = isset($_GET['sub']) ? trim($_GET['sub']) : '';
$category_name = isset($_GET['cat']) ? trim($_GET['cat']) : '';

$sql = "SELECT p.*,
                 AVG(pr.Rating_Value) as avg_rating,
                 COUNT(pr.Products_ID) as total_reviews,
                 c.Categories_name_hi, c.Categories_name_gu,
                 s.Subcategories_name_hi, s.Subcategories_name_gu
          FROM products p
          JOIN categories c ON p.Categories_ID = c.Categories_ID
          LEFT JOIN subcategories s ON p.Subcategories_ID = s.Subcategories_ID
          LEFT JOIN Products_Ratings pr ON p.Products_ID = pr.Products_ID
          WHERE c.Category_Group = 'Girls' AND p.Status = 1 ";

if ($category_name !== '') {
    $cat = mysqli_real_escape_string($conn, $category_name);
    $sql .= " AND c.Categories_name = '$cat' ";
}

if ($sub_category !== '') {
    $sub = mysqli_real_escape_string($conn, $sub_category);
    $sql .= " AND s.Subcategories_name = '$sub' ";
}

if ($search_query !== '') {
    $s = mysqli_real_escape_string($conn, $search_query);
    $sql .= " AND (p.Products_Name LIKE '%$s%' OR p.Description LIKE '%$s%' OR p.Products_Name_hi LIKE '%$s%' OR p.Products_Name_gu LIKE '%$s%') ";
}

$sql .= " GROUP BY p.Products_ID";

$result = mysqli_query($conn, $sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Girls' Collection | StyleMate</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php require_once 'header.php'; ?>

<main>
    <div class="page-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php"><?php echo $lang['home']; ?></a>
                <span>/</span>
                <span><?php echo $lang['girls']; ?></span>
                <?php if ($category_name): ?>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($category_name); ?></span>
                <?php endif; ?>
            </div>
            <h1 class="page-title">
                <?php 
                    if ($search_query) {
                        echo (isset($lang['search']) ? $lang['search'] : "Search") . ": " . htmlspecialchars($search_query);
                    } elseif ($category_name) {
                        echo htmlspecialchars($category_name);
                    } elseif ($sub_category) {
                        echo htmlspecialchars($sub_category);
                    } else {
                        echo $lang['girls'];
                    }
                ?>
            </h1>
        </div>
    </div>

    <div class="container">
        <div class="products-grid">
            <?php
                // Get user's wishlist if logged in
                $wishlist_items = [];
                if (isset($_SESSION['user_id'])) {
                    $u_id = $_SESSION['user_id'];
                    $w_res = $conn->query("SELECT product_id FROM wishlist WHERE user_id = '$u_id'");
                    if ($w_res) {
                        while ($w_row = $w_res->fetch_assoc()) {
                            $wishlist_items[] = $w_row['product_id'];
                        }
                    }
                }

                if (mysqli_num_rows($result) > 0) {
                    $current_lang = $_SESSION['lang'] ?? 'en';
                    while($row = mysqli_fetch_assoc($result)) {
                        $idVal = $row['Products_ID'];
                        
                        // Localized name
                        $nameVal = $row['Products_Name'];
                        if ($current_lang == 'hi' && !empty($row['Products_Name_hi'])) {
                            $nameVal = $row['Products_Name_hi'];
                        } elseif ($current_lang == 'gu' && !empty($row['Products_Name_gu'])) {
                            $nameVal = $row['Products_Name_gu'];
                        }

                        $descVal = $row['Description'];
                        $priceVal = (float)($row['price'] ?? 0);
                        $imgVal = $row['image'];
                        
                        // Image source logic
                        $imgSrc = 'images/products/' . ($imgVal ?: 'placeholder.svg');
                        $href = "product_detail.php?id=" . intval($idVal) . "&category=girls";
                        $isInWishlist = in_array($idVal, $wishlist_items);
                        
                        // Determine if this product category needs size validation
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
                <div class="product-card" onclick="if(!event.target.closest('button') && !event.target.closest('a')) window.location.href='<?php echo $href; ?>'">
                    <div class="product-image">
                        <button class="wishlist-btn <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                onclick="event.stopPropagation(); toggleWishlist(<?php echo intval($idVal); ?>)" 
                                data-product-id="<?php echo intval($idVal); ?>">
                            <i class='bx <?php echo $isInWishlist ? 'bxs-heart' : 'bx-heart'; ?>'></i>
                        </button>
                        
                        <?php if(isset($row['Stock']) && (int)$row['Stock'] < 10 && (int)$row['Stock'] > 0) { ?>
                            <span class="stock-status low-stock"><?php echo $lang['only']; ?> <?php echo (int)$row['Stock']; ?> <?php echo $lang['left']; ?></span>
                        <?php } elseif(isset($row['Stock']) && (int)$row['Stock'] <= 0) { ?>
                            <span class="stock-status out-of-stock"><?php echo $lang['out_of_stock']; ?></span>
                        <?php } ?>

                        <a href="<?php echo htmlspecialchars($href); ?>">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                 alt="<?php echo htmlspecialchars($nameVal); ?>" 
                                 onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                            <span class="quick-view"><?php echo $lang['view_details']; ?></span>
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
                        
                        <?php if ($show_sizes): ?>
                            <button class="add-to-cart" onclick="window.location.href='<?php echo $href . '&msg=select_size'; ?>'">
                                <?php echo isset($lang['add_to_cart']) ? $lang['add_to_cart'] : 'Add to Cart'; ?>
                            </button>
                        <?php else: ?>
                            <button class="add-to-cart" 
                                    onclick="addToCart(<?php echo intval($idVal); ?>, '<?php echo addslashes($nameVal); ?>', <?php echo $priceVal; ?>, '<?php echo $imgSrc; ?>', <?php echo (int)($row['Stock'] ?? 1000); ?>, 'false')">
                                <?php echo $lang['add_to_cart']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                    }
                } else {
                    echo "<div style='grid-column: 1/-1; text-align: center; padding: 100px 0; color: #888;'>" . (isset($lang['no_products_found']) ? $lang['no_products_found'] : 'No products found.') . "</div>";
                }
            ?>
        </div>
    </div>
</main>

<div id="cartToast" class="cart-toast">
    <div class="toast-content">
        <i class='bx bx-check-circle'></i>
        <span id="toastMessage"><?php echo (isset($lang['added_to_cart_success']) ? $lang['added_to_cart_success'] : 'Added to cart successfully'); ?></span>
    </div>
</div>

<?php require_once 'footer.php'?>

<script src="script.js"></script>
</body>
</html>

