// API endpoints
const API_BASE_URL = ''; // Will be replaced with your domain
const API = {
    auth: `${API_BASE_URL}/api/auth.php`,
    products: `${API_BASE_URL}/api/products.php`,
    orders: `${API_BASE_URL}/api/orders.php`,
    appointments: `${API_BASE_URL}/api/appointments.php`
};

// Authentication functions
async function login(email, password) {
    try {
        const response = await fetch(API.auth, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'login',
                email,
                password
            })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data;
    } catch (error) {
        console.error('Login failed:', error);
        throw error;
    }
}

async function register(name, email, password) {
    try {
        const response = await fetch(API.auth, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'register',
                name,
                email,
                password
            })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data;
    } catch (error) {
        console.error('Registration failed:', error);
        throw error;
    }
}

async function logout() {
    try {
        const response = await fetch(API.auth, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data;
    } catch (error) {
        console.error('Logout failed:', error);
        throw error;
    }
}

// Product functions
async function getProducts(filters = {}) {
    try {
        const queryParams = new URLSearchParams(filters);
        const response = await fetch(`${API.products}?${queryParams}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data.products;
    } catch (error) {
        console.error('Failed to fetch products:', error);
        throw error;
    }
}

async function getProduct(id) {
    try {
        const response = await fetch(`${API.products}?id=${id}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data;
    } catch (error) {
        console.error('Failed to fetch product:', error);
        throw error;
    }
}

// Cart functions
function getCart() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

function addToCart(product) {
    const cart = getCart();
    const productCopy = { ...product, quantity: 1 };
    
    const existingItem = cart.find(item => item.id === product.id);
    if (existingItem) {
        existingItem.quantity = (existingItem.quantity || 1) + 1;
    } else {
        cart.push(productCopy);
    }
    
    saveCart(cart);
    updateCartCount();
}

function updateCartCount() {
    const cart = getCart();
    const count = cart.reduce((total, item) => total + (item.quantity || 1), 0);
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Order functions
async function createOrder(orderData) {
    try {
        const response = await fetch(API.orders, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data;
    } catch (error) {
        console.error('Failed to create order:', error);
        throw error;
    }
}

async function getOrders() {
    try {
        const response = await fetch(API.orders);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data.orders;
    } catch (error) {
        console.error('Failed to fetch orders:', error);
        throw error;
    }
}

// Appointment functions
async function createAppointment(appointmentData) {
    try {
        const response = await fetch(API.appointments, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(appointmentData)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data;
    } catch (error) {
        console.error('Failed to create appointment:', error);
        throw error;
    }
}

async function getAppointments() {
    try {
        const response = await fetch(API.appointments);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        return data.appointments;
    } catch (error) {
        console.error('Failed to fetch appointments:', error);
        throw error;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
    
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const productId = button.dataset.productId;
            try {
                const product = await getProduct(productId);
                addToCart(product);
                alert('Product added to cart!');
            } catch (error) {
                alert('Failed to add product to cart');
            }
        });
    });

    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const result = await login(
                    loginForm.email.value,
                    loginForm.password.value
                );
                window.location.href = '/';
            } catch (error) {
                alert('Login failed: ' + error.message);
            }
        });
    }

    // Register form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const result = await register(
                    registerForm.name.value,
                    registerForm.email.value,
                    registerForm.password.value
                );
                window.location.href = '/';
            } catch (error) {
                alert('Registration failed: ' + error.message);
            }
        });
    }

    // Logout button
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await logout();
                window.location.href = '/';
            } catch (error) {
                alert('Logout failed: ' + error.message);
            }
        });
    }
});
