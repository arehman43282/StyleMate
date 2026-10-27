<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();
require_once 'db_connect.php';
$categories = [];
$catSql = "SELECT * FROM categories WHERE Status = 1 ORDER BY Categories_name ASC";
$catResult = $conn->query($catSql);

if ($catResult) {
    while ($cat = $catResult->fetch_assoc()) {
        $cat['subcategories'] = [];
        $categories[$cat['Categories_ID']] = $cat;
    }
}

if (!empty($categories)) {
    $catIds = implode(',', array_keys($categories));
    $subSql = "SELECT * FROM subcategories WHERE Status = 1 AND Categories_ID IN ($catIds) ORDER BY Subcategories_name ASC";
    $subResult = $conn->query($subSql);
    if ($subResult) {
        while ($sub = $subResult->fetch_assoc()) {
            if (isset($categories[$sub['Categories_ID']])) {
                $categories[$sub['Categories_ID']]['subcategories'][] = $sub;
            }
        }
    }
}

$groups = ['Women' => [], 'Men' => [], 'Kids' => []];
foreach ($categories as $cat) {
    if (isset($cat['Category_Group']) && isset($groups[$cat['Category_Group']])) {
        $groups[$cat['Category_Group']][] = $cat;
    }
}
$groups['Kids'] = array_merge($groups['Kids'], $groups['Boys'] ?? [], $groups['Girls'] ?? []);

// Custom sort for Women category: Western Wear -> Accessories -> Footwear
if (!empty($groups['Women'])) {
    $western = null;
    $accessories = null;
    $footwear = null;
    $others = [];
    
    foreach ($groups['Women'] as $cat) {
        if ($cat['Categories_name'] === 'Western Wear') {
            $western = $cat;
        } elseif ($cat['Categories_name'] === 'Accessories' || $cat['Categories_name'] === 'Fashion Accessories') {
            $accessories = $cat;
        } elseif ($cat['Categories_name'] === 'Footwear') {
            $footwear = $cat;
        } else {
            $others[] = $cat;
        }
    }
    
    $groups['Women'] = [];
    if ($western) $groups['Women'][] = $western;
    foreach ($others as $cat) $groups['Women'][] = $cat;
    if ($accessories) $groups['Women'][] = $accessories;
    if ($footwear) $groups['Women'][] = $footwear;
}

// Custom sort for Men category: Topwear -> Bottomwear -> Footwear -> Fashion Accessories
if (!empty($groups['Men'])) {
    $topwear = null;
    $bottomwear = null;
    $footwear = null;
    $accessories = null;
    $others = [];

    foreach ($groups['Men'] as $cat) {
        if ($cat['Categories_name'] === 'Topwear') {
            $topwear = $cat;
        } elseif ($cat['Categories_name'] === 'Bottomwear') {
            $bottomwear = $cat;
        } elseif ($cat['Categories_name'] === 'Footwear') {
            $footwear = $cat;
        } elseif ($cat['Categories_name'] === 'Fashion Accessories') {
            $accessories = $cat;
        } else {
            $others[] = $cat;
        }
    }

    $groups['Men'] = [];
    if ($topwear) $groups['Men'][] = $topwear;
    if ($bottomwear) $groups['Men'][] = $bottomwear;
    if ($footwear) $groups['Men'][] = $footwear;
    if ($accessories) $groups['Men'][] = $accessories;
    foreach ($others as $cat) $groups['Men'][] = $cat;
}

$cart_count = 0;
// If user is not logged in, clear any existing cart session to enforce "no login, no cart" rule
if (!isset($_SESSION['user_id'])) {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
}

if (isset($_SESSION['user_id']) && isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}

$wallet_balance = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $wallet_sql = "SELECT amount FROM wallet WHERE user_id = ?";
    $wallet_stmt = $conn->prepare($wallet_sql);
    if ($wallet_stmt) {
        $wallet_stmt->bind_param("i", $user_id);
        $wallet_stmt->execute();
        $wallet_res = $wallet_stmt->get_result();
        if ($wallet_row = $wallet_res->fetch_assoc()) {
            $wallet_balance = $wallet_row['amount'];
        }
        $wallet_stmt->close();
    }
}
?>
<!-- Header Assets -->
<link rel="stylesheet" href="header.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Announcement Bar -->
<div class="announcement-bar">
    <p><?php echo $lang['free_shipping']; ?></p>
</div>

<header class="main-header">
<!-- Back to Top Button -->
<button id="backToTop" title="Back to Top">
    <i class='bx bx-up-arrow-alt'></i>
</button>
    <div class="logo-container">
        <button class="mobile-menu-toggle" aria-label="Toggle Menu">
            <i class='bx bx-menu'></i>
        </button>
        <a href="index.php" class="logo-link">
            <img src="StyleMate-removebg-preview.png" alt="StyleMate Logo" class="logo-img">
        </a>
    </div>

    <nav class="nav-container">
        <ul class="nav-menu">
            <?php if (!empty($groups['Women'])): ?>
                <li class="nav-item">
                    <a href="women_fashion.php" class="nav-link"><?php echo $lang['women']; ?></a>
                    <div class="mega-menu">
                        <div class="mega-menu-content">
                            <div class="subcategory-grid">
                                <?php foreach ($groups['Women'] as $cat): ?>
                                    <div class="subcategory-column">
                                        <a href="women_fashion.php?cat=<?php echo urlencode($cat['Categories_name']); ?>" class="sub-heading-link">
                                            <span class="sub-heading"><?php echo htmlspecialchars($cat['Categories_name']); ?></span>
                                        </a>
                                        <?php if (!empty($cat['subcategories'])): ?>
                                            <?php foreach ($cat['subcategories'] as $sub): ?>
                                                <a href="women_fashion.php?sub=<?php echo urlencode($sub['Subcategories_name']); ?>" class="sub-link"><?php echo htmlspecialchars($sub['Subcategories_name']); ?></a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </li>
            <?php else: ?>
                <li class="nav-item"><a href="women_fashion.php" class="nav-link"><?php echo $lang['women']; ?></a></li>
            <?php endif; ?>

            <?php if (!empty($groups['Men'])): ?>
                <li class="nav-item">
                    <a href="men_fashion.php" class="nav-link"><?php echo $lang['men']; ?></a>
                    <div class="mega-menu">
                        <div class="mega-menu-content">
                            <div class="subcategory-grid">
                                <?php foreach ($groups['Men'] as $cat): ?>
                                    <div class="subcategory-column">
                                        <a href="men_fashion.php?cat=<?php echo urlencode($cat['Categories_name']); ?>" class="sub-heading-link">
                                            <span class="sub-heading"><?php echo htmlspecialchars($cat['Categories_name']); ?></span>
                                        </a>
                                        <?php if (!empty($cat['subcategories'])): ?>
                                            <?php foreach ($cat['subcategories'] as $sub): ?>
                                                <a href="men_fashion.php?sub=<?php echo urlencode($sub['Subcategories_name']); ?>" class="sub-link"><?php echo htmlspecialchars($sub['Subcategories_name']); ?></a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </li>
            <?php else: ?>
                <li class="nav-item"><a href="men_fashion.php" class="nav-link"><?php echo $lang['men']; ?></a></li>
            <?php endif; ?>

            <?php if (!empty($groups['Kids'])): ?>
                <li class="nav-item">
                    <a href="kids_fashion.php" class="nav-link">Kids</a>
                    <div class="mega-menu">
                        <div class="mega-menu-content">
                            <div class="subcategory-grid">
                                <?php foreach ($groups['Kids'] as $cat): ?>
                                    <div class="subcategory-column">
                                        <a href="kids_fashion.php?cat=<?php echo urlencode($cat['Categories_name']); ?>" class="sub-heading-link">
                                            <span class="sub-heading"><?php echo htmlspecialchars($cat['Categories_name']); ?></span>
                                        </a>
                                        <?php if (!empty($cat['subcategories'])): ?>
                                            <?php foreach ($cat['subcategories'] as $sub): ?>
                                                <a href="kids_fashion.php?sub=<?php echo urlencode($sub['Subcategories_name']); ?>" class="sub-link"><?php echo htmlspecialchars($sub['Subcategories_name']); ?></a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </li>
            <?php else: ?>
                <li class="nav-item"><a href="kids_fashion.php" class="nav-link">Kids</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="header-right">
        <div class="search-container">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="query" placeholder="<?php echo $lang['search_placeholder']; ?>" class="search-input" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                <button type="submit" class="search-btn">
                    <i class='bx bx-search'></i>
                </button>
            </form>
        </div>

        <div class="user-actions">
            <!-- Language Switcher -->
            <div class="lang-dropdown">
                <div class="lang-welcome">
                    <i class='bx bx-globe'></i>
                    <span class="lang-name"><?php echo strtoupper($current_lang); ?> <i class='bx bx-chevron-down'></i></span>
                </div>
                <div class="lang-dropdown-content">
                    <a href="change_language.php?lang=en" class="dropdown-item <?php echo $current_lang == 'en' ? 'active' : ''; ?>">
                        <span>English</span>
                    </a>
                    <a href="change_language.php?lang=hi" class="dropdown-item <?php echo $current_lang == 'hi' ? 'active' : ''; ?>">
                        <span>हिन्दी</span>
                    </a>
                    <a href="change_language.php?lang=gu" class="dropdown-item <?php echo $current_lang == 'gu' ? 'active' : ''; ?>">
                        <span>ગુજરાતી</span>
                    </a>
                </div>
            </div>

            <!-- StyleMate Club Header Link -->
            <a href="StyleMate_club.php" class="action-item" style="display: flex; align-items: center; gap: 5px; text-decoration: none;">
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    background: linear-gradient(135deg, #000 0%, #333 100%);
                    color: #FFD700;
                    padding: 8px 16px;
                    border-radius: 50px;
                    font-size: 13px;
                    font-weight: 700;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                    border: 1px solid rgba(255, 215, 0, 0.3);
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'">
                    <i class='bx bxs-crown'></i>
                    <span>StyleMate Club</span>
                </div>
            </a>

            <?php if (isset($_SESSION['user_id'])): 
                $username = $_SESSION['username'];
                $hash = md5($username);
                $avatar_path = "avatar/generated/{$hash}.svg";
                
                if (!file_exists($avatar_path)) {
                    // Call python script to generate avatar
                    // $command = "python3 avatar/generate_avatar.py " . escapeshellarg($username);
                    // shell_exec($command);
                    // Use default avatar if generated one doesn't exist
                    $avatar_path = "StyleMate-removebg-preview.png"; 
                }
            ?>
                <div class="user-dropdown">
                    <div class="user-welcome">
                        <?php if (file_exists($avatar_path)): ?>
                            <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="user-avatar" onerror="this.onerror=null;this.src='StyleMate-removebg-preview.png'">
                        <?php endif; ?>
                        <div class="welcome-details">
                            <span class="welcome-text"><?php echo $lang['hello']; ?>,</span>
                            <span class="user-name"><?php echo htmlspecialchars($username); ?> <i class='bx bx-chevron-down'></i></span>
                        </div>
                    </div>
                    <div class="dropdown-content">
                        <a href="wallet.php" class="dropdown-item wallet-info">
                            <i class='bx bx-wallet'></i>
                            <span><?php echo $lang['wallet']; ?>: ₹<?php echo number_format($wallet_balance, 2); ?></span>
                        </a>
                        <a href="profile.php" class="dropdown-item">
                            <i class='bx bx-user'></i>
                            <span><?php echo $lang['profile']; ?></span>
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class='bx bx-cog'></i>
                            <span><?php echo $lang['settings']; ?></span>
                        </a>
                        <a href="StyleMate_club.php" class="dropdown-item">
                            <i class='bx bxs-crown' style="color: #d4af37;"></i>
                            <span style="color: #d4af37; font-weight: 600;">StyleMate Club</span>
                        </a>
                        <div class="dropdown-divider"></div>

                        <a href="track_order.php" class="dropdown-item">
                            <i class='bx bx-package'></i>
                            <span><?php echo $lang['track_order']; ?></span>
                        </a>
                        <a href="wishlist.php" class="dropdown-item">
                            <i class='bx bx-heart'></i>
                            <span><?php echo $lang['wishlist']; ?></span>
                        </a>
                        <a href="cart.php" class="dropdown-item">
                            <i class='bx bx-shopping-bag'></i>
                            <span><?php echo $lang['cart']; ?></span>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item logout-btn">
                            <i class='bx bx-log-out'></i>
                            <span><?php echo $lang['logout']; ?></span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="action-item login-btn" title="<?php echo $lang['login']; ?>">
                    <i class='bx bx-user'></i>
                    <span class="action-text"><?php echo $lang['login']; ?></span>
                </a>
                <a href="login.php?redirect=wishlist.php" class="action-item" title="<?php echo $lang['wishlist']; ?>" onclick="return checkLogin(event, 'wishlist.php')">
                    <i class='bx bx-heart'></i>
                </a>
                <a href="login.php?redirect=cart.php" class="action-item cart-action" title="<?php echo $lang['cart']; ?>" onclick="return checkLogin(event, 'cart.php')">
                    <i class='bx bx-shopping-bag'></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function checkLogin(e, redirectUrl) {
        <?php if (!isset($_SESSION['user_id'])): ?>
            e.preventDefault();
            alert('<?php echo addslashes(isset($lang['please_login_alert']) ? $lang['please_login_alert'] : 'Please login to access this feature'); ?>');
            window.location.href = 'login.php?redirect=' + encodeURIComponent(redirectUrl);
            return false;
        <?php endif; ?>
        return true;
    }

    // Sticky Header Logic
    window.addEventListener('scroll', () => {
        const header = document.querySelector('.main-header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile Menu Toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navContainer = document.querySelector('.nav-container');
    const navItems = document.querySelectorAll('.nav-item');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            navContainer.classList.toggle('active');
            const icon = mobileToggle.querySelector('i');
            if (navContainer.classList.contains('active')) {
                icon.className = 'bx bx-x';
            } else {
                icon.className = 'bx bx-menu';
            }
        });
    }

    // Mobile Accordion for Mega Menu
    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        if (link && window.innerWidth <= 1024) {
            link.addEventListener('click', (e) => {
                if (item.querySelector('.mega-menu')) {
                    e.preventDefault();
                    item.classList.toggle('active');
                }
            });
        }
    });

    // Back to Top Logic
    const backToTop = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    </script>
</header>
