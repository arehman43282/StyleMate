<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Global Search Query
$sql = "SELECT p.*, 
               c.Categories_name,
               s.Subcategories_name,
               AVG(pr.Rating_Value) as avg_rating, 
               COUNT(pr.Products_ID) as total_reviews 
        FROM products p 
        LEFT JOIN categories c ON p.Categories_ID = c.Categories_ID
        LEFT JOIN subcategories s ON p.Subcategories_ID = s.Subcategories_ID
        LEFT JOIN Products_Ratings pr ON p.Products_ID = pr.Products_ID 
        WHERE p.Status = 1 ";

if ($search_query !== '') {
    $s = mysqli_real_escape_string($conn, $search_query);
    $words = explode(' ', $search_query);
    $word_conditions = [];
    
    // Build relevance scoring
    $relevance_sql = ", (
        (CASE WHEN p.Products_Name LIKE '%$s%' THEN 10 ELSE 0 END) +
        (CASE WHEN c.Categories_name LIKE '%$s%' THEN 8 ELSE 0 END) +
        (CASE WHEN s.Subcategories_name LIKE '%$s%' THEN 7 ELSE 0 END) +
        (CASE WHEN p.Description LIKE '%$s%' THEN 2 ELSE 0 END) +
        (CASE WHEN p.Specifications LIKE '%$s%' THEN 1 ELSE 0 END)
    ) as relevance ";
    
    // Insert relevance column into the SELECT part
    $sql = str_replace("p.*,", "p.*" . $relevance_sql . ",", $sql);

    foreach ($words as $word) {
        $word = mysqli_real_escape_string($conn, trim($word));
        if ($word !== '') {
            $word_conditions[] = "(
                p.Products_Name LIKE '%$word%' OR 
                p.Description LIKE '%$word%' OR 
                p.Specifications LIKE '%$word%' OR
                c.Categories_name LIKE '%$word%' OR 
                s.Subcategories_name LIKE '%$word%'
            )";
        }
    }
    
    if (!empty($word_conditions)) {
        $sql .= " AND (" . implode(" AND ", $word_conditions) . ") ";
    }
} else {
    // If no query, redirect to home or show nothing
    header("Location: index.php");
    exit();
}

$sql .= " GROUP BY p.Products_ID";

// Filter out low relevance results for specific queries to avoid cross-category pollution
if ($search_query !== '' && strlen($search_query) > 3) {
    $sql .= " HAVING relevance >= 3";
}

$sql .= " ORDER BY relevance DESC, p.Products_Name ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - StyleMate</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .search-results-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 40px 20px; 
        }
        .no-results { 
            text-align: center; 
            padding: 100px 20px; 
            background: #fff; 
        }
        .no-results i { 
            font-size: 64px; 
            color: #eee; 
            margin-bottom: 24px; 
        }
        .no-results h2 {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 12px;
            color: #1a1a1a;
        }
        .no-results p {
            color: #666;
            margin-bottom: 30px;
        }
        .return-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: opacity 0.3s;
        }
        .return-btn:hover {
            opacity: 0.8;
        }
        .search-page-wrapper {
            padding: 40px 0 20px;
            background: #fff;
        }
        .search-meta {
            color: #666;
            margin-top: 8px;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <div class="search-page-wrapper" style="margin-top: 180px;">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Home</a> / <span>Search</span>
            </div>
            <h1 class="page-title">Search Results</h1>
            <p class="search-meta">Showing <?php echo mysqli_num_rows($result); ?> results for "<?php echo htmlspecialchars($search_query); ?>"</p>
        </div>
    </div>

    <main class="search-results-container">
        <div class="products-grid">
            <?php 
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

            if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $price = $row['price'] ?? 0;
                    $imgSrc = 'images/products/' . ($row['image'] ?: 'placeholder.svg');
                    $idVal = $row['Products_ID'];
                    $isInWishlist = in_array($idVal, $wishlist_items);
                    $href = "product_detail.php?id=" . $idVal;
                    
                    $show_sizes = true;
                    $prod_name = strtolower($row['Products_Name']);
                    $cat_name = isset($row['Categories_name']) ? strtolower($row['Categories_name']) : "";
                    
                    if (strpos($prod_name, 'watch') !== false || strpos($cat_name, 'accessory') !== false || 
                        strpos($prod_name, 'bag') !== false || strpos($prod_name, 'sunglass') !== false ||
                        strpos($prod_name, 'belt') !== false || strpos($prod_name, 'wallet') !== false ||
                        strpos($prod_name, 'jewelry') !== false || strpos($prod_name, 'perfume') !== false ||
                        strpos($prod_name, 'saree') !== false || strpos($cat_name, 'saree') !== false ||
                        strpos($prod_name, 'hair') !== false || strpos($prod_name, 'clip') !== false ||
                        strpos($prod_name, 'band') !== false) {
                        $show_sizes = false;
                    }
                ?>
                    <div class="product-card">
                        <div class="product-image">
                            <button class="wishlist-btn <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                    onclick="toggleWishlist(<?php echo intval($idVal); ?>)" 
                                    data-product-id="<?php echo intval($idVal); ?>">
                                <i class='bx <?php echo $isInWishlist ? 'bxs-heart' : 'bx-heart'; ?>'></i>
                            </button>
                            
                            <?php if(isset($row['Stock']) && (int)$row['Stock'] < 10 && (int)$row['Stock'] > 0) { ?>
                                <span class="stock-status low-stock">Low Stock</span>
                            <?php } elseif(isset($row['Stock']) && (int)$row['Stock'] <= 0) { ?>
                                <span class="stock-status out-of-stock">Out of Stock</span>
                            <?php } ?>

                            <a href="<?php echo $href; ?>">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($row['Products_Name']); ?>" 
                                     onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                                <span class="quick-view">View Details</span>
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
                            <a href="<?php echo $href; ?>" style="text-decoration:none;">
                                <h3><?php echo htmlspecialchars($row['Products_Name']); ?></h3>
                            </a>
                            <div class="product-price">₹<?php echo number_format($price, 2); ?></div>
                            <button class="add-to-cart" 
                                    onclick="addToCart(<?php echo $idVal; ?>, '<?php echo addslashes($row['Products_Name']); ?>', <?php echo $price; ?>, '<?php echo $imgSrc; ?>', <?php echo isset($row['Stock']) ? (int)$row['Stock'] : 1000; ?>, <?php echo $show_sizes ? 'true' : 'false'; ?>)">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results" style="grid-column: 1/-1;">
                    <i class="bx bx-search-alt"></i>
                    <h2>No products found</h2>
                    <p>We couldn't find any products matching your search for "<?php echo htmlspecialchars($search_query); ?>".</p>
                    <a href="index.php" class="return-btn">Return to Shop</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="cartToast" class="cart-toast">
        <span id="toastMessage">Product added to cart!</span>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>

</body>
</html>