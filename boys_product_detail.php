<?php
session_start();
require_once 'db_connect.php';

// Get product ID from URL and sanitize it
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details from boys_fashion table
$sql = "SELECT * FROM boys_fashion WHERE products_id = " . $product_id;
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    // If product not found in boys_fashion, stay on boys page
    header('Location: boys_fashion.php');
    exit;
}

// Fetch average rating and total reviews
$rating_sql = "SELECT AVG(Rating_Value) as avg_rating, COUNT(*) as total_reviews FROM Products_Ratings WHERE Products_ID = " . $product_id;
$rating_result = mysqli_query($conn, $rating_sql);
$rating_data = mysqli_fetch_assoc($rating_result);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;

// Fetch all reviews
$reviews_sql = "SELECT r.*, u.Username as user_name FROM Products_Ratings r JOIN users u ON r.User_ID = u.User_ID WHERE r.Products_ID = $product_id ORDER BY r.Date DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Amazon+Ember:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/product_detail.css">
    <style>
        .rating-section {
            margin-top: 40px;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .rating-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .avg-rating-box {
            background: #fcfcfc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            min-width: 200px;
        }

        .avg-rating-display .avg-rating-stars {
            font-size: 24px;
            color: #ffb400;
            margin-bottom: 10px;
        }

        .avg-rating-display .avg-rating-number {
            font-size: 32px;
            margin: 0;
            color: #111;
            font-weight: 700;
        }

        .avg-rating-display .total-reviews-count {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }

        .review-form-container {
            flex: 1;
            min-width: 300px;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e7e7e7;
        }

        .review-form-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        .stars-wrapper {
            margin-bottom: 20px;
            text-align: center;
        }

        .stars-container {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 10px;
        }

        .stars-container input {
            display: none;
        }

        .stars-container label {
            font-size: 36px;
            color: #e9e9e9;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .stars-container label:hover,
        .stars-container label:hover ~ label,
        .stars-container input:checked ~ label {
            color: #ffb400;
            transform: scale(1.1);
        }

        .rating-label-text {
            display: block;
            margin-top: 10px;
            font-weight: 500;
            color: #666;
            min-height: 20px;
        }

        .textarea-container {
            position: relative;
            margin-bottom: 15px;
        }

        .review-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .review-form textarea:focus {
            outline: none;
            border-color: #ffb400;
            box-shadow: 0 0 0 3px rgba(255,180,0,0.1);
        }

        .char-counter {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 12px;
            color: #999;
        }

        .submit-review-btn {
            background: #ffd814;
            border: 1px solid #fcd200;
            color: #0f1111;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s;
            font-size: 16px;
        }

        .submit-review-btn:hover {
            background: #f7ca00;
            border-color: #f2c200;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .submit-review-btn:disabled {
            background: #eee;
            border-color: #ddd;
            color: #999;
            cursor: not-allowed;
        }

        .reviews-list {
            margin-top: 40px;
        }

        .review-item {
            padding: 25px 0;
            border-bottom: 1px solid #f0f0f0;
            animation: fadeIn 0.5s ease-out;
        }

        .review-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            background: #e7e9ec;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
        }

        .review-user-name {
            font-weight: 600;
            color: #111;
        }

        .review-meta {
            font-size: 0.9rem;
            color: #565959;
            margin-bottom: 8px;
        }

        .review-stars-small {
            color: #ffb400;
            font-size: 14px;
            margin-right: 8px;
        }

        .review-text-content {
            line-height: 1.5;
            color: #333;
            margin: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Amazon-style layout improvements */
        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        @media (max-width: 992px) {
            .product-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <main>
        <div class="breadcrumb-container">
            <div class="container">
                <div class="breadcrumb">
                    <a href="index.php">Home</a>
                    <i class='bx bx-chevron-right'></i>
                    <a href="boys_fashion.php">Boys' Fashion</a>
                    <i class='bx bx-chevron-right'></i>
                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                </div>
            </div>
        </div>
        <div class="product-detail-container">
            <div class="product-layout">
            <div class="gallery-container">
                <div class="thumbnail-container vertical">
                    <?php
                        $image_urls = array();
                        
                        // Add main image
                        if (!empty($product['image_url'])) {
                            $image_urls[] = $product['image_url'];
                        }
                        
                        // Add additional images from database if they exist
                        for ($i = 1; $i <= 5; $i++) {
                            $colName = "image$i";
                            if (isset($product[$colName]) && !empty($product[$colName])) {
                                $image_urls[] = $product[$colName];
                            }
                        }
                        
                        // Filter out empty values and duplicates
                        $image_urls = array_filter(array_unique($image_urls));
                    
                        // Display thumbnails
                        foreach($image_urls as $index => $image_url) {
                            $active_class = ($index === 0) ? ' active' : '';
                            echo '<img src="images/products/' . $image_url . '" 
                                alt="' . htmlspecialchars($product['name']) . '" 
                                class="thumbnail' . $active_class . '"
                                onclick="changeMainImage(this.src)">';
                        }
                    ?>
                </div>
                <div class="main-image-container">
                    <img src="images/products/<?php echo $product['image_url']; ?>" 
                        alt="<?php echo htmlspecialchars($product['name']); ?>" 
                        class="main-image" 
                        id="mainImage">
                </div>
            </div>

            <div class="product-info">
                <h1><?php echo $product['name']; ?></h1>
                <div class="price-section">
                    <p class="price">₹<?php echo number_format($product['price'], 2); ?></p>
                </div>

                <div class="product-section">
                    <h2>About this item</h2>
                    <ul class="feature-list">
                        <?php 
                        if (!empty($product['description'])): 
                            // Split description by newlines, double colons, double pipes, or single pipes
                            $features = preg_split('/\n|::|\|\||\|/', $product['description']);
                            foreach ($features as $feature):
                                $feature = trim($feature);
                                if (!empty($feature)):
                        ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php 
                                endif;
                            endforeach;
                        endif; 
                        ?>
                        <?php 
                        $fields = [
                            'care_instructions' => 'Care Instructions',
                            'material' => 'Material',
                            'pattern' => 'Pattern',
                            'fit_type' => 'Fit Type'
                        ];
                        foreach ($fields as $field => $label) {
                            if (!empty($product[$field])) {
                                echo "<li>$label: " . htmlspecialchars($product[$field]) . "</li>";
                            }
                        }
                        if (empty($product['description']) && empty($product['care_instructions']) && empty($product['material'])) {
                            echo "<li>High-quality product from our collection</li>";
                            echo "<li>Premium materials and craftsmanship</li>";
                            echo "<li>Perfect for your everyday needs</li>";
                        }
                        ?>
                    </ul>
                </div>

                <div class="product-section">
                    <h2>Product Specifications</h2>
                    <table class="specifications-table">
                        <?php
                        $specs = [
                            'Brand' => ['brand', 'Brand'],
                            'Material' => ['material', 'Material'],
                            'Color' => ['color', 'Color'],
                            'Pattern' => ['pattern', 'Pattern'],
                            'Care Instructions' => ['care_instructions', 'Care_Instructions']
                        ];

                        foreach ($specs as $label => $keys) {
                            $value = null;
                            foreach ($keys as $key) {
                                if (!empty($product[$key])) {
                                    $value = $product[$key];
                                    break;
                                }
                            }
                            if ($value !== null) {
                                echo "<tr><th>$label</th><td>" . htmlspecialchars($value) . "</td></tr>";
                            }
                        }

                        // Custom specifications from database
                        $custom_specs = !empty($product['Specifications']) ? $product['Specifications'] : (!empty($product['specifications']) ? $product['specifications'] : (!empty($product['description']) ? $product['description'] : ''));
                        if (!empty($custom_specs)) {
                            $lines = preg_split('/\n|::|\|\||\|/', $custom_specs);
                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (empty($line)) continue;
                                if (strpos($line, ':') !== false) {
                                    list($label, $value) = explode(':', $line, 2);
                                    echo "<tr><th>" . htmlspecialchars(trim($label)) . "</th><td>" . htmlspecialchars(trim($value)) . "</td></tr>";
                                } else {
                                    echo "<tr><th>Detail</th><td>" . htmlspecialchars($line) . "</td></tr>";
                                }
                            }
                        }
                        ?>
                    </table>
                </div>

                <div class="size-selection">
                    <h2>Size</h2>
                    <div class="size-buttons">
                        <?php
                        $db_sizes = isset($product['sizes']) ? $product['sizes'] : (isset($product['size']) ? $product['size'] : '');
                        if (!empty($db_sizes)) {
                            $sizes = preg_split('/[,\s]+/', $db_sizes);
                            $sizes = array_filter(array_map('trim', $sizes));
                            foreach ($sizes as $size) {
                                echo '<button class="size-btn" onclick="selectSize(this)">' . htmlspecialchars($size) . '</button>';
                            }
                        } else {
                            $sizes = array('S', 'M', 'L', 'XL');
                            foreach ($sizes as $size) {
                                echo '<button class="size-btn" onclick="selectSize(this)">' . $size . '</button>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="buy-box">
                    <p class="stock-status">In Stock (<?php echo $product['stock']; ?> items available)</p>
                    <div class="delivery-info">
                        <i class="fas fa-map-marker-alt"></i> Deliver to your Location
                        <br>
                        <i class="fas fa-truck"></i> FREE Delivery within 4-5 days
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                    <button class="buy-now-btn">Buy Now</button>
                </div>
            </div>
        </div>

        <div class="rating-section">
            <h2>Customer Reviews</h2>
            
            <div class="rating-header-flex">
                <div class="avg-rating-box">
                    <div class="avg-rating-display">
                        <div class="avg-rating-stars">
                            <?php 
                            for($i=1; $i<=5; $i++) {
                                echo $i <= $avg_rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <div class="avg-rating-number"><?php echo $avg_rating; ?> <small style="font-size: 16px; color: #666; font-weight: normal;">out of 5</small></div>
                        <div class="total-reviews-count"><?php echo $total_reviews; ?> global ratings</div>
                    </div>
                </div>


            </div>

            <div class="reviews-list">
                <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                    <?php while($review = mysqli_fetch_assoc($reviews_result)): ?>
                        <div class="review-item">
                            <div class="review-user-info">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="review-user-name"><?php echo htmlspecialchars($review['user_name']); ?></span>
                            </div>
                            <div class="review-meta">
                                <span class="review-stars-small">
                                    <?php 
                                    for($i=1; $i<=5; $i++) {
                                        echo $i <= $review['Rating_Value'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </span>
                                <span style="font-weight: 500;">Verified Purchase</span>
                                <span style="color: #565959; margin-left: 10px;">Reviewed on <?php echo date('F d, Y', strtotime($review['Date'])); ?></span>
                            </div>
                            <p class="review-text-content"><?php echo nl2br(htmlspecialchars($review['Review_Text'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #666; padding: 40px;">
                        <i class="far fa-comment-dots" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                        <p>No reviews yet. Be the first to share your thoughts!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    </main>

    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
                if (thumb.src === src) {
                    thumb.classList.add('active');
                }
            });
        }

        function selectSize(btn) {
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            // Store selected size for cart
            selectedSize = btn.textContent;
        }

        let selectedSize = '';

        document.querySelectorAll('.add-to-cart-btn, .buy-now-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const selectedSize = document.querySelector('.size-btn.selected');
                if (!selectedSize) {
                    alert('Please select a size first');
                    return;
                }
                alert('Product added to cart!');
            });
        });

        // Handle rating form submission
        const ratingForm = document.getElementById('ratingForm');
        if (ratingForm) {
            const ratingText = document.getElementById('ratingText');
            const reviewTextarea = document.getElementById('reviewText');
            const currentChar = document.getElementById('currentChar');
            const ratingLabels = {
                '5': 'Excellent',
                '4': 'Good',
                '3': 'Average',
                '2': 'Poor',
                '1': 'Very Poor'
            };

            // Handle star hover effects and text update
            const stars = document.querySelectorAll('.stars-container label');
            const radioButtons = document.querySelectorAll('.stars-container input');

            stars.forEach(star => {
                star.addEventListener('mouseover', function() {
                    const rating = this.getAttribute('for').replace('star', '');
                    ratingText.textContent = ratingLabels[rating];
                    ratingText.style.color = '#ffb400';
                });

                star.addEventListener('mouseout', function() {
                    const checkedRating = document.querySelector('.stars-container input:checked');
                    if (checkedRating) {
                        ratingText.textContent = ratingLabels[checkedRating.value];
                        ratingText.style.color = '#ffb400';
                    } else {
                        ratingText.textContent = 'Select your rating';
                        ratingText.style.color = '#666';
                    }
                });
            });

            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    ratingText.textContent = ratingLabels[this.value];
                    ratingText.style.color = '#ffb400';
                });
            });

            // Character counter for textarea
            if (reviewTextarea) {
                reviewTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    currentChar.textContent = length;
                    if (length >= 450) {
                        currentChar.style.color = '#b12704';
                    } else {
                        currentChar.style.color = '#999';
                    }
                });
            }

            ratingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const ratingValue = formData.get('rating');

                if (!ratingValue) {
                    ratingText.textContent = 'Please select stars!';
                    ratingText.style.color = '#b12704';
                    return;
                }

                const submitBtn = this.querySelector('.submit-review-btn');
                const originalBtnText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';

                fetch('submit_rating.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        submitBtn.style.background = '#4CAF50';
                        submitBtn.style.color = 'white';
                        submitBtn.textContent = 'Submitted!';
                        
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert(data.message || 'Error submitting review');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                });
            });
        }
    </script>
</body>
</html>
