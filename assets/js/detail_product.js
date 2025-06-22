// Remove item via AJAX (toast nhỏ gọn)

function removeCartItem(pid, image_url, name, price) {
    fetch('/public/remove_from_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `product_id=${pid}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showCartToast('remove', {
                    image_url: image_url,
                    name: name,
                    price: price
                });
                // Cập nhật lại giao diện giỏ hàng nếu cần
                // ...
            } else {
                Swal.fire('Lỗi', data.message || 'Không thể xóa sản phẩm', 'error');
            }
        });
}
// --- Tabs ---
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.getElementById('tab-' + this.dataset.tab).style.display = '';
    });
});
// --- Share ---
document.querySelector('.share-btn').addEventListener('click', function () {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        Swal.fire('Đã sao chép liên kết!');
    }
});
// --- Size guide toggle ---
document.getElementById('sizeGuideToggle').addEventListener('click', function (e) {
    e.preventDefault();
    const box = document.getElementById('sizeGuideBox');
    box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
});
// --- Fundiin Learn More Popup (EN, rounded corners) ---
document.getElementById('fundiinLearnMore').addEventListener('click', function (e) {
    e.preventDefault();
    Swal.fire({
        html: `
       <div class="fundiin-title">Fundiin Payment Discounts</div>

<!-- Code 1 -->
<div class="discount-card">
  <div class="discount-icon">
    <svg width="34" height="34" viewBox="0 0 32 32" fill="none">
      <rect width="32" height="32" rx="8" fill="none"/>
      <path d="M10 12h12M10 16h8M10 20h4" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
    </svg>
  </div>
  <div class="discount-content">
    <div class="discount-header">
      <span>Use code</span>
      <span class="discount-code">XINCHAO</span>
    </div>
    <div class="discount-description"><strong>15% OFF</strong> up to 30K</div>
  </div>
</div>
<div class="discount-note">For first-time Fundiin users</div>

<!-- Code 2 -->
<div class="discount-card">
  <div class="discount-icon">
    <svg width="34" height="34" viewBox="0 0 32 32" fill="none">
      <rect width="32" height="32" rx="8" fill="none"/>
      <path d="M10 12h12M10 16h8M10 20h4" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
    </svg>
  </div>
  <div class="discount-content">
    <div class="discount-header">
      <span>Use code</span>
      <span class="discount-code">FUNDAY</span>
    </div>
    <div class="discount-description"><strong>10% OFF</strong> up to 15K</div>
  </div>
</div>
<div class="discount-note">For returning Fundiin users</div>

        `,
        showConfirmButton: false,
        showCloseButton: true,
        width: 540,
        background: '#fff',
        customClass: {
            popup: 'fundiin-popup-custom'
        }
    });
});
