function editCategory(cat) {
    document.getElementById('edit_id').value = cat.category_id;
    document.getElementById('name').value = cat.name;
    document.getElementById('parent_id').value = cat.parent_id ?? '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('categoryForm').reset();
    document.getElementById('edit_id').value = '';
}

document.addEventListener('DOMContentLoaded', function () {
    const searchParams = new URLSearchParams(window.location.search);
    const msg = searchParams.get('msg');

    // Tự đóng alert sau 2s
    if (msg) {
        setTimeout(() => {
            const alertEl = document.querySelector('.alert');
            if (alertEl) {
                const alert = new bootstrap.Alert(alertEl);
                alert.close();
            }
        }, 0);
    }

    // Reload lại trang sau 2.5s nếu có msg
    if (msg) {
        setTimeout(() => {
            window.location.href = window.location.pathname;
        }, 1000);
    }
});