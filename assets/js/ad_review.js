document.addEventListener('DOMContentLoaded', function() {
    // Show loading state
    function showLoading(selectElement, message = 'Loading...') {
        selectElement.innerHTML = `<option value="">${message}</option>`;
        selectElement.disabled = true;
    }
    
    // Hide loading state
    function hideLoading(selectElement, defaultText = '-- All --') {
        selectElement.disabled = false;
        if (selectElement.children.length === 1 && selectElement.children[0].textContent.includes('Loading')) {
            selectElement.innerHTML = `<option value="">${defaultText}</option>`;
        }
    }

    // Auto-filter function
    function autoFilter() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        // Remove empty parameters to keep URL clean
        for (let [key, value] of [...params.entries()]) {
            if (!value.trim()) {
                params.delete(key);
            }
        }
        
        // Reload page with new filter parameters
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    }

    // Handle parent category change
    document.getElementById('parent_id').addEventListener('change', function() {
        const parentId = this.value;
        const childSelect = document.getElementById('child_id');
        const productSelect = document.getElementById('product_id');
        
        // Clear and show loading
        showLoading(childSelect, 'Loading subcategories...');
        showLoading(productSelect, 'Select subcategory first');
        
        if (parentId) {
            // Load child categories
            fetch(`../public/get_child_categories.php?parent_id=${parentId}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading(childSelect);
                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_id;
                        option.textContent = category.name;
                        childSelect.appendChild(option);
                    });
                    hideLoading(productSelect);
                    // Auto filter after loading child categories
                    autoFilter();
                })
                .catch(error => {
                    console.error('Error loading child categories:', error);
                    hideLoading(childSelect);
                    hideLoading(productSelect);
                    // Still auto filter even if error
                    autoFilter();
                });
        } else {
            hideLoading(childSelect);
            hideLoading(productSelect);
            // Auto filter immediately if no parent selected
            autoFilter();
        }
    });
    
    // Handle child category change
    document.getElementById('child_id').addEventListener('change', function() {
        const childId = this.value;
        const productSelect = document.getElementById('product_id');
        
        // Show loading for products
        showLoading(productSelect, 'Loading products...');
        
        if (childId) {
            // Load products for this category
            fetch(`../public/get_products_by_category.php?category_id=${childId}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading(productSelect);
                    data.forEach(product => {
                        const option = document.createElement('option');
                        option.value = product.product_id;
                        option.textContent = product.name;
                        productSelect.appendChild(option);
                    });
                    // Auto filter after loading products
                    autoFilter();
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    hideLoading(productSelect);
                    // Still auto filter even if error
                    autoFilter();
                });
        } else {
            hideLoading(productSelect);
            // Auto filter immediately if no child selected
            autoFilter();
        }
    });
    
    // Handle product selection change
    document.getElementById('product_id').addEventListener('change', function() {
        autoFilter();
    });
    
    // Handle rating selection change
    document.getElementById('rating').addEventListener('change', function() {
        autoFilter();
    });
    
    // Handle reset button click
    document.getElementById('resetBtn').addEventListener('click', function() {
        window.location.href = window.location.pathname;
    });
});

// Function to delete review
function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review?')) {
        return;
    }
    
    fetch('../public/delete_review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ review_id: reviewId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect with success message
            window.location.href = window.location.pathname + '?deleted=success';
        } else {
            // Redirect with error message
            window.location.href = window.location.pathname + '?error=' + encodeURIComponent(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = window.location.pathname + '?error=' + encodeURIComponent('Error occurred while deleting review!');
    });
}

// Function to show notification
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 1000);

