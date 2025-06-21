// Product filtering function
function filterProducts(page = 1) {
    const form = document.getElementById('filterForm');
    const productsContainer = document.querySelector('.row.gy-3.gx-3');
    const paginationContainer = document.querySelector('.row.mt-3');
    const loadingIndicator = document.getElementById('loading');
    
    // Show loading indicator
    loadingIndicator.style.display = 'block';
    
    // Get form data
    const formData = new FormData(form);
    formData.append('page', page);
    formData.append('ajax', 'true');
    
    // Convert FormData to URL parameters
    const params = new URLSearchParams(formData);
    
    // Update URL without reloading
    window.history.pushState({}, '', `?${params.toString()}`);
    
    // Fetch filtered products
    fetch(`/public/filter_new_products.php?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
            // Hide loading indicator
            loadingIndicator.style.display = 'none';
            
            // Parse the response HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update products
            const newProducts = doc.querySelector('.row.gy-3.gx-3');
            if (newProducts) {
                productsContainer.innerHTML = newProducts.innerHTML;
            }
            
            // Update pagination
            const newPagination = doc.querySelector('.row.mt-3');
            if (paginationContainer) {
                if (newPagination) {
                    paginationContainer.innerHTML = newPagination.innerHTML;
                } else {
                    paginationContainer.innerHTML = '';
                }
            }
            
            // Initialize any needed tooltips or other Bootstrap components
            initializeComponents();
        })
        .catch(error => {
            console.error('Error:', error);
            loadingIndicator.style.display = 'none';
            Swal.fire({
                title: 'Lỗi',
                text: 'Có lỗi xảy ra khi lọc sản phẩm',
                icon: 'error',
                timer: 1500
            });
        });
}

// Ensure Bootstrap JS is loaded
(function ensureBootstrapLoaded() {
    if (typeof bootstrap === 'undefined') {
        var script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js";
        script.async = false;
        document.head.appendChild(script);
    }
})();

// Initialize Bootstrap components
function initializeComponents() {
    // Khởi tạo tooltip Bootstrap nếu có sử dụng
    if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }
    // Thêm các khởi tạo component Bootstrap khác nếu cần
}

// Handle pagination clicks
document.addEventListener('click', function(e) {
    if (e.target.closest('.pagination a')) {
        e.preventDefault();
        const page = new URLSearchParams(e.target.closest('a').href.split('?')[1]).get('page');
        filterProducts(page);
    }
});


// Favorite toggle function
function handleFavorite(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    // Check if user is logged in
    if (!isLoggedIn) {
        // Lưu productId vào session bằng redirect kèm GET
        window.location.href = '/pages/login.php?pending_favorite=' + productId;
        return;
    }

    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    
    fetch('new_products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `toggle_favorite=1&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            
            // Show success message
            const isFavorited = icon.classList.contains('fas');
            Swal.fire({
                title: isFavorited ? 'Đã thêm vào yêu thích' : 'Đã xóa khỏi yêu thích',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Lỗi',
            text: 'Có lỗi xảy ra, vui lòng thử lại sau',
            icon: 'error',
            timer: 1500
        });
    });
}