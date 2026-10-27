// DOM Elements
const cartIcon = document.querySelector('.cart-icon');
const cartSidebar = document.querySelector('.cart-sidebar');
const closeCart = document.querySelector('.close-cart');
const cartItems = document.querySelector('.cart-items');
const cartTotal = document.querySelector('.total');
const cartCount = document.querySelector('.cart-count');

// Cart Array
let cart = [];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadCart();
    
    // Toggle Cart Sidebar
    if (cartIcon) {
        cartIcon.addEventListener('click', () => {
            cartSidebar.classList.add('active');
        });
    }

    if (closeCart) {
        closeCart.addEventListener('click', () => {
            cartSidebar.classList.remove('active');
        });
    }
});

// Toast Notification
function showToast(message) {
    const toast = document.getElementById('cartToast');
    const toastMsg = document.getElementById('toastMessage');
    if (toast && toastMsg) {
        toastMsg.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
}

// Size Selection (Product Detail Page)
function selectSize(element, size) {
    const parent = element.parentElement;
    parent.querySelector('.selected')?.classList.remove('selected');
    element.classList.add('selected');
    // Store selected size in a global variable or data attribute if needed, 
    // but we can just query the DOM in handleAddToCart
}

// Add to Cart from Product Detail Page
function handleAddToCart() {
    const btn = document.getElementById('addToCartBtn');
    if (!btn) return;

    const productId = btn.dataset.productId;
    const name = btn.dataset.name;
    const price = btn.dataset.price;
    const image = btn.dataset.image;
    const stock = btn.dataset.stock; // Optional usage

    // Check for size requirement
    const sizeGrid = document.querySelector('.size-grid');
    let selectedSize = null;

    if (sizeGrid) {
        const selectedOption = sizeGrid.querySelector('.size-option.selected');
        if (!selectedOption) {
            showToast('Please select a size first');
            return;
        }
        selectedSize = selectedOption.textContent.trim();
    }

    // Call the backend
    addToCartBackend(productId, name, price, image, selectedSize);
}

// Add to Cart from Listing Pages (and internal use)
// Signature matches: onclick="addToCart(id, name, price, image, stock, sizeRequired)"
function addToCart(productId, name, price, image, stock, sizeRequired = false) {
    // Convert string 'false'/'true' to boolean if necessary
    if (typeof sizeRequired === 'string') {
        sizeRequired = (sizeRequired === 'true');
    }

    if (sizeRequired) {
        // If this function is called directly for a size-required product without a size,
        // it means we should redirect (though women_fashion.php handles this via direct redirect now).
        // This is a fallback.
        showToast('Please select a size first');
        setTimeout(() => {
            window.location.href = `product_detail.php?id=${productId}&msg=select_size`;
        }, 1000);
        return;
    }

    addToCartBackend(productId, name, price, image, null);
}

// Core Backend Call
function addToCartBackend(productId, name, price, image, size) {
    const params = new URLSearchParams();
    params.append('product_id', productId);
    params.append('name', name);
    params.append('price', price);
    if (image) params.append('image', image);
    if (size) params.append('size', size);

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local cart array and UI
            // We'll rely on the server's cart count mostly, but we can update local state too
            // For simplicity, let's update the global count
            const countElem = document.querySelector('.cart-count');
            if (countElem) countElem.textContent = data.cartCount;
            
            showToast('Added to cart successfully!');
            
            // Optionally update local cart array logic if you want the sidebar to update instantly
            // without page reload. For now, let's assume we just update count.
            // If sidebar needs to be populated, we should fetch cart items.
            // But the original code had complex local cart logic. 
            // Let's try to preserve the sidebar update if possible.
            
            updateLocalCart(productId, name, price, image, size);
        } else {
            showToast(data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding to cart');
    });
}

function updateLocalCart(id, name, price, image, size) {
    // This attempts to sync local UI with the action
    // Note: id might need to be unique per size for local array
    const existingItem = cart.find(item => 
        item.id == id && item.size == size
    );

    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id: id,
            title: name,
            price: price,
            image: image,
            size: size || '',
            quantity: 1
        });
    }
    
    updateCartCount(); // Updates the UI number from local state
    saveCart(); // Saves to local storage
    updateCartUI(); // Updates sidebar HTML
    
    // Open sidebar
    if (cartSidebar) cartSidebar.classList.add('active');
}


// Update Cart UI (Sidebar)
function updateCartUI() {
    if (!cartItems) return;
    
    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item" data-id="${item.id}" data-size="${item.size || ''}">
            <img src="${item.image}" alt="${item.title}" onerror="this.src='images/default.jpg'">
            <div class="item-details">
                <h4>${item.title}</h4>
                ${item.size ? `<p>Size: ${item.size}</p>` : ''}
                <p>₹${parseFloat(item.price).toFixed(2)}</p>
                <div class="quantity">
                    <button class="decrease">-</button>
                    <span>${item.quantity}</span>
                    <button class="increase">+</button>
                </div>
            </div>
            <button class="remove-item">&times;</button>
        </div>
    `).join('');

    updateCartTotal();
    setupCartItemEvents();
}

// Update Cart Total
function updateCartTotal() {
    if (!cartTotal) return;
    const total = cart.reduce((sum, item) => {
        const price = parseFloat(item.price); // Assumes price is clean number or string
        return sum + (price * item.quantity);
    }, 0);

    cartTotal.textContent = `Total: ₹${total.toFixed(2)}`;
}

// Update Cart Count
function updateCartCount() {
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartCount) cartCount.textContent = count;
}

// Setup Cart Item Events
function setupCartItemEvents() {
    const cartItemElements = document.querySelectorAll('.cart-item');

    cartItemElements.forEach(item => {
        const id = item.dataset.id;
        const size = item.dataset.size;
        const increaseBtn = item.querySelector('.increase');
        const decreaseBtn = item.querySelector('.decrease');
        const removeBtn = item.querySelector('.remove-item');

        increaseBtn.addEventListener('click', () => updateQuantity(id, size, 1));
        decreaseBtn.addEventListener('click', () => updateQuantity(id, size, -1));
        removeBtn.addEventListener('click', () => removeItem(id, size));
    });
}

// Update Item Quantity
function updateQuantity(id, size, change) {
    const item = cart.find(item => item.id == id && (item.size || '') == size);
    if (item) {
        item.quantity += change;
        if (item.quantity < 1) {
            removeItem(id, size);
        } else {
            updateCartUI();
            saveCart();
            // Should also sync with server?
            // For now, let's keep local and server sync simple (add only)
        }
    }
}

// Remove Item from Cart
function removeItem(id, size) {
    cart = cart.filter(item => !(item.id == id && (item.size || '') == size));
    updateCartUI();
    updateCartCount();
    saveCart();
}

// Save Cart to LocalStorage
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Load Cart from LocalStorage
function loadCart() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
            updateCartUI();
            updateCartCount();
        } catch(e) {
            console.error('Error parsing cart', e);
        }
    }
}

// Wishlist Functionality
function toggleWishlist(productId) {
    const btn = document.querySelector(`.wishlist-btn[data-product-id="${productId}"]`);
    const icon = btn?.querySelector('i');
    
    if (!btn) return;

    fetch('toggle_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.action === 'added') {
                btn.classList.add('active');
                icon?.classList.replace('bx-heart', 'bxs-heart');
                showToast('Added to wishlist');
            } else {
                btn.classList.remove('active');
                icon?.classList.replace('bxs-heart', 'bx-heart');
                showToast('Removed from wishlist');
            }
        } else if (data.status === 'unauthorized') {
            window.location.href = 'login.php';
        }
    })
    .catch(error => console.error('Error:', error));
}

function toggleWishlistDetail(productId) {
    toggleWishlist(productId);
}
