let lastActivity = Date.now();
function resetTimer() {
    lastActivity = Date.now();
}
['click', 'mousemove', 'keydown', 'scroll'].forEach(evt =>
    document.addEventListener(evt, resetTimer)
);
setInterval(function() {
    // Kiểm tra nếu đã có popup thì không hiện lại nữa
    if (
        Date.now() - lastActivity > 15 * 60 * 1000 && // 1 phút (hoặc đổi lại 15 * 60 * 1000 cho 15 phút)
        !document.querySelector('.swal2-container')
    ) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                // Không dùng icon: 'warning ...', chỉ dùng icon trong html
                title: '',
                html: `
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 48px;"></i>
                        <div style="font-size:23px;font-weight:700;color:#222;margin-bottom:8px;">Session Expired</div>
                        <div style="font-size:17px;color:#555;margin-bottom:18px;">
                             Your session has expired due to inactivity.<br>
                            Click Continue to stay signed in.
                        </div>
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <button id="continueSessionBtn" class="btn btn-dark px-4 fw-bold d-flex align-items-center" style="font-size:17px;">
                                <i class="bi bi-arrow-repeat me-2"></i> Continue
                            </button>
                            <button id="goHomeBtn" class="btn btn-outline-secondary px-4 fw-bold d-flex align-items-center" style="font-size:17px;">
                                <i class="bi bi-box-arrow-left me-2"></i> Go to Home
                            </button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: false,
                width: 420,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    popup: 'rounded-4 shadow border-0 py-4'
                },
                didOpen: () => {
                    document.getElementById('continueSessionBtn').onclick = function() {
                        lastActivity = Date.now();
                        Swal.close();
                    };
                    document.getElementById('goHomeBtn').onclick = function() {
                        fetch('/public/logout.php', { method: 'POST' })
                            .finally(() => {
                                window.location.href = '/pages/home.php';
                            });
                    };
                }
            });
        } else {
            window.location.href = '/pages/login.php?timeout=1';
        }
    }
}, 5 * 1000); // kiểm tra mỗi 5 giây