let discountValue = 0;
let discountType = null;
let shippingDiscount = 0;
const DEFAULT_SHIPPING = 35000;
let checkoutStep = 'shipping';

function setCheckoutStep(step) {
    checkoutStep = step;
    const navShipping = document.getElementById('navShipping');
    const navPayment = document.getElementById('navPayment');
    if (step === 'shipping') {
        navShipping.classList.add('active');
        navShipping.classList.remove('inactive');
        navPayment.classList.remove('active');
        navPayment.classList.add('inactive');
        navShipping.style.pointerEvents = 'none';
        navPayment.style.pointerEvents = 'none';
    } else if (step === 'payment') {
        navShipping.classList.remove('active');
        navShipping.classList.add('inactive');
        navPayment.classList.add('active');
        navPayment.classList.remove('inactive');
        navShipping.style.pointerEvents = '';
        navPayment.style.pointerEvents = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Thêm data-quantity cho các item không có
    document.querySelectorAll('.checkout-cart-item').forEach(item => {
        if (!item.getAttribute('data-quantity')) {
            // Tìm badge để lấy quantity
            const badge = item.querySelector('.badge, .position-absolute span');
            let quantity = 1;
            if (badge && badge.textContent) {
                quantity = parseInt(badge.textContent) || 1;
            }
            item.setAttribute('data-quantity', quantity);
            console.log('Added data-quantity:', quantity, 'to item:', item.getAttribute('data-pid'));
        }
    });
    
    setCheckoutStep('shipping');
    // Khi submit form chuyển sang bước payment
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Nếu chưa đăng nhập thì chuyển sang login trước khi cho qua bước payment
        if (typeof window.isLoggedIn === 'undefined' ? false : !window.isLoggedIn) {
            saveCheckoutFormToLocal(true); // Lưu thông tin và flag
            window.location.href = "/pages/login.php?redirect=/pages/checkout.php?step=payment";
            return;
        }
        setCheckoutStep('payment');
        document.getElementById('checkoutForm').style.display = 'none';
        document.getElementById('paymentMethodSection').style.display = '';
        document.getElementById('paymentMethodSection').scrollIntoView({behavior: 'smooth'});
    });
    // Cho phép click navShipping để quay lại shipping khi đang ở bước payment
    document.getElementById('navShipping').addEventListener('click', function() {
        if (checkoutStep === 'payment') {
            setCheckoutStep('shipping');
            document.getElementById('checkoutForm').style.display = '';
            document.getElementById('paymentMethodSection').style.display = 'none';
        }
    });
    // Đảm bảo sự kiện luôn được gắn
    const applyBtn = document.getElementById('applyDiscountBtn');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const code = document.getElementById('discountCodeInput').value.trim();
            const msgEl = document.getElementById('discountCodeMsg');
            msgEl.textContent = '';
            if (!code) {
                msgEl.textContent = 'Please enter a discount code.';
                msgEl.style.color = '#e74c3c';
                discountValue = 0;
                discountType = null;
                shippingDiscount = 0;
                updateCheckoutTotals();
                return;
            }
            fetch('/public/check_discount_code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ code })
            })
            .then(r => r.json())
            .then(data => {
                if (data.valid) {
                    discountValue = parseFloat(data.discount_value);
                    discountType = data.discount_type;
                    shippingDiscount = parseFloat(data.shipping_discount || 0);
                    msgEl.textContent = data.message || 'Áp dụng mã thành công!';
                    msgEl.style.color = '#27ae60';
                } else {
                    discountValue = 0;
                    discountType = null;
                    shippingDiscount = 0;
                    msgEl.textContent = data.message || 'Invalid or expired discount code.';
                    msgEl.style.color = '#e74c3c';
                }
                updateCheckoutTotals();
            })
            .catch(() => {
                msgEl.textContent = 'Error checking discount code.';
                msgEl.style.color = '#e74c3c';
                discountValue = 0;
                discountType = null;
                shippingDiscount = 0;
                updateCheckoutTotals();
            });
        });
    }
});
// Khi trang vừa load, tính tổng cộng luôn có phí ship mặc định
document.addEventListener('DOMContentLoaded', function() {
    bindRemoveCheckoutEvents();
    updateCheckoutTotals();
});

function bindRemoveCheckoutEvents() {
    document.querySelectorAll('.btn-remove-checkout-item').forEach(btn => {
        btn.onclick = function() {
            const pid = this.getAttribute('data-pid');
            const color = this.getAttribute('data-color');
            const size = this.getAttribute('data-size');
            // Xóa khỏi checkout_selected trong localStorage
            let selected = [];
            try {
                selected = JSON.parse(localStorage.getItem('checkout_selected')) || [];
            } catch (e) {}
            const idx = selected.findIndex(x => x.pid == pid && x.color == color && x.size == size);
            if (idx !== -1) {
                selected.splice(idx, 1);
                localStorage.setItem('checkout_selected', JSON.stringify(selected));
            }
            // Xóa sản phẩm khỏi DOM
            const item = document.querySelector('.checkout-cart-item[data-pid="' + pid + '"][data-color="' + color + '"][data-size="' + size + '"]');
            if (item) item.remove();
            updateCheckoutTotals();
            // Gắn lại sự kiện xóa cho các nút còn lại (nếu DOM thay đổi)
            bindRemoveCheckoutEvents();
            // Cập nhật badge nếu có hàm updateCartBadge (số lượng sản phẩm thanh toán)
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(selected.length);
            }
        };
    });
}

// --- TÍNH TỔNG TIỀN VÀ ÁP DỤNG DISCOUNT CODE ---
function getSelectedCheckoutItems() {
    const items = [];
    document.querySelectorAll('.checkout-cart-item').forEach(function(item) {
        const pid = item.getAttribute('data-pid');
        const color = item.getAttribute('data-color');
        const size = item.getAttribute('data-size');
        // Lấy số lượng nếu có badge, mặc định 1
        let qty = 1;
        const badge = item.querySelector('span.position-absolute');
        if (badge) {
            const n = parseInt(badge.textContent.trim());
            if (!isNaN(n) && n > 0) qty = n;
        }
        items.push({
            product_id: pid ? parseInt(pid) : 0,
            color: color || '',
            size: size || '',
            quantity: qty
        });
    });
    return items;
}

function updateCheckoutTotals() {
    let subtotal = 0;
    let shipping = 35000; // mặc định
    let discount = 0;
    let discountType = null;
    let discountValue = 0;
    
    // Đếm số sản phẩm đang hiển thị
    const itemCount = document.querySelectorAll('.checkout-cart-item').length;
    
    // Cập nhật label tổng tiền dựa trên số lượng sản phẩm
    const totalLabelEl = document.getElementById('totalLabel');
    if (totalLabelEl) {
        if (itemCount === 0) {
            totalLabelEl.textContent = 'Total Amount';
        } else if (itemCount === 1) {
            totalLabelEl.textContent = 'Total Amount';
        } else {
            totalLabelEl.textContent = `Total (${itemCount} items)`;
        }
    }
    
    // Lấy danh sách sản phẩm đang hiển thị
    document.querySelectorAll('.checkout-cart-item').forEach(function(item) {
        // Lấy giá đúng từ text node đầu tiên (loại bỏ badge)
        let priceDivs = item.querySelectorAll('div[style*="font-weight:600"]');
        let price = 0;
        if (priceDivs.length) {
            let priceText = priceDivs[0].textContent.replace(/[^\d]/g, '');
            price = parseInt(priceText) || 0;
        }
        subtotal += price;
    });
    // Lấy mã giảm giá
    const code = document.getElementById('discountCodeInput')?.value.trim();
    const selectedItems = getSelectedCheckoutItems();
    if (code) {
        fetch('/public/check_discount_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'code=' + encodeURIComponent(code) + '&selected_items=' + encodeURIComponent(JSON.stringify(selectedItems))
        })
        .then(r => r.json())
        .then(data => {
            const msgEl = document.getElementById('discountCodeMsg');
            if (data.valid) {
                discountType = data.discount_type;
                discountValue = parseFloat(data.discount_value);
                if (discountType === 'percent') {
                    discount = Math.round(subtotal * discountValue / 100);
                    document.getElementById('discountLabel').textContent = `Discount (${discountValue}%)`;
                    document.getElementById('discountValue').textContent = '-' + discount.toLocaleString('vi-VN') + '₫';
                } else if (discountType === 'fixed') {
                    discount = discountValue;
                    document.getElementById('discountLabel').textContent = `Discount (${code})`;
                    document.getElementById('discountValue').textContent = '-' + discount.toLocaleString('vi-VN') + '₫';
                }
                // Không cho phép discount lớn hơn subtotal
                if (discount > subtotal) discount = subtotal;
                document.getElementById('discountRow').style.display = '';
                msgEl.textContent = data.message;
                msgEl.style.color = '#27ae60'; // xanh lá
            } else {
                discount = 0;
                document.getElementById('discountRow').style.display = 'none';
                msgEl.textContent = data.message;
                msgEl.style.color = '#e74c3c'; // đỏ
            }
            document.getElementById('checkoutSubtotal').textContent = subtotal.toLocaleString('vi-VN') + '₫';
            document.getElementById('checkoutShipping').textContent = shipping.toLocaleString('vi-VN') + '₫';
            let total = subtotal - discount + shipping;
            if (total < 0) total = 0;
            document.getElementById('checkoutTotal').textContent = total.toLocaleString('vi-VN');
        });
    } else {
        document.getElementById('discountRow').style.display = 'none';
        document.getElementById('discountCodeMsg').textContent = '';
        document.getElementById('checkoutSubtotal').textContent = subtotal.toLocaleString('vi-VN') + '₫';
        document.getElementById('checkoutShipping').textContent = shipping.toLocaleString('vi-VN') + '₫';
        let total = subtotal + shipping;
        if (total < 0) total = 0;
        document.getElementById('checkoutTotal').textContent = total.toLocaleString('vi-VN');
    }
}
// Gắn sự kiện cho input discount code
setTimeout(function() {
    const input = document.getElementById('discountCodeInput');
    if (input) {
        input.addEventListener('change', updateCheckoutTotals);
        input.addEventListener('keyup', function(e) { if (e.key === 'Enter') updateCheckoutTotals(); });
    }
    updateCheckoutTotals();
}, 500);

// Đảm bảo sự kiện luôn được gắn
const applyBtn = document.getElementById('applyDiscountBtn');
if (applyBtn) {
    applyBtn.addEventListener('click', function() {
        const code = document.getElementById('discountCodeInput').value.trim();
        const msgEl = document.getElementById('discountCodeMsg');
        msgEl.textContent = '';
        if (!code) {
            msgEl.textContent = 'Please enter a discount code.';
            msgEl.style.color = '#e74c3c';
            discountValue = 0; discountType = null; shippingDiscount = 0;
            updateCheckoutTotals();
            return;
        }
        const selectedItems = getSelectedCheckoutItems();
        fetch('/public/check_discount_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'code=' + encodeURIComponent(code) + '&selected_items=' + encodeURIComponent(JSON.stringify(selectedItems))
        })
        .then(r => r.json())
        .then(data => {
            if (data.valid) {
                discountValue = parseFloat(data.discount_value);
                discountType = data.discount_type;
                shippingDiscount = parseFloat(data.shipping_discount || 0);
                msgEl.textContent = data.message || 'Áp dụng mã thành công!';
                msgEl.style.color = '#27ae60'; // xanh lá
            } else {
                discountValue = 0; discountType = null; shippingDiscount = 0;
                msgEl.textContent = data.message || 'Invalid or expired discount code.';
                msgEl.style.color = '#e74c3c'; // đỏ
            }
            updateCheckoutTotals();
        })
        .catch(() => {
            msgEl.textContent = 'Error checking discount code.';
            msgEl.style.color = '#e74c3c';
            discountValue = 0; discountType = null; shippingDiscount = 0;
            updateCheckoutTotals();
        });
    });
}
updateCheckoutTotals();

// Đảm bảo gọi updateCheckoutTotals() sau khi render lại sản phẩm (AJAX)
document.addEventListener('DOMContentLoaded', function() {
    // Lấy danh sách sản phẩm đã chọn từ localStorage
    let selected = JSON.parse(localStorage.getItem('checkout_selected') || '[]');
    if (selected.length > 0) {
        const checkoutCartListInner = document.getElementById('checkoutCartListInner');
        fetch('/pages/checkout.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({selected})
        })
        .then(r => r.text())
        .then(html => {
            if (checkoutCartListInner) {
                checkoutCartListInner.innerHTML = html;
                // Gắn lại sự kiện xóa cho các nút còn lại (nếu DOM thay đổi)
                checkoutCartListInner.querySelectorAll('.btn-remove-checkout-item').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        const pid = this.getAttribute('data-pid');
                        const color = this.getAttribute('data-color');
                        const size = this.getAttribute('data-size');
                        // Xóa khỏi checkout_selected trong localStorage
                        let selected = [];
                        try {
                            selected = JSON.parse(localStorage.getItem('checkout_selected')) || [];
                        } catch (e) {}
                        const idx = selected.findIndex(x => x.pid == pid && x.color == color && x.size == size);
                        if (idx !== -1) {
                            selected.splice(idx, 1);
                            localStorage.setItem('checkout_selected', JSON.stringify(selected));
                        }
                        // Xóa sản phẩm khỏi DOM
                        const item = document.querySelector('.checkout-cart-item[data-pid="' + pid + '"][data-color="' + color + '"][data-size="' + size + '"]');
                        if (item) item.remove();
                        updateCheckoutTotals();
                        // Gắn lại sự kiện xóa cho các nút còn lại (nếu DOM thay đổi)
                        bindRemoveCheckoutEvents();
                        if (typeof updateCartBadge === 'function') {
                            updateCartBadge(selected.length);
                        }
                    });
                });
                // Cập nhật lại tổng tiền khi danh sách thay đổi
                if (typeof updateCheckoutTotals === 'function') updateCheckoutTotals();
            }
        });
    }
    // ...existing code...
});

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Nếu chưa đăng nhập thì chuyển sang login trước khi cho qua bước payment
    if (typeof window.isLoggedIn === 'undefined' ? false : !window.isLoggedIn) {
        saveCheckoutFormToLocal(true); // Lưu thông tin và flag
        window.location.href = "/pages/login.php?redirect=/pages/checkout.php?step=payment";
        return;
    }
    setCheckoutStep('payment');
    document.getElementById('checkoutForm').style.display = 'none';
    document.getElementById('paymentMethodSection').style.display = '';
    document.getElementById('paymentMethodSection').scrollIntoView({behavior: 'smooth'});
});

document.getElementById('payBank').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('bankTransferOptions').style.display = '';
        // Nếu đã chọn bank trước đó thì cập nhật lại input ẩn
        const bankSelect = document.getElementById('bankSelect');
        const bankNameInput = document.getElementById('bankNameInput');
        if (bankSelect && bankNameInput) {
            let bankName = '';
            switch (bankSelect.value) {
                case 'vcb': bankName = 'Vietcombank'; break;
                case 'tcb': bankName = 'Techcombank'; break;
                case 'mb': bankName = 'MB Bank'; break;
                case 'bidv': bankName = 'BIDV'; break;
                default: bankName = '';
            }
            bankNameInput.value = bankName;
        }
    }
});

document.getElementById('payCOD').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('bankTransferOptions').style.display = 'none';
        document.getElementById('qrSection').style.display = 'none';
        // Xóa giá trị input ẩn bank_name khi chọn COD
        const bankNameInput = document.getElementById('bankNameInput');
        if (bankNameInput) bankNameInput.value = '';
    }
});

document.getElementById('bankSelect').addEventListener('change', function() {
    const bank = this.value;
    const qrSection = document.getElementById('qrSection');
    const qrImage = document.getElementById('qrImage');
    const qrBankName = document.getElementById('qrBankName');
    const qrAmount = document.getElementById('qrAmount');
    let bankName = '';
    let qrSrc = '';
    // Lấy tổng tiền từ DOM
    const total = document.getElementById('checkoutTotal').textContent.replace(/[^\d]/g, '');
    const amount = Number(total);
    // Gọi API lấy mã QR động
    if (bank) {
        // Tên ngân hàng hiển thị
        switch(bank) {
            case 'vcb': bankName = 'Vietcombank'; break;
            case 'tcb': bankName = 'Techcombank'; break;
            case 'mb': bankName = 'MB Bank'; break;
            case 'bidv': bankName = 'BIDV'; break;
            default: bankName = ''; break;
        }
        // Gọi API lấy link QR động
        fetch('/public/get_qr_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'bank=' + encodeURIComponent(bank) + '&amount=' + encodeURIComponent(amount)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.qr_url) {
                qrImage.src = data.qr_url;
                qrSection.style.display = '';
                qrBankName.textContent = bankName;
                
            } else {
                qrSection.style.display = 'none';
            }
        })
        .catch(() => {
            qrSection.style.display = 'none';
        });
    } else {
        qrSection.style.display = 'none';
    }
});

// --- Tự động load tỉnh, quận, xã ---
const provinceSelect = document.getElementById('provinceSelect');
const districtSelect = document.getElementById('districtSelect');
const wardSelect = document.getElementById('wardSelect');

// Load tỉnh
fetch('https://provinces.open-api.vn/api/p/')
    .then(r => r.json())
    .then(data => {
        data.forEach(province => {
            const opt = document.createElement('option');
            opt.value = province.code;
            opt.textContent = province.name;
            provinceSelect.appendChild(opt);
        });
    });

provinceSelect.onchange = function() {
    const provinceCode = this.value;
    districtSelect.innerHTML = '<option value="">Quận / huyện</option>';
    wardSelect.innerHTML = '<option value="">Phường / xã</option>';
    if (!provinceCode) {
        districtSelect.disabled = true;
        wardSelect.disabled = true;
        return;
    }
    districtSelect.disabled = false;
    wardSelect.disabled = true;
    fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=2`)
        .then(r => r.json())
        .then(data => {
            if (!data.districts || !Array.isArray(data.districts)) {
                console.error('Không có dữ liệu quận/huyện');
                districtSelect.disabled = true;
                return;
            }
            data.districts.forEach(district => {
                const opt = document.createElement('option');
                opt.value = district.code;
                opt.textContent = district.name;
                districtSelect.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Lỗi load quận/huyện:', err);
            districtSelect.disabled = true;
        });
};

districtSelect.onchange = function() {
    const districtCode = this.value;
    wardSelect.innerHTML = '<option value="">Phường / xã</option>';
    if (!districtCode) {
        wardSelect.disabled = true;
        return;
    }
    wardSelect.disabled = false;
    fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`)
        .then(r => r.json())
        .then(data => {
            if (!data.wards || !Array.isArray(data.wards)) {
                console.error('Không có dữ liệu phường/xã');
                wardSelect.disabled = true;
                return;
            }
            data.wards.forEach(ward => {
                const opt = document.createElement('option');
                opt.value = ward.code;
                opt.textContent = ward.name;
                wardSelect.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Lỗi load phường/xã:', err);
            wardSelect.disabled = true;
        });
};

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('checkout.php')) {
        if (!provinceSelect.value) {
            districtSelect.disabled = true;
            wardSelect.disabled = true;
        } else if (!districtSelect.value) {
            districtSelect.disabled = false;
            wardSelect.disabled = true;
        } else if (!wardSelect.value) {
            districtSelect.disabled = false;
            wardSelect.disabled = false;
        }
    }
});

document.getElementById('confirmOrderBtn').addEventListener('click', function(e) {
    // Kiểm tra đăng nhập bằng biến JS do PHP sinh ra ở file checkout.php
    if (typeof window.isLoggedIn === 'undefined' ? false : !window.isLoggedIn) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: '<span style="color:#fff;font-weight:500;">Please login to order!</span>',
            background: '#222',
            color: '#fff',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            customClass: { popup: 'swal2-toast-custom' }
        });
        return;
    }
    // Kiểm tra điều kiện Bank Wire Transfer
    const payBank = document.getElementById('payBank');
    const bankSelect = document.getElementById('bankSelect');
    if (payBank && payBank.checked) {
        if (!bankSelect.value) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: '<span style="color:#fff;font-weight:500;">Please select a bank to pay!</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
            return;
        }
        if (typeof qrScanned !== 'undefined' && !qrScanned) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: '<span style="color:#fff;font-weight:500;">Please scan the QR code to pay before ordering.!</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
            return;
        }
    }
    // Lấy dữ liệu form
    const form = document.getElementById('checkoutForm');
    const formData = new FormData(form);
    // Lấy shipping info
    const fullname = formData.get('fullname');
    const email = formData.get('email');
    const phone = formData.get('phone');
    const address = formData.get('address');
    const province = formData.get('province');
    const district = formData.get('district');
    const ward = formData.get('ward');
    // Lấy payment method
    const payment_method = document.querySelector('input[name="payment_method"]:checked').value;
    // Lấy shipping method (nếu có)
    const shipping_method = 'Standard Delivery';
    // Lấy tổng tiền
    const total_amount = document.getElementById('checkoutTotal').textContent.replace(/[^\d]/g, '');
    // Lấy giỏ hàng
    const cartItems = [];
    document.querySelectorAll('.checkout-cart-item').forEach(item => {
        let quantity = 1; // mặc định
        
        // Ưu tiên lấy từ data-quantity attribute
        const dataQty = item.getAttribute('data-quantity');
        if (dataQty && dataQty !== '1') {
            quantity = parseInt(dataQty) || 1;
            console.log('Got quantity from data-quantity:', quantity);
        } else {
            // Fallback: Lấy từ badge (chỉ hiện khi > 1)
            const badge = item.querySelector('.badge, .position-absolute span');
            if (badge && badge.textContent) {
                const badgeQty = parseInt(badge.textContent);
                if (badgeQty > 1) {
                    quantity = badgeQty;
                    console.log('Got quantity from badge:', quantity);
                }
            }
        }
        
        console.log('Final quantity for product', item.getAttribute('data-pid'), ':', quantity);
        
        cartItems.push({
            product_id: item.getAttribute('data-pid'),
            color: item.getAttribute('data-color'),
            size: item.getAttribute('data-size'),
            quantity: quantity
        });
    });
    // Lấy tên ngân hàng (nếu có)
    const bank_name = formData.get('bank_name') || '';
    if (cartItems.length === 0) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: '<span style="color:#fff;font-weight:500;">Không có sản phẩm nào để đặt hàng!</span>',
            background: '#222',
            color: '#fff',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            customClass: { popup: 'swal2-toast-custom' }
        });
        return;
    }
    // Lấy mã giảm giá đã áp dụng (chỉ gửi nếu có discount value > 0)
    const appliedDiscountCode = (discountValue > 0 && document.getElementById('discountCodeInput')?.value.trim()) || '';
    
    // Gửi ajax tạo đơn hàng
    fetch('/public/add_order.php', {
        method: 'POST',
        body: new URLSearchParams({
            fullname, email, phone, address, province, district, ward,
            shipping_method, payment_method,
            total_amount,
            order_details: JSON.stringify(cartItems),
            bank_name, // <-- gửi thêm trường này
            discount_code: appliedDiscountCode // <-- gửi mã giảm giá
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Xóa sản phẩm khỏi DOM ngay khi đặt hàng thành công
            document.querySelectorAll('.checkout-cart-item').forEach(item => item.remove());
            localStorage.removeItem('checkoutFormData');
            localStorage.removeItem('checkout_selected'); // <-- Xóa luôn danh sách thanh toán khi đặt hàng thành công
            // Cập nhật badge giỏ hàng nếu có hàm updateCartBadge
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(0);
            } else {
                // Nếu không có, thử cập nhật thủ công
                const badge = document.querySelector('.cart-badge, #cartBadge');
                if (badge) badge.textContent = '0';
            }
            // Xóa cart ở backend (nếu cần, gọi API xóa cart)
            fetch('/public/remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ clear_all: '1' })
            });
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<span style="color:#fff;font-weight:500;">Order Placed Successfully!</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
            if (data.order_id) {
                setTimeout(() => { window.location.href = '/pages/detail_orders.php?order_id=' + data.order_id; }, 2000);
            } else {
                setTimeout(() => { window.location.href = '/pages/detail_orders.php'; }, 2000);
            }
        } else {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '<span style="color:#fff;font-weight:500;">' + (data.message || 'Lỗi khi đặt hàng!') + '</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
        }
    })
    .catch(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '<span style="color:#fff;font-weight:500;">Lỗi kết nối máy chủ!</span>',
            background: '#222',
            color: '#fff',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            customClass: { popup: 'swal2-toast-custom' }
        });
    });
});

// Lưu thông tin form checkout vào localStorage khi bấm Login/Register
function saveCheckoutFormToLocal(goToPayment) {
    const form = document.getElementById('checkoutForm');
    if (!form) return;
    const data = {
        fullname: form.fullname?.value || '',
        email: form.email?.value || '',
        phone: form.phone?.value || '',
        address: form.address?.value || '',
        province: form.province?.value || '',
        district: form.district?.value || '',
        ward: form.ward?.value || ''
    };
    localStorage.setItem('checkoutFormData', JSON.stringify(data));
    if (goToPayment) localStorage.setItem('checkoutGoToPayment', '1');
}

// Gắn sự kiện cho link Login/Register
window.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href*="login.php"], a[href*="register.php"]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            saveCheckoutFormToLocal(true); // set flag để quay lại vào payment
        });
    });

    // Khi trang checkout load, nếu có dữ liệu localStorage thì điền vào form
    const saved = localStorage.getItem('checkoutFormData');
    if (saved) {
        try {
            const data = JSON.parse(saved);
            const form = document.getElementById('checkoutForm');
            if (form) {
                if (data.fullname) form.fullname.value = data.fullname;
                if (data.email) form.email.value = data.email;
                if (data.phone) form.phone.value = data.phone;
                if (data.address) form.address.value = data.address;
                // Đảm bảo set province rồi mới set district, rồi ward
                if (data.province) {
                    form.province.value = data.province;
                    form.province.dispatchEvent(new Event('change'));
                    setTimeout(function() {
                        if (data.district) {
                            form.district.value = data.district;
                            form.district.dispatchEvent(new Event('change'));
                            setTimeout(function() {
                                if (data.ward) form.ward.value = data.ward;
                                // Lưu lại province/district/ward vào localStorage khi đã set xong
                                localStorage.setItem('checkoutFormData', JSON.stringify({
                                    ...data,
                                    province: form.province.value,
                                    district: form.district.value,
                                    ward: form.ward.value
                                }));
                                // Đánh dấu đã autofill xong để không bị ghi đè bởi user_info
                                window.__checkoutAutofilled = true;
                            }, 700);
                        }
                    }, 700);
                }
            }
        } catch(e) {}
    }

    // Nếu có user_info (từ PHP) và chưa autofill từ localStorage thì chỉ điền các trường còn thiếu
    if (typeof window.user_info === 'object' && !window.__checkoutAutofilled) {
        const form = document.getElementById('checkoutForm');
        if (form) {
            if (window.user_info.fullname && !form.fullname.value) form.fullname.value = window.user_info.fullname;
            if (window.user_info.email && !form.email.value) form.email.value = window.user_info.email;
            if (window.user_info.phone && !form.phone.value) form.phone.value = window.user_info.phone;
            if (window.user_info.address && !form.address.value) form.address.value = window.user_info.address;
        }
    }

    // Khi quay lại Shipping, nếu có localStorage thì điền lại luôn
    document.getElementById('navShipping').addEventListener('click', function() {
        const saved = localStorage.getItem('checkoutFormData');
        if (saved) {
            try {
                const data = JSON.parse(saved);
                const form = document.getElementById('checkoutForm');
                if (form) {
                    if (data.fullname) form.fullname.value = data.fullname;
                    if (data.email) form.email.value = data.email;
                    if (data.phone) form.phone.value = data.phone;
                    if (data.address) form.address.value = data.address;
                    if (data.province) {
                        form.province.value = data.province;
                        form.province.dispatchEvent(new Event('change'));
                        setTimeout(function() {
                            if (data.district) {
                                form.district.value = data.district;
                                form.district.dispatchEvent(new Event('change'));
                                setTimeout(function() {
                                    if (data.ward) form.ward.value = data.ward;
                                }, 700);
                            }
                        }, 700);
                    }
                }
            } catch(e) {}
        }
    });
});

// Kiểm tra sản phẩm được chọn cho thanh toán
window.addEventListener('DOMContentLoaded', function() {
    let selected = JSON.parse(localStorage.getItem('checkout_selected') || '[]');
    const mainContent = document.querySelector('.container-fluid');
    let emptyDiv = document.getElementById('checkout-empty-message');
    if (!emptyDiv) {
        emptyDiv = document.createElement('div');
        emptyDiv.id = 'checkout-empty-message';
        emptyDiv.style = 'display:none;text-align:center;margin:120px auto;font-size:1.2rem;color:#888;';
        document.body.appendChild(emptyDiv);
    }
    if (selected.length === 0) {
        if (mainContent) mainContent.style.display = 'none';
        emptyDiv.style.display = '';
        emptyDiv.innerHTML = '';
        return;
    } else {
        if (mainContent) mainContent.style.display = '';
        emptyDiv.style.display = 'none';
    }
});

// --- Đồng bộ checkbox sản phẩm giữa checkout và mini cart qua localStorage ---
function getSelectedCartItemsFromStorage() {
    try {
        return JSON.parse(localStorage.getItem('checkout_selected')) || [];
    } catch (e) { return []; }
}
function saveSelectedCartItemsToStorage(selected) {
    localStorage.setItem('checkout_selected', JSON.stringify(selected));
}
function syncCheckoutCheckboxesWithStorage() {
    const selected = getSelectedCartItemsFromStorage();
    document.querySelectorAll('.checkout-cart-item').forEach(function(item) {
        const pid = item.getAttribute('data-pid');
        const color = item.getAttribute('data-color');
        const size = item.getAttribute('data-size');
        const checkbox = item.querySelector('.checkout-item-select');
        if (!checkbox) return;
        const found = selected.find(x => x.pid == pid && x.color == color && x.size == size);
        checkbox.checked = !!found;
    });
}
function updateSelectedCheckoutItemsFromUI() {
    const selected = getSelectedCartItemsFromStorage();
    document.querySelectorAll('.checkout-cart-item').forEach(function(item) {
        const checkbox = item.querySelector('.checkout-item-select');
        if (!checkbox) return;
        const pid = item.getAttribute('data-pid');
        const color = item.getAttribute('data-color');
        const size = item.getAttribute('data-size');
        const qty = parseInt(item.querySelector('.qty-input')?.value) || 1;
        const idx = selected.findIndex(x => x.pid == pid && x.color == color && x.size == size);
        if (checkbox.checked) {
            if (idx === -1) selected.push({ pid, color, size, qty });
            else selected[idx].qty = qty;
        } else {
            if (idx !== -1) selected.splice(idx, 1);
        }
    });
    saveSelectedCartItemsToStorage(selected);
}
document.addEventListener('DOMContentLoaded', function() {
    syncCheckoutCheckboxesWithStorage();
    document.querySelectorAll('.checkout-item-select').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateSelectedCheckoutItemsFromUI();
            if (typeof updateCheckoutTotals === 'function') updateCheckoutTotals();
        });
    });
    document.querySelectorAll('.qty-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTimeout(function() {
                updateSelectedCheckoutItemsFromUI();
                if (typeof updateCheckoutTotals === 'function') updateCheckoutTotals();
            }, 20);
        });
    });
});

// Xử lý tự động chuyển sang chọn phương thức thanh toán khi nhập đủ thông tin và chọn tỉnh
document.addEventListener('DOMContentLoaded', function () {
    // Chỉ áp dụng trên trang checkout
    if (window.location.pathname.includes('checkout.php')) {
        const form = document.getElementById('checkoutForm');
        const paymentSection = document.getElementById('paymentMethodSection');
        const provinceSelect = document.getElementById('provinceSelect');
        const fullname = form.querySelector('input[name="fullname"]');
        const email = form.querySelector('input[name="email"]');
        const phone = form.querySelector('input[name="phone"]');
        const address = form.querySelector('input[name="address"]');

        function isFormFilled() {
            return fullname.value.trim() && email.value.trim() && phone.value.trim() && address.value.trim() && provinceSelect.value;
        }

        function tryShowPaymentSection() {
            if (isFormFilled()) {
                paymentSection.style.display = 'block';
            } else {
                paymentSection.style.display = 'none';
            }
        }

        // Lắng nghe thay đổi trên các trường
        [fullname, email, phone, address, provinceSelect, districtSelect, wardSelect].forEach(function (el) {
            el.addEventListener('input', tryShowPaymentSection);
            el.addEventListener('change', tryShowPaymentSection);
        });
        // Gọi thử khi load lại (autofill)
        tryShowPaymentSection();
    }
});

// 1. Ẩn nút Continue to Payment
const continueBtn = document.querySelector('#checkoutForm button[type="submit"]');
if (continueBtn) continueBtn.style.display = 'none';

// 2. Chỉ hiện phương thức thanh toán khi đã chọn xã (ward), cho phép sửa lại địa chỉ
// Khi sửa địa chỉ, block phương thức thanh toán sẽ tự động ẩn đi, chỉ hiện lại khi đủ thông tin

document.addEventListener('DOMContentLoaded', function () {
    if (window.location.pathname.includes('checkout.php')) {
        const form = document.getElementById('checkoutForm');
        const paymentSection = document.getElementById('paymentMethodSection');
        const fullname = form.querySelector('input[name="fullname"]');
        const email = form.querySelector('input[name="email"]');
        const phone = form.querySelector('input[name="phone"]');
        const address = form.querySelector('input[name="address"]');
        const provinceSelect = document.getElementById('provinceSelect');
        const districtSelect = document.getElementById('districtSelect');
        const wardSelect = document.getElementById('wardSelect');

        function isFormFilled() {
            return fullname.value.trim() && email.value.trim() && phone.value.trim() && address.value.trim() && provinceSelect.value && districtSelect.value && wardSelect.value;
        }

        function tryShowPaymentSection() {
            if (isFormFilled()) {
                paymentSection.style.display = 'block';
            } else {
                paymentSection.style.display = 'none';
            }
        }

        [fullname, email, phone, address, provinceSelect, districtSelect, wardSelect].forEach(function (el) {
            el.addEventListener('input', tryShowPaymentSection);
            el.addEventListener('change', tryShowPaymentSection);
        });
        // Gọi thử khi load lại (autofill)
        tryShowPaymentSection();
    }
});

window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('step') === 'payment') {
        // 1. Autofill lại form từ localStorage nếu có
        const saved = localStorage.getItem('checkoutFormData');
        if (saved) {
            try {
                const data = JSON.parse(saved);
                const form = document.getElementById('checkoutForm');
                if (form && data) {
                    if (form.fullname) form.fullname.value = data.fullname || '';
                    if (form.email) form.email.value = data.email || '';
                    if (form.phone) form.phone.value = data.phone || '';
                    if (form.address) form.address.value = data.address || '';
                    if (form.province) form.province.value = data.province || '';
                    if (form.district) form.district.value = data.district || '';
                    if (form.ward) form.ward.value = data.ward || '';
                }
            } catch(e) {}
        }
        // 2. Chuyển sang bước payment
        if (typeof setCheckoutStep === 'function') {
            setCheckoutStep('payment');
        }
        // 3. Ẩn form shipping, hiện payment method
        var checkoutForm = document.getElementById('checkoutForm');
        var paymentSection = document.getElementById('paymentMethodSection');
        if (checkoutForm) checkoutForm.style.display = 'none';
        if (paymentSection) paymentSection.style.display = '';
        // 4. Scroll đến payment
        if (paymentSection) paymentSection.scrollIntoView({behavior: 'smooth'});
    }
});

// --- Chặn Submit Order nếu chưa quét mã QR thành công (Bank Transfer) ---
let qrScanned = false;

// Khi chọn phương thức thanh toán, reset trạng thái
const payBankRadio = document.getElementById('payBank');
const payCODRadio = document.getElementById('payCOD');
if (payBankRadio) payBankRadio.addEventListener('change', function() { qrScanned = false; });
if (payCODRadio) payCODRadio.addEventListener('change', function() { qrScanned = true; });

// Khi quét mã QR thành công (giả lập: khi QR code hiển thị)
document.getElementById('bankSelect').addEventListener('change', function() {
    const bank = this.value;
    if (bank) {
        // Khi QR code đã load xong, cho phép submit
        setTimeout(function() {
            qrScanned = true;
            // TỰ ĐỘNG SUBMIT ORDER nếu đã quét QR thành công
            // Có thể thêm điều kiện xác thực thanh toán thực tế ở đây nếu có webhook hoặc polling
            // Ở đây giả lập: sau khi chọn bank và QR đã hiện, tự động submit
            const payBank = document.getElementById('payBank');
            if (payBank && payBank.checked) {
                document.getElementById('confirmOrderBtn').click();
            }
        }, 5000); // Đợi 5s cho chắc chắn QR đã hiện
    } else {
        qrScanned = false;
    }
});

// Chặn submit nếu chưa quét mã QR (Bank)
document.getElementById('confirmOrderBtn').addEventListener('click', function(e) {
    const payBank = document.getElementById('payBank');
    const bankSelect = document.getElementById('bankSelect');
    if (payBank && payBank.checked) {
        if (!bankSelect.value) {
            e.preventDefault();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: '<span style="color:#fff;font-weight:500;">Please select a bank to pay!</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
            return false;
        }
        if (!qrScanned) {
            e.preventDefault();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: '<span style="color:#fff;font-weight:500;">Please scan the QR code to pay before ordering.!</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
            return false;
        }
    }
});