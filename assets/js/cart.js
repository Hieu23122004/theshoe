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
                width: 380,
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
                        return;
                    }
                    location.reload();
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
        window.location.href = '/pages/checkout.php';
    });