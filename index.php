<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } 

// Show success message if order was placed
if (isset($_GET['order_success']) && isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show text-center m-0" role="alert" style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; min-width:300px; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
            <i class="fas fa-check-circle me-2"></i>' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}

require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StyleMate | Professional Fashion & Lifestyle</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <main>
        <!-- Studio Minimalist Hero Slider -->
        <section class="premium-hero">
            <div class="hero-slider-container">
                <div class="hero-slide active" style="background-image: url('https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1485230895905-ec40ba36b9bc?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1550614000-4b9519e02d48?auto=format&fit=crop&w=1920&q=80')"></div>
                <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=1920&q=80')"></div>
            </div>
            
            <div class="hero-overlay"></div>
            
            <div class="hero-content">
                <span class="hero-subtitle"><?php echo $lang['hero_subtitle']; ?></span>
                <h1 class="hero-title"><?php echo $lang['hero_title']; ?></h1>
                <p class="hero-description"><?php echo $lang['hero_description']; ?></p>
                <div class="hero-btns">
                    <a href="women_fashion.php" class="premium-btn primary"><?php echo $lang['explore_collection']; ?></a>
                    <a href="#trending" class="premium-btn outline"><?php echo $lang['lookbook']; ?></a>
                </div>
            </div>

            <div class="slider-dots">
                <div class="dot active"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </section>

        <!-- StyleMate Club Banner -->
        <!-- Moved to bottom as requested -->

        <script>
            // Hero Slider Logic
            const slides = document.querySelectorAll('.hero-slide');
            const dots = document.querySelectorAll('.dot');
            let currentSlide = 0;

            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                dots[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            }

            // Auto slide every 5 seconds
            let slideInterval = setInterval(nextSlide, 5000);

            // Manual dot navigation
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    clearInterval(slideInterval);
                    slides[currentSlide].classList.remove('active');
                    dots[currentSlide].classList.remove('active');
                    currentSlide = index;
                    slides[currentSlide].classList.add('active');
                    dots[currentSlide].classList.add('active');
                    slideInterval = setInterval(nextSlide, 6000);
                });
            });
        </script>

        <!-- Automatic Sliding Banner -->
        <section class="infinite-slider">
            <div class="slider-track">
                <!-- First set of slides - Curated Editorial Style -->
                <div class="slide"><img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=600&q=80" alt="Style 1"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?auto=format&fit=crop&w=600&q=80" alt="Style 2"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=600&q=80" alt="Style 3"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=600&q=80" alt="Style 4"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?auto=format&fit=crop&w=600&q=80" alt="Style 5"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1485230895905-ec40ba36b9bc?auto=format&fit=crop&w=600&q=80" alt="Style 6"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80" alt="Style 7"></div>
                <!-- Duplicate set for infinite loop -->
                <div class="slide"><img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=600&q=80" alt="Style 1"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?auto=format&fit=crop&w=600&q=80" alt="Style 2"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=600&q=80" alt="Style 3"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=600&q=80" alt="Style 4"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?auto=format&fit=crop&w=600&q=80" alt="Style 5"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1485230895905-ec40ba36b9bc?auto=format&fit=crop&w=600&q=80" alt="Style 6"></div>
                <div class="slide"><img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80" alt="Style 7"></div>
            </div>
        </section>

        <!-- Studio Features -->
        <section class="container">
            <div class="feature-grid-home">
                <div class="feature-home-item">
                    <i class='bx bx-check-shield'></i>
                    <h3><?php echo $lang['quality_assured']; ?></h3>
                    <p><?php echo $lang['quality_desc']; ?></p>
                </div>
                <div class="feature-home-item">
                    <i class='bx bx-globe'></i>
                    <h3><?php echo $lang['worldwide_shipping']; ?></h3>
                    <p><?php echo $lang['shipping_desc']; ?></p>
                </div>
                <div class="feature-home-item">
                    <i class='bx bx-refresh'></i>
                    <h3><?php echo $lang['easy_returns']; ?></h3>
                    <p><?php echo $lang['returns_desc']; ?></p>
                </div>
                <div class="feature-home-item">
                    <i class='bx bx-support'></i>
                    <h3><?php echo $lang['expert_support']; ?></h3>
                    <p><?php echo $lang['support_desc']; ?></p>
                </div>
            </div>
        </section>

        <!-- Shop by Category -->
        <section class="category-section container">
            <div class="section-header">
                <h2 class="section-title"><?php echo $lang['shop_by_category']; ?></h2>
                <p class="section-subtitle"><?php echo $lang['category_subtitle']; ?></p>
            </div>
            <div class="category-grid">
                <a href="women_fashion.php" class="category-card">
                    <img src="images/categories/women.jpg" alt="Women's Fashion" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1000&q=80'">
                    <div class="category-overlay">
                        <span><?php echo $lang['new_season']; ?></span>
                        <h3><?php echo $lang['women_collection']; ?></h3>
                    </div>
                </a>
                <a href="men_fashion.php" class="category-card">
                    <img src="https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?auto=format&fit=crop&w=1000&q=80" alt="Men's Fashion">
                    <div class="category-overlay">
                        <span><?php echo $lang['essentials']; ?></span>
                        <h3><?php echo $lang['men_collection']; ?></h3>
                    </div>
                </a>
                <a href="kids_fashion.php" class="category-card">
                    <img src="https://images.unsplash.com/photo-1519457431-44ccd64a579b?auto=format&fit=crop&w=1000&q=80" alt="Kids' Fashion">
                    <div class="category-overlay">
                        <span><?php echo (isset($lang['kids_wear']) ? $lang['kids_wear'] : 'Kids Wear'); ?></span>
                        <h3><?php echo (isset($lang['kids_collection']) ? $lang['kids_collection'] : 'Kids Collection'); ?></h3>
                    </div>
                </a>
            </div>
        </section>

        <!-- AI Skin Tone Finder Banner -->
        <section class="container" style="margin-bottom: 80px;">
            <div style="background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); border-radius: 20px; overflow: hidden; display: flex; flex-direction: row; min-height: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
                <div style="flex: 1; padding: 60px; display: flex; flex-direction: column; justify-content: center;">
                    <div style="display: inline-flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <span style="background: #000; color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;">New Feature</span>
                        <span style="color: #666; font-size: 14px; font-weight: 600;"><i class='bx bx-brain'></i> AI Powered</span>
                    </div>
                    <h2 style="font-size: 42px; font-weight: 800; margin-bottom: 20px; line-height: 1.1; color: #000;">Find Your Perfect Match with AI</h2>
                    <p style="font-size: 18px; color: #555; margin-bottom: 30px; line-height: 1.6; max-width: 500px;">
                        Our advanced AI scans your skin tone to recommend the perfect color palette for your outfits. Get personalized suggestions for clothes, accessories, and footwear.
                    </p>
                    <div style="display: flex; gap: 15px;">
                        <a href="skin_tone_recommend.php" class="premium-btn primary" style="background: #000; color: #fff; border: none; padding: 15px 35px;">
                            <i class='bx bx-scan' style="margin-right: 8px;"></i> Try Skin Tone Finder
                        </a>
                    </div>
                </div>
                <div style="flex: 1; background-image: url('https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?auto=format&fit=crop&w=1000&q=80'); background-size: cover; background-position: center; min-height: 300px;"></div>
            </div>
        </section>

        <!-- Trending Products -->
        <section id="trending" class="container" style="padding-bottom: 100px;">
            <div class="section-header">
                <h2 class="section-title"><?php echo $lang['trending_now']; ?></h2>
                <p class="section-subtitle"><?php echo $lang['trending_subtitle']; ?></p>
            </div>

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

                $prodSql = "SELECT p.*, c.Categories_name, c.Categories_name_hi, c.Categories_name_gu, 
                                   AVG(r.Rating_Value) as avg_rating,
                                   COUNT(r.Products_ID) as total_reviews
                           FROM products p 
                           LEFT JOIN categories c ON p.Categories_ID = c.Categories_ID
                           LEFT JOIN Products_Ratings r ON p.Products_ID = r.Products_ID 
                           WHERE p.Status = 1 
                           GROUP BY p.Products_ID 
                           ORDER BY avg_rating DESC, p.Products_ID DESC 
                           LIMIT 4";
                $prodResult = $conn->query($prodSql);
                
                while($product = $prodResult->fetch_assoc()): 
                    $current_lang = $_SESSION['lang'] ?? 'en';
                    $idVal = $product['Products_ID'];
                    
                    // Localization
                    $displayName = $product['Products_Name'];
                    if ($current_lang === 'hi' && !empty($product['Products_Name_hi'])) {
                        $displayName = $product['Products_Name_hi'];
                    } elseif ($current_lang === 'gu' && !empty($product['Products_Name_gu'])) {
                        $displayName = $product['Products_Name_gu'];
                    }
                    
                    $price = (float)($product['price'] ?? 0);
                    $imgVal = $product['image'];
                    if (strpos($imgVal, 'http') === 0) {
                        $imgSrc = $imgVal;
                    } else {
                        $imgSrc = 'images/products/' . ($imgVal ?: 'placeholder.svg');
                    }
                    $stockVal = (int)($product['Stock'] ?? 1000);
                    $isInWishlist = in_array($idVal, $wishlist_items);
                    $show_sizes = true;
                    $prod_name_lower = strtolower($product['Products_Name']);
                    $cat_name = isset($product['Categories_name']) ? strtolower($product['Categories_name']) : "";
                    
                    if (strpos($prod_name_lower, 'watch') !== false || strpos($cat_name, 'accessory') !== false || 
                        strpos($prod_name_lower, 'bag') !== false || strpos($prod_name_lower, 'sunglass') !== false ||
                        strpos($prod_name_lower, 'belt') !== false || strpos($prod_name_lower, 'wallet') !== false ||
                        strpos($prod_name_lower, 'jewelry') !== false || strpos($prod_name_lower, 'perfume') !== false ||
                        strpos($prod_name_lower, 'saree') !== false || strpos($cat_name, 'saree') !== false) {
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

                            <?php if(isset($product['Stock']) && (int)$product['Stock'] < 10 && (int)$product['Stock'] > 0) { ?>
                                <span class="stock-status low-stock"><?php echo (isset($lang['only']) ? $lang['only'] : 'Only'); ?> <?php echo (int)$product['Stock']; ?> <?php echo (isset($lang['left']) ? $lang['left'] : 'Left'); ?></span>
                            <?php } elseif(isset($product['Stock']) && (int)$product['Stock'] <= 0) { ?>
                                <span class="stock-status out-of-stock"><?php echo $lang['out_of_stock']; ?></span>
                            <?php } ?>

                            <a href="product_detail.php?id=<?php echo $idVal; ?>" class="product-link">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($displayName); ?>" 
                                     onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                                <div class="quick-view">
                                    <i class='bx bx-search'></i>
                                    <span><?php echo (isset($lang['quick_view']) ? $lang['quick_view'] : 'Quick View'); ?></span>
                                </div>
                            </a>
                        </div>
                        <div class="product-details">
                <div class="product-rating">
                    <?php
                    $avg_rating = round($product['avg_rating'] ?? 0, 1);
                    $total_reviews = $product['total_reviews'] ?? 0;
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
                            <a href="product_detail.php?id=<?php echo $idVal; ?>" style="text-decoration:none;">
                                <h3><?php echo htmlspecialchars($displayName); ?></h3>
                            </a>
                            <div class="product-price">₹<?php echo number_format($price, 2); ?></div>
                            <?php if ($show_sizes): ?>
                                <button class="add-to-cart" onclick="window.location.href='product_detail.php?id=<?php echo $idVal; ?>&msg=select_size'">
                                    <?php echo isset($lang['add_to_cart']) ? $lang['add_to_cart'] : 'Add to Cart'; ?>
                                </button>
                            <?php else: ?>
                                <button class="add-to-cart" onclick="addToCart(<?php echo $idVal; ?>, '<?php echo addslashes($displayName); ?>', <?php echo $price; ?>, '<?php echo $imgSrc; ?>', <?php echo $stockVal; ?>, 'false')">
                                    <?php echo $lang['add_to_cart']; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- StyleMate Club Premium Banner -->
        <section class="container" style="margin-top: 100px; margin-bottom: 100px;">
            <div style="
                background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
                color: #ffffff;
                border-radius: 24px;
                padding: 80px 60px;
                position: relative;
                overflow: hidden;
                box-shadow: 0 30px 60px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                border: 1px solid #333;
            ">
                <!-- Background Decoration -->
                <div style="
                    position: absolute;
                    top: 0;
                    right: 0;
                    width: 600px;
                    height: 100%;
                    background: radial-gradient(circle at center, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
                    pointer-events: none;
                "></div>
                
                <div style="max-width: 600px; position: relative; z-index: 2;">
                    <div style="
                        display: inline-flex;
                        align-items: center;
                        gap: 10px;
                        background: rgba(255, 255, 255, 0.1);
                        padding: 8px 16px;
                        border-radius: 30px;
                        margin-bottom: 30px;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        backdrop-filter: blur(10px);
                    ">
                        <i class='bx bxs-crown' style="color: #FFD700; font-size: 18px;"></i>
                        <span style="font-size: 13px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #FFD700;">Premium Membership</span>
                    </div>
                    
                    <h2 style="
                        font-size: 56px;
                        font-weight: 800;
                        margin-bottom: 24px;
                        line-height: 1.1;
                        background: linear-gradient(90deg, #fff, #ccc);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    ">Join the StyleMate Club</h2>
                    
                    <p style="
                        font-size: 18px;
                        color: #a0a0a0;
                        margin-bottom: 40px;
                        line-height: 1.6;
                        max-width: 480px;
                    ">
                        Elevate your shopping experience. Unlock <span style="color: #fff; font-weight: 600;">20% OFF</span> on all orders and enjoy <span style="color: #fff; font-weight: 600;">Free Priority Shipping</span> over ₹999.
                    </p>
                    
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <a href="StyleMate_club.php" class="premium-btn primary" style="
                            background: #fff;
                            color: #000;
                            border: none;
                            padding: 16px 40px;
                            font-size: 16px;
                            font-weight: 700;
                            letter-spacing: 0.5px;
                            transition: transform 0.3s ease;
                        " onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            Explore Benefits
                        </a>
                        <span style="color: #666; font-size: 14px;">Limited Time Offer</span>
                    </div>
                </div>

                <!-- Visual Element -->
                <div class="StyleMate-visual-element">
                    <div style="
                        width: 300px;
                        height: 300px;
                        border-radius: 50%;
                        background: linear-gradient(45deg, #FFD700, #FFA500);
                        filter: blur(60px);
                        opacity: 0.15;
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                    "></div>
                    <i class='bx bxs-diamond' style="
                        font-size: 250px;
                        background: linear-gradient(135deg, #ffffff 0%, #888888 100%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        filter: drop-shadow(0 20px 40px rgba(0,0,0,0.5));
                        transform: rotate(-15deg);
                        display: block;
                    "></i>
                </div>
            </div>
        </section>
    </main>

    <!-- StyleMate AI Chatbot -->
    <style>
        #StyleMate-chatbot-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 10000;
            font-family: 'Inter', sans-serif;
        }
        #chatbot-toggle-btn {
            width: 60px;
            height: 60px;
            background: #000;
            color: #fff;
            border-radius: 50%;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        #chatbot-toggle-btn:hover {
            transform: scale(1.1);
        }
        #chatbot-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 500px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform-origin: bottom right;
            transform: scale(0);
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            border: 1px solid #f0f0f0;
        }
        #chatbot-window.active {
            transform: scale(1);
            opacity: 1;
            pointer-events: all;
        }
        .chatbot-header {
            background: #000;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }
        .chatbot-header span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #chatbot-close-btn {
            background: transparent;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 18px;
        }
        #chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .message {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
        }
        .bot-message {
            background: #fff;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .user-message {
            background: #000;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }
        .chatbot-input-area {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        #chatbot-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }
        #chatbot-input:focus {
            border-color: #000;
        }
        #chatbot-send-btn {
            background: #000;
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bot-action-link {
            display: block;
            margin-top: 8px;
            padding: 8px 12px;
            background: #f0f0f0;
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            transition: background 0.2s;
            border: 1px solid #e0e0e0;
        }
        .bot-action-link:hover {
            background: #e0e0e0;
        }
        .bot-action-link img {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            vertical-align: middle;
            margin-right: 8px;
            object-fit: cover;
        }
    </style>

    <div id="StyleMate-chatbot-container">
        <div id="chatbot-window">
            <div class="chatbot-header">
                <span><i class="fas fa-robot"></i> StyleMate AI</span>
                <button id="chatbot-close-btn"><i class="fas fa-times"></i></button>
            </div>
            <div id="chatbot-messages">
                <div class="message bot-message">Hello! I'm your StyleMate AI assistant. Ask me about products, orders, or policies.</div>
            </div>
            <div class="chatbot-input-area">
                <input type="text" id="chatbot-input" placeholder="Type a message...">
                <button id="chatbot-send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <button id="chatbot-toggle-btn">
            <i class="fas fa-comment-dots"></i>
        </button>
    </div>

    <script>
        const chatWindow = document.getElementById('chatbot-window');
        const toggleBtn = document.getElementById('chatbot-toggle-btn');
        const closeBtn = document.getElementById('chatbot-close-btn');
        const sendBtn = document.getElementById('chatbot-send-btn');
        const input = document.getElementById('chatbot-input');
        const messagesContainer = document.getElementById('chatbot-messages');

        toggleBtn.addEventListener('click', () => {
            chatWindow.classList.toggle('active');
            if (chatWindow.classList.contains('active')) {
                input.focus();
            }
        });

        closeBtn.addEventListener('click', () => {
            chatWindow.classList.remove('active');
        });

        function addMessage(text, isUser = false) {
            const div = document.createElement('div');
            div.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
            div.textContent = text;
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function addActions(actions) {
            if (!actions || actions.length === 0) return;
            
            const div = document.createElement('div');
            div.className = 'message bot-message';
            div.style.background = 'transparent';
            div.style.boxShadow = 'none';
            div.style.padding = '0';
            
            actions.forEach(action => {
                const link = document.createElement('a');
                link.className = 'bot-action-link';
                link.href = action.link;
                
                let html = '';
                if (action.image) {
                    html += `<img src="${action.image}" alt="Product">`;
                }
                html += `<span>${action.text}</span>`;
                
                link.innerHTML = html;
                div.appendChild(link);
            });
            
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        async function sendMessage() {
            const text = input.value.trim();
            if (!text) return;

            addMessage(text, true);
            input.value = '';
            
            // Show typing indicator?
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message bot-message';
            loadingDiv.innerHTML = '<i class="fas fa-ellipsis-h fa-fade"></i>';
            loadingDiv.id = 'typing-indicator';
            messagesContainer.appendChild(loadingDiv);

            try {
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });
                const data = await response.json();
                
                // Remove loading
                const loader = document.getElementById('typing-indicator');
                if (loader) loader.remove();

                if (data.response) {
                    addMessage(data.response);
                }
                if (data.actions) {
                    addActions(data.actions);
                }
            } catch (error) {
                console.error('Error:', error);
                const loader = document.getElementById('typing-indicator');
                if (loader) loader.remove();
                addMessage("Sorry, I'm having trouble connecting to the server.");
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    </script>

    <?php require_once 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>
