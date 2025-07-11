function showCartToast(type, product) {
    if (!product) return;

    const { image_url, name, price } = product;
    const formattedPrice = price.toLocaleString('vi-VN') + '₫';

    let htmlContent = '';

    if (type === 'remove') {
        htmlContent = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="${image_url}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;">
            <div style="text-align: left;">
                <div style="font-size: 16px; font-weight: 600;">${name}</div>
                <div style="color: #e74c3c; font-weight: bold; font-size: 16px;">${formattedPrice}</div>
                <div style="color: #27ae60; margin-top: 4px; font-size: 14px;">Đã xoá khỏi giỏ hàng</div>
            </div>
            <ion-icon name="trash-outline" style="font-size: 24px; color: #e74c3c;"></ion-icon>
        </div>`;
    } else if (type === 'add') {
        htmlContent = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="${image_url}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;">
            <div style="text-align: left;">
                <div style="font-size: 16px; font-weight: 600;">${name}</div>
                <div style="color: #e74c3c; font-weight: bold; font-size: 16px;">${formattedPrice}</div>
                <div style="color: #27ae60; margin-top: 4px; font-size: 14px;">Đã thêm vào giỏ hàng</div>
            </div>
            <ion-icon name="checkmark-circle-outline" style="font-size: 24px; color: #27ae60;"></ion-icon>
        </div>`;
    }

    if (htmlContent) {
        Swal.fire({
            html: htmlContent,
            showConfirmButton: false,
            timer: 1600,
            width: 500,
            customClass: {
                popup: 'swal2-toast-custom'
            }
        });
    }
}
