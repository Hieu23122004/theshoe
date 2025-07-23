// Auto-hide alerts after 3 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (alert && alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 1000); // 1 second for smoother transition
    });
});

function editUser(user) {
    console.log('Edit user called:', user); // Debug log

    // Reset form first
    resetForm();

    // Change form to edit mode
    document.getElementById('user_action').value = 'edit_user';
    document.getElementById('edit_user_id').value = user.user_id;

    // Fill form fields with proper null checks
    document.getElementById('fullname').value = user.fullname || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('address').value = user.address || '';
    document.getElementById('role').value = user.role || 'customer';

    // Hide password field for editing
    const passwordField = document.getElementById('password_field');
    passwordField.style.display = 'none';
    document.getElementById('password').required = false;

    // Change button text to indicate edit mode
    const submitBtn = document.querySelector('#userForm button[type="submit"]');
    submitBtn.textContent = 'Update User';

    // Show cancel button or change reset button text
    const resetBtn = document.querySelector('#userForm button[onclick="resetForm()"]');
    resetBtn.textContent = 'Cancel Edit';

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    // Reset the form
    document.getElementById('userForm').reset();
    
    // Reset hidden fields
    document.getElementById('user_action').value = 'create_user';
    document.getElementById('edit_user_id').value = '';

    // Show password field for creating new user
    const passwordField = document.getElementById('password_field');
    passwordField.style.display = 'block';
    document.getElementById('password').required = true;

    // Change button text back to create mode
    const submitBtn = document.querySelector('#userForm button[type="submit"]');
    submitBtn.textContent = 'Save User';

    // Reset the cancel/reset button text
    const resetBtn = document.querySelector('#userForm button[onclick="resetForm()"]');
    resetBtn.textContent = 'Reset';
}

function resetPassword(userId, userName) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_user_name').textContent = userName;

    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function deleteUserWithAlert(userName) {
    // Show SweetAlert2 toast after form submission (handled in PHP)
    // Just allow form submission, notification will be handled by PHP after reload
    return true;
}