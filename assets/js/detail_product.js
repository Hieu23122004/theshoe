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
  // Lấy danh sách mã từ window.fundiinCodes
  const codes = (window.fundiinCodes || []).filter(c => c.code && c.valid_from && c.valid_until);
  let html = `<div class="fundiin-title">Discount codes when paying via Fundiin</div>`;
  if (codes.length === 0) {
    html += '<div class="fundiin-container"><div class="voucher-note">No valid Fundiin discount codes at this time.</div></div>';
  } else {
    // Bọc các mã vào 1 khung cuộn nếu > 2
    if (codes.length > 2) {
      html += '<div style="max-height:340px;overflow-y:auto;padding-right:6px;scrollbar-width:thin;">';
    }
    codes.forEach((c, idx) => {
      html += `
      <div class="fundiin-container" style="margin-top:${idx > 0 ? '8px' : '0'};">
        <div class="voucher-card">
          <div class="voucher-left">
            <svg class="voucher-icon" viewBox="0 0 32 32">
              <path d="M10 12h12M10 16h8M10 20h4" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="voucher-right" style="height: 65px;">
            <div class="voucher-line">
              Use code <span class="code fundiin-copy-code" data-code="${c.code}" style="cursor:pointer;">${c.code}</span>
            </div>
            <div class="voucher-desc">
              Get <strong>${c.discount_type === 'percent' ? c.discount_value + '%' : Number(c.discount_value).toLocaleString('vi-VN') + '₫'}</strong> off orders over <strong>${Number(c.min_order_amount).toLocaleString('vi-VN')}₫</strong>
            </div>
          </div>
        </div>
        <div class="voucher-note">Valid: ${c.valid_from.substr(0,10)} - ${c.valid_until.substr(0,10)}</div>
      </div>
      `;
    });
    if (codes.length > 2) {
      html += '</div>';
    }
  }
  Swal.fire({
    html,
    showConfirmButton: false,
    showCloseButton: true,
    width: 540,
    background: '#fff',
    customClass: {
      popup: 'fundiin-popup-custom',
      closeButton: 'swal2-close-no-outline'
    }
  });

  // Thêm sự kiện copy cho các mã code
  setTimeout(function() {
    const style = document.createElement('style');
    style.innerHTML = `
      .swal2-toast-custom-small {
        height: 70px !important;
        min-height: 50px !important;
        display: flex !important;
        align-items: center !important;
      }
      .swal2-toast-custom-small .swal2-icon {
        width: 25px !important;
        height: 25px !important;
        margin: 0 10px 0 0 !important;
        flex-shrink: 0 !important;
      }
      .swal2-toast-custom-small .swal2-title {
        font-size: 17px !important;
        line-height: 1 !important;
        margin: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
      .swal2-toast-custom-small .swal2-content {
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
      }
      .swal2-close-no-outline {
        outline: none !important;
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
      }
      .swal2-close-no-outline:focus {
        outline: none !important;
        box-shadow: none !important;
      }
    `;
    document.head.appendChild(style);
    document.querySelectorAll('.fundiin-copy-code').forEach(function(el) {
      el.addEventListener('click', function() {
        const code = this.getAttribute('data-code');
        navigator.clipboard.writeText(code);
        Swal.fire({
          toast: true,
                position: 'top-end',
                icon: 'success',
                title: `<b>Code: ${code}</b>`,
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                width: 280,
                padding: '8px 12px',
                customClass: { 
                  popup: 'swal2-toast-custom-small'
                }
        });
      });
    });
  }, 100);
});

// --- Hiển thị tự động popup Fundiin khi vào trang ---
window.addEventListener('DOMContentLoaded', function() {
  const fundiinBtn = document.getElementById('fundiinLearnMore');
  if (fundiinBtn) {
    fundiinBtn.click();
  }
});


