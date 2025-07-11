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
    fetch(`/public/filter_type_products.php?${params.toString()}`)
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
document.addEventListener('click', function (e) {
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
        // Lấy thông tin sản phẩm từ DOM
        const card = event.currentTarget.closest('.product-card');
        const img = card.querySelector('.product-image')?.src || '';
        const name = card.querySelector('.product-title')?.textContent || '';
        const price = card.querySelector('.current-price')?.textContent || '';
        Swal.fire({
            title: '',
            html: `
                 <div style='background:#fff;border-radius:18px;box-shadow:0 4px 32px rgba(44,62,80,0.10);padding:24px 18px 18px 18px;max-width:350px; max-height:400px;margin:0 auto;'>
                    <div style='font-size:20px;font-weight:800;color:#222;text-align:center;margin-bottom:16px;letter-spacing:0.5px;'>Please log in to continue</div>
                    <div style='display:flex;align-items:center;gap:18px;margin-bottom:18px;'>
                        <img src='${img}' style='width:90px;height:90px;object-fit:cover;border-radius:16px;border:2px solid #eee;box-shadow:0 2px 12px rgba(44,62,80,0.10);background:#fafafa;'>
                        <div style='text-align:left;max-width:200px;'>
                            <div style='font-size:19px;font-weight:800;margin-bottom:4px;line-height:1.2;word-break:break-word;color:#222;'>${name}</div>
                            <div style='color:#e74c3c;font-weight:800;font-size:18px;margin-bottom:2px;'>${price}</div>
                        </div>
                    </div>
                    <div style='font-size:15px;color:#444;margin-bottom:18px;text-align:left;'>You need to log in to add products to your favorites list.</div>
                    <div style='display:flex;gap:12px;'>
                        <button id='loginFavBtn' style='flex:1;background:#6e5f51;color:#fff;font-weight:700;font-size:16px;padding:10px 0;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(44,62,80,0.08);cursor:pointer;'>Log In</button>
                        <button id='cancelFavBtn' style='flex:1;background:#f3f3f3;color:#444;font-weight:600;font-size:16px;padding:10px 0;border:none;border-radius:8px;cursor:pointer;'>Later</button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: false,
            background: 'transparent',
            customClass: { popup: 'swal2-login-fav-popup' },
            width: 500,
            didOpen: () => {
                document.getElementById('loginFavBtn').onclick = function () {
                    window.location.href = '/pages/login.php?pending_favorite=' + productId;
                };
                document.getElementById('cancelFavBtn').onclick = function () {
                    Swal.close();
                };
            }
        });
        return;
    }

    const btn = event.currentTarget;
    const icon = btn.querySelector('i');

    fetch('type_products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `toggle_favorite=1&product_id=${productId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
                // Gọi AJAX lấy lại số lượng yêu thích và cập nhật badge
                fetch('/public/get_favorite_count.php')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) updateFavoriteBadge(data.count);
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

// Đảm bảo đoạn này chạy sau khi DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Lắng nghe sự kiện xóa sản phẩm yêu thích trong mini-favorite popup
    document.addEventListener('click', function(e) {
        if (e.target.closest('.favorite-mini-remove')) {
            const btn = e.target.closest('.favorite-mini-remove');
            const pid = btn.getAttribute('data-pid');
            // Gọi API xóa
            fetch('/public/remove_from_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ product_id: pid })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Xóa khỏi popup
                    const item = btn.closest('.d-flex');
                    if (item) item.remove();
                    // Cập nhật badge header
                    fetch('/public/get_favorite_count.php')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && typeof updateFavoriteBadge === 'function') updateFavoriteBadge(data.count);
                        });
                    // Cập nhật icon tim trên danh sách sản phẩm (nếu có)
                    document.querySelectorAll('.favorite-btn').forEach(function(favBtn) {
                        if (favBtn.onclick && favBtn.onclick.toString().includes(pid)) {
                            const icon = favBtn.querySelector('i');
                            if (icon && icon.classList.contains('fas')) {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                            }
                        }
                        // Nếu dùng data-product-id:
                        if (favBtn.getAttribute('onclick') && favBtn.getAttribute('onclick').includes(pid)) {
                            const icon = favBtn.querySelector('i');
                            if (icon && icon.classList.contains('fas')) {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                            }
                        }
                    });
                }
            });
        }
    });

    // Đảm bảo khi chọn category sẽ lọc ngay lập tức
    // (thay vì onchange="filterProducts()" trên select, dùng event listener để tránh reload trang)
    const form = document.getElementById('filterForm');
    if (form) {
        // Bỏ onchange="filterProducts()" trên select category và color trong HTML (chỉ dùng JS)
        form.querySelectorAll('select[name="category"]').forEach(function(select) {
            select.onchange = null;
            select.addEventListener('change', function() {
                // Reset các filter khác về mặc định khi đổi category
                form.querySelector('select[name="sort"]').selectedIndex = 0;
                form.querySelector('select[name="price"]').selectedIndex = 0;
                // Lấy lại màu động theo category
                const colorSelect = form.querySelector('select[name="color"]');
                colorSelect.selectedIndex = 0;
                colorSelect.innerHTML = '<option value="all">All Color</option>';
                const catVal = select.value;
                fetch(`/public/get_colors_product.php?category=${catVal}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && Array.isArray(data.colors)) {
                            data.colors.forEach(function(color) {
                                const opt = document.createElement('option');
                                opt.value = color;
                                opt.textContent = color;
                                colorSelect.appendChild(opt);
                            });
                        }
                        filterProducts(1);
                    });
            });
        });
        form.querySelectorAll('select[name="sort"], select[name="price"]').forEach(function(select) {
            select.onchange = null;
            select.addEventListener('change', function() {
                filterProducts(1);
            });
        });
        // Khi đổi màu cũng lọc lại
        form.querySelectorAll('select[name="color"]').forEach(function(select) {
            select.onchange = null;
            select.addEventListener('change', function() {
                filterProducts(1);
            });
        });
    }

    // Đảm bảo khi bấm F5 hoặc back/forward trên trình duyệt sẽ load lại đúng filter
    window.addEventListener('popstate', function() {
        filterProducts();
    });
});