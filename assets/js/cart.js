function showCartToast(type, product) {
        if (type === 'remove' && product) {
            Swal.fire({
                html: `
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
        <div style="font-size: 20px; font-weight: 700;">Successfully removed from cart!</div>
        <ion-icon name="checkmark-circle-outline" style="font-size: 30px; color: #27ae60;"></ion-icon>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <img src="${product.image_url}" style="width:100px;height:100px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
        <div style="text-align:left;">
            <div style="font-size:16px;font-weight:600;margin-bottom:2px;">${product.name}</div>
            <div style="color:#e74c3c;font-weight:700;font-size:18px;">${product.price.toLocaleString('vi-VN')}₫</div>
        </div>
    </div>
            `,
                showConfirmButton: false,
                timer: 1600,
                width: 500,
                customClass: {
                    popup: 'swal2-toast-custom'
                }
            });
        } else if (type === 'add' && product) {
            Swal.fire({
                html: `
                <div style=\"display: flex; align-items: center; justify-content: center; flex-direction: column;\">
                    <img src=\"${product.image_url}\" style=\"width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #eee;margin-bottom:10px;\">
                    <div style=\"font-size:22px;font-weight:700;margin-bottom:6px;\">${product.name}</div>
                    <div style=\"color:#e74c3c;font-weight:700;font-size:18px;margin-bottom:6px;\">${product.price.toLocaleString('vi-VN')}₫</div>
                    <div style=\"color:#27ae60;font-size:18px;margin-top:2px;\">Successfully removed from cart!</div>
                </div>
            `,
                showConfirmButton: false,
                timer: 1600,
                width: 500,
                customClass: {
                    popup: 'swal2-toast-custom'
                }
            });
        }
    }


    function removeCartItem(pid, color, size, product) {
        fetch('/public/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    product_id: pid,
                    color: color,
                    size: size
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.cart-item[data-pid="' + pid + '"' +
                        '][data-color="' + color + '"]' +
                        '[data-size="' + size + '"]')?.remove();
                    showCartToast('remove', product);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    Swal.fire('Error', data.message || 'Could not remove item.', 'error');
                }
            });
    }

    document.querySelectorAll('.cart-item-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const pid = this.getAttribute('data-pid');
            const color = this.getAttribute('data-color');
            const size = this.getAttribute('data-size');
            const product = {
                image_url: this.getAttribute('data-image'),
                name: this.getAttribute('data-name'),
                price: parseInt(this.getAttribute('data-price'))
            };
            removeCartItem(pid, color, size, product);
        });
    });


    function updateCartQty(pid, color, size, newQty) {
        fetch('/public/update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    product_id: pid,
                    color: color,
                    size: size,
                    quantity: newQty
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Nếu số lượng thực tế trả về nhỏ hơn số lượng yêu cầu, báo toast và không reload
                    if (data.quantity < newQty && typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'This is the maximum quantity available in stock.',
                            showConfirmButton: false,
                            timer: 2200,
                            background: '#222',
                            color: '#fff',
                            customClass: { popup: 'swal2-toast-custom' }
                        });
                        // Cập nhật lại input số lượng trên giao diện
                        document.querySelectorAll('.cart-item').forEach(function(item) {
                            if (
                                item.getAttribute('data-pid') == pid &&
                                item.getAttribute('data-color') == color &&
                                item.getAttribute('data-size') == size
                            ) {
                                let input = item.querySelector('.qty-input');
                                if (input) input.value = data.quantity;
                            }
                        });
                        // Cập nhật lại tổng tiền nếu sản phẩm đang được tích
                        updateCartTotalBySelection && updateCartTotalBySelection();
                        return;
                    }
                    // Không reload lại trang, chỉ cập nhật số lượng trên DOM và tổng tiền
                    document.querySelectorAll('.cart-item').forEach(function(item) {
                        if (
                            item.getAttribute('data-pid') == pid &&
                            item.getAttribute('data-color') == color &&
                            item.getAttribute('data-size') == size
                        ) {
                            let input = item.querySelector('.qty-input');
                            if (input) input.value = data.quantity;
                        }
                    });
                    // Cập nhật lại tổng tiền nếu sản phẩm đang được tích
                    updateCartTotalBySelection && updateCartTotalBySelection();
                } else {
                    Swal.fire('Error', data.message || 'Could not update quantity.', 'error');
                }
            });
    }

    document.querySelectorAll('.qty-increase').forEach(btn => {
        btn.addEventListener('click', function() {
            const pid = this.getAttribute('data-pid');
            const color = this.getAttribute('data-color');
            const size = this.getAttribute('data-size');
            const input = this.parentElement.querySelector('.qty-input');
            let qty = parseInt(input.value) || 1;
            updateCartQty(pid, color, size, qty + 1);
        });
    });
    document.querySelectorAll('.qty-decrease').forEach(btn => {
        btn.addEventListener('click', function() {
            const pid = this.getAttribute('data-pid');
            const color = this.getAttribute('data-color');
            const size = this.getAttribute('data-size');
            const input = this.parentElement.querySelector('.qty-input');
            let qty = parseInt(input.value) || 1;
            if (qty > 1) updateCartQty(pid, color, size, qty - 1);
        });
    });


    document.querySelector('.cart-summary-btn').addEventListener('click', function() {
        const selected = [];
        document.querySelectorAll('.cart-item').forEach(function(item) {
            const checkbox = item.querySelector('.cart-item-select');
            if (checkbox && checkbox.checked) {
                selected.push({
                    pid: item.getAttribute('data-pid'),
                    color: item.getAttribute('data-color'),
                    size: item.getAttribute('data-size'),
                    qty: parseInt(item.querySelector('.qty-input')?.value) || 1
                });
            }
        });
        if (selected.length === 0) {
            // Hiển thị toast notification bằng SweetAlert2
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: '<span style="color:#fff;font-weight:500;">Vui lòng chọn ít nhất một sản phẩm để thanh toán!</span>',
                background: '#222',
                color: '#fff',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                customClass: { popup: 'swal2-toast-custom' }
            });
            return;
        }
        localStorage.setItem('checkout_selected', JSON.stringify(selected));
        window.location.href = '/pages/checkout.php';
    });

     // Tính tổng tiền chỉ các sản phẩm đã tích
    function updateCartTotalBySelection() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(function(item) {
            const checkbox = item.querySelector('.cart-item-select');
            if (checkbox && checkbox.checked) {
                const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace(/[^\d]/g, ''));
                const qty = parseInt(item.querySelector('.qty-input').value);
                if (!isNaN(price) && !isNaN(qty)) total += price * qty;
            }
        });
        const totalEl = document.querySelector('.cart-summary-total');
        if (totalEl) {
            if (total === 0) {
                totalEl.textContent = '0₫';
            } else {
                totalEl.textContent = total.toLocaleString('vi-VN') + '₫';
            }
        }
    }

    // Gắn lại sự kiện khi DOM đã sẵn sàng
    function attachCartCheckboxEvents() {
        document.querySelectorAll('.cart-item-select').forEach(function(checkbox) {
            checkbox.removeEventListener('change', updateCartTotalBySelection);
            checkbox.addEventListener('change', updateCartTotalBySelection);
            checkbox.removeEventListener('input', updateCartTotalBySelection);
            checkbox.addEventListener('input', updateCartTotalBySelection);
        });
    }
    attachCartCheckboxEvents();

    // Khi tăng/giảm số lượng, nếu sản phẩm đang được tích thì cũng cập nhật lại tổng tiền
    document.querySelectorAll('.qty-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTimeout(updateCartTotalBySelection, 10);
        });
    });

    // Mặc định tổng tiền là 0 khi chưa tích sản phẩm nào
    updateCartTotalBySelection();

    // Xử lý sự kiện click cho nút CHECKOUT NOW
    document.querySelector('.cart-summary-btn').addEventListener('click', function() {
        // Lấy danh sách các sản phẩm đã chọn
        const selectedItems = [];
        document.querySelectorAll('.cart-item').forEach(function(item) {
            const checkbox = item.querySelector('.cart-item-select');
            if (checkbox && checkbox.checked) {
                const pid = item.getAttribute('data-pid');
                const color = item.getAttribute('data-color');
                const size = item.getAttribute('data-size');
                const qty = parseInt(item.querySelector('.qty-input').value);
                selectedItems.push({ pid, color, size, qty });
            }
        });

        if (selectedItems.length === 0) {
            // Nếu không có sản phẩm nào được chọn, hiển thị thông báo
            Swal.fire({
                icon: 'warning',
                title: 'Chưa có sản phẩm nào được chọn',
                text: 'Vui lòng chọn ít nhất một sản phẩm trong giỏ hàng để tiếp tục',
                confirmButtonText: 'Đồng ý'
            });
            return;
        }

        // Chuyển hướng đến trang thanh toán với danh sách sản phẩm đã chọn
        const checkoutUrl = '/pages/checkout.php';
        const params = new URLSearchParams();
        selectedItems.forEach(item => {
            params.append('product_id[]', item.pid);
            params.append('color[]', item.color);
            params.append('size[]', item.size);
            params.append('quantity[]', item.qty);
        });
        window.location.href = `${checkoutUrl}?${params.toString()}`;
    });

// --- Đồng bộ checkbox sản phẩm giữa mini cart và checkout qua localStorage ---
function getSelectedCartItemsFromStorage() {
    try {
        return JSON.parse(localStorage.getItem('checkout_selected')) || [];
    } catch (e) { return []; }
}
function saveSelectedCartItemsToStorage(selected) {
    localStorage.setItem('checkout_selected', JSON.stringify(selected));
}
function syncCartCheckboxesWithStorage() {
    const selected = getSelectedCartItemsFromStorage();
    document.querySelectorAll('.cart-item').forEach(function(item) {
        const pid = item.getAttribute('data-pid');
        const color = item.getAttribute('data-color');
        const size = item.getAttribute('data-size');
        const checkbox = item.querySelector('.cart-item-select');
        if (!checkbox) return;
        const found = selected.find(x => x.pid == pid && x.color == color && x.size == size);
        checkbox.checked = !!found;
    });
}
function updateSelectedCartItemsFromUI() {
    const selected = [];
    document.querySelectorAll('.cart-item').forEach(function(item) {
        const checkbox = item.querySelector('.cart-item-select');
        if (checkbox && checkbox.checked) {
            selected.push({
                pid: item.getAttribute('data-pid'),
                color: item.getAttribute('data-color'),
                size: item.getAttribute('data-size'),
                qty: parseInt(item.querySelector('.qty-input')?.value) || 1
            });
        }
    });
    saveSelectedCartItemsToStorage(selected);
}
document.addEventListener('DOMContentLoaded', function() {
    syncCartCheckboxesWithStorage();
    document.querySelectorAll('.cart-item-select').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateSelectedCartItemsFromUI();
            updateCartTotalBySelection && updateCartTotalBySelection();
        });
    });
    // Khi tăng/giảm số lượng, nếu sản phẩm đang được tích thì cũng cập nhật storage
    document.querySelectorAll('.qty-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTimeout(function() {
                updateSelectedCartItemsFromUI();
                updateCartTotalBySelection && updateCartTotalBySelection();
            }, 20);
        });
    });
});
    // Khi click CHECKOUT NOW, lấy từ localStorage, không ghi đè
    document.querySelector('.cart-summary-btn').addEventListener('click', function() {
        // Lấy danh sách các sản phẩm đã chọn từ localStorage
        const selectedItems = getSelectedCartItemsFromStorage();

        if (selectedItems.length === 0) {
            // Nếu không có sản phẩm nào được chọn, hiển thị thông báo
            Swal.fire({
                icon: 'warning',
                title: 'Chưa có sản phẩm nào được chọn',
                text: 'Vui lòng chọn ít nhất một sản phẩm trong giỏ hàng để tiếp tục',
                confirmButtonText: 'Đồng ý'
            });
            return;
        }

        // Chuyển hướng đến trang thanh toán với danh sách sản phẩm đã chọn
        const checkoutUrl = '/pages/checkout.php';
        const params = new URLSearchParams();
        selectedItems.forEach(item => {
            params.append('product_id[]', item.pid);
            params.append('color[]', item.color);
            params.append('size[]', item.size);
            params.append('quantity[]', item.qty);
        });
        window.location.href = `${checkoutUrl}?${params.toString()}`;
    });

    // Tính lại tổng tiền khi thay đổi số lượng
// Tính lại tổng tiền chỉ các sản phẩm đã tích
function updateCartTotals() {
    let grandTotal = 0;
    document.querySelectorAll('.cart-item').forEach(function(item) {
        const checkbox = item.querySelector('.cart-item-select');
        const priceText = item.querySelector('.cart-item-price').textContent.replace(/[^\d]/g, '');
        const price = parseInt(priceText) || 0;
        const qty = parseInt(item.querySelector('.qty-input').value) || 1;
        const lineTotal = price * qty;
        // Luôn hiển thị tổng từng dòng đúng số lượng
        item.querySelector('.cart-item-total').textContent = lineTotal.toLocaleString('vi-VN') + '₫';
        // Chỉ cộng vào tổng nếu sản phẩm được tích
        if (checkbox && checkbox.checked) {
            grandTotal += lineTotal;
        }
    });
    document.querySelector('.cart-summary-total').textContent = grandTotal.toLocaleString('vi-VN') + '₫';
}

// Xử lý tăng/giảm số lượng
function changeCartQty(btn, delta) {
    const item = btn.closest('.cart-item');
    const qtyInput = item.querySelector('.qty-input');
    let qty = parseInt(qtyInput.value) || 1;
    qty += delta;
    if (qty < 1) qty = 1;
    qtyInput.value = qty;
    // Gửi AJAX cập nhật server nếu cần
    const pid = btn.getAttribute('data-pid');
    const color = btn.getAttribute('data-color');
    const size = btn.getAttribute('data-size');
    fetch('/public/update_cart_quantity.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${encodeURIComponent(pid)}&color=${encodeURIComponent(color)}&size=${encodeURIComponent(size)}&quantity=${qty}`
    }).then(r => r.json()).then(data => {
        // Có thể xử lý thông báo nếu cần
        updateCartTotals();
    });
    updateCartTotals();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.qty-increase').forEach(function(btn) {
        btn.addEventListener('click', function() {
            changeCartQty(btn, 1);
        });
    });
    document.querySelectorAll('.qty-decrease').forEach(function(btn) {
        btn.addEventListener('click', function() {
            changeCartQty(btn, -1);
        });
    });
    updateCartTotals();
});

document.querySelector('.cart-summary-btn').addEventListener('click', function() {
    const selected = [];
    document.querySelectorAll('.cart-item').forEach(function(item) {
        const checkbox = item.querySelector('.cart-item-select');
        if (checkbox && checkbox.checked) {
            selected.push({
                pid: item.getAttribute('data-pid'),
                color: item.getAttribute('data-color'),
                size: item.getAttribute('data-size'),
                qty: parseInt(item.querySelector('.qty-input')?.value) || 1
            });
        }
    });
    if (selected.length === 0) {
        // Hiển thị toast notification bằng SweetAlert2 góc trên phải
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: '<span style="color:#fff;font-weight:500; font-size:15px;">You need to select at least one product to proceed to checkout!</span>',
            background: '#222',
            color: '#fff',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            customClass: { popup: 'swal2-toast-custom' }
        });
        return;
    }
    localStorage.setItem('checkout_selected', JSON.stringify(selected));
    window.location.href = '/pages/checkout.php';
});