// ============================================
// FILE: assets/js/main.js
// ============================================

// Add to Cart with AJAX
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', async function(e) {
        e.preventDefault();

        const productId = this.dataset.productId;
        const originalText = this.innerHTML;

        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        this.disabled = true;

        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=add&product_id=${productId}`
            });

            const data = await response.json();

            if (data.success) {
                // Update cart count in navbar
                const cartBadge = document.querySelector('.cart-count');
                if (cartBadge) {
                    cartBadge.textContent = data.cart_count;
                    cartBadge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                }

                // Show success notification
                showNotification('Product added to cart!', 'success');

                // Reset button
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            }
        } catch (error) {
            console.error('Error:', error);
            this.innerHTML = originalText;
            this.disabled = false;
            showNotification('Failed to add product', 'error');
        }
    });
});

// Update cart quantity (AJAX)
document.querySelectorAll('.update-cart-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                location.reload(); // Reload to update totals
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});

// Product search with debounce
let searchTimeout;
const searchInput = document.querySelector('input[name="search"]');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 500);
    });
}

// Quantity input validation
document.querySelectorAll('input[type="number"][name="quantity"]').forEach(input => {
    input.addEventListener('change', function() {
        const max = parseInt(this.max);
        const min = parseInt(this.min);
        let value = parseInt(this.value);

        if (isNaN(value)) value = min;
        if (value > max) this.value = max;
        if (value < min) this.value = min;
    });
});

// Delete confirmation for all delete links
document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

// Price calculation on cart page (client-side)
function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.cart-item-total').forEach(el => {
        subtotal += parseFloat(el.textContent.replace('$', ''));
    });

    const shipping = 5.00;
    const tax = subtotal * 0.10;
    const total = subtotal + shipping + tax;

    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;
}

// Notification system
function showNotification(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
    <div class="d-flex">
    <div class="toast-body">
    ${message}
    </div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
    `;

    document.querySelector('.toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Loading spinner
function showLoading() {
    let spinner = document.querySelector('.spinner-overlay');
    if (!spinner) {
        spinner = document.createElement('div');
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
        document.body.appendChild(spinner);
    }
    spinner.style.display = 'flex';
}

function hideLoading() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        // Email validation
        const emailField = this.querySelector('input[type="email"]');
        if (emailField && emailField.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value)) {
                emailField.classList.add('is-invalid');
                isValid = false;
            }
        }

        // Password confirmation
        const password = this.querySelector('input[name="password"]');
        const confirmPassword = this.querySelector('input[name="confirm_password"]');
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            confirmPassword.classList.add('is-invalid');
            isValid = false;
            showNotification('Passwords do not match', 'error');
        }

        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields correctly', 'error');
        }
    });
});

// Price formatting
function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

// Initialize Bootstrap tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});

// Logout confirmation
document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
});
