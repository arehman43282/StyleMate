<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';
require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery | StyleMate</title>
    <style>
        .gallery-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 60vh;
        }
        .gallery-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 40px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            cursor: pointer;
            background: #fff;
        }
        .gallery-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        .gallery-item img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            display: block;
            transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .gallery-item:hover img {
            transform: scale(1.08);
        }
        .gallery-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 30px 20px 20px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .gallery-item:hover .gallery-caption {
            opacity: 1;
            transform: translateY(0);
        }
        .gallery-prod-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .gallery-prod-price {
            font-size: 1rem;
            font-weight: 500;
            color: #ffd700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="gallery-container">
        <h1 class="gallery-title"><?php echo isset($lang['gallery']) ? $lang['gallery'] : 'Gallery'; ?></h1>
        
        <div class="gallery-grid">
            <?php
            // Fetch products for the gallery
            $sql = "SELECT * FROM products WHERE Status = 1 ORDER BY RAND() LIMIT 24";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $imagePath = 'images/products/' . $row['Product_image1'];
                    // Basic check if file exists (optional, but good for cleanup)
                    if (!file_exists($imagePath)) {
                        // try to see if it's just the path relative issue or just use placeholder
                        // $imagePath = 'https://via.placeholder.com/300x400?text=Product';
                    }
                    ?>
                    <div class="gallery-item" onclick="window.location.href='product_detail.php?id=<?php echo $row['Products_ID']; ?>'">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['Products_Name']); ?>" loading="lazy">
                        <div class="gallery-caption">
                            <div class="gallery-prod-name"><?php echo htmlspecialchars($row['Products_Name']); ?></div>
                            <div class="gallery-prod-price">₹<?php echo htmlspecialchars($row['Product_Price']); ?></div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p style="text-align:center; grid-column: 1/-1;">No items found in gallery.</p>';
            }
            ?>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
</body>
</html>
