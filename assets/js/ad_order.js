document.addEventListener('DOMContentLoaded', function() {
    // Xử lý cập nhật trạng thái
    document.querySelectorAll('.status-select').forEach(select => {
        const currentStatus = select.dataset.currentStatus;
        // Ẩn các option không hợp lệ dựa trên trạng thái hiện tại
        Array.from(select.options).forEach(option => {
            if (!isValidTransition(currentStatus, option.value)) {
                option.disabled = true;
                option.style.color = '#999';
                option.title = 'Cannot transition to this status';
            }
        });

        select.addEventListener('change', async function(e) {
            const orderId = e.target.dataset.orderId;
            const newStatus = e.target.value;
            const oldStatus = e.target.dataset.currentStatus;
            
            console.log(`Updating order ${orderId} from ${oldStatus} to ${newStatus}`);
            
            try {
                const response = await fetch('../public/update_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({orderId: parseInt(orderId), status: newStatus})
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response: ' + responseText);
                }
                
                console.log('Parsed response data:', result);
                
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Update failed');
                }
                
                e.target.dataset.currentStatus = newStatus;
                // Cập nhật lại disabled states cho các option
                Array.from(e.target.options).forEach(option => {
                    if (!isValidTransition(newStatus, option.value)) {
                        option.disabled = true;
                        option.style.color = '#999';
                        option.title = 'Cannot transition to this status';
                    } else {
                        option.disabled = false;
                        option.style.color = '';
                        option.title = '';
                    }
                });
                
                // Hiển thị thông báo thành công với trạng thái mới
                showStatusUpdateNotification(newStatus);
                
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Error updating status: ' + error.message);
                e.target.value = e.target.dataset.currentStatus; // Reset to original value
            }
        });
    });

    // Kiểm tra xem transition có hợp lệ không
    function isValidTransition(currentStatus, newStatus) {
        if (currentStatus === newStatus) return true;
        
        const validTransitions = {
            'Pending': ['Processing', 'Cancelled'],
            'Processing': ['Shipped', 'Cancelled'],
            'Shipped': ['Delivered'],
            'Delivered': [],
            'Cancelled': []
        };
        
        return validTransitions[currentStatus]?.includes(newStatus) ?? false;
    }

    // Hiển thị thông báo cập nhật trạng thái
    function showStatusUpdateNotification(newStatus) {
        const statusMessages = {
            'Processing': 'Order is being processed',
            'Shipped': 'Order has been shipped',
            'Delivered': 'Order has been delivered successfully',
            'Cancelled': 'Order has been cancelled'
        };

        // Redirect with message parameter
        const message = statusMessages[newStatus] || 'Status updated successfully';
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('message', message);
        window.location.href = currentUrl.toString();
    }
});