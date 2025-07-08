<!-- Floating Contact Button -->
<style>
    .floating-contact-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s;
        transform: translateX(120%);
        opacity: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .floating-contact-container.visible {
        transform: translateX(0);
        opacity: 1;
    }

    .floating-contact-icons {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        margin-bottom: 10px;
        transition: opacity 0.3s, visibility 0.3s;
        opacity: 0;
        visibility: hidden;
    }

    .floating-contact-icons.show {
        opacity: 1;
        visibility: visible;
    }

    .floating-contact-btn {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #fff !important;
        color: #111 !important;
        border: 1px solid #ddd;
        outline: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .floating-contact-btn.close {
        background: #fff !important;
    }

    .floating-contact-icon {
        width: 48px;
        height: 48px;
        margin-bottom: 10px;
        border-radius: 50%;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #333;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        transition: background 0.2s;
    }

    .floating-contact-icon:hover {
        background: #f0f0f0;
    }

    .pulse-red {
        position: relative;
        z-index: 1;
    }

    .pulse-red::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 56px;
        height: 56px;
        background: #ff9999;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        z-index: -1;
        animation: pulse-red-effect 1.2s infinite;
    }

    @keyframes pulse-red-effect {
        0% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.7;
        }

        70% {
            transform: translate(-50%, -50%) scale(1.4);
            opacity: 0;
        }

        100% {
            transform: translate(-50%, -50%) scale(1.4);
            opacity: 0;
        }
    }

    .profile-icon-white,
    .profile-icon-blue,
    .profile-icon-x {
        color: #111 !important;
        /* icon màu đen */
        text-shadow: none;
    }

    #profileIcon {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        width: 56px;
        height: 56px;
        padding: 0;
        margin: 0;
    }

    #profileIcon img {
        width: 44px;
        height: 44px;
        object-fit: contain;
        margin: 0;
        display: block;
    }
</style>

<div class="floating-contact-container">
    <div id="floatingContactIcons" class="floating-contact-icons">
        <!-- Zalo -->
        <a href="https://zalo.me/0382606824" target="_blank" class="floating-contact-icon" title="Zalo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Icon_of_Zalo.svg/2048px-Icon_of_Zalo.svg.png" alt="Zalo" style="width:30px;height:30px;object-fit:contain;">
        </a>
        <!-- Facebook Messenger -->
        <a href="https://m.me/651659121367246" target="_blank" class="floating-contact-icon" title="Facebook Messenger">
            <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Facebook_Logo_%282019%29.png" alt="Messenger" style="width:30px;height:30px;object-fit:contain;">
        </a>
        <!-- Web Chat -->
        <a href="#" class="floating-contact-icon" title="Chat trực tiếp trên web" onclick="openWebChat()">
            <img src="https://cdn-icons-png.flaticon.com/512/8272/8272767.png" alt="Web Chat" style="width:30px;height:30px;object-fit:contain;">
        </a>
    </div>
    <button id="floatingContactBtn" class="floating-contact-btn" aria-label="Liên hệ" style="align-items: flex-start; margin-bottom: 50px;">
        <span id="profileIcon" class="pulse-red profile-icon-white" style="font-size:30px; margin-bottom:0; margin-top:3px;"></span>
    </button>
</div>

<script>
    const btn = document.getElementById('floatingContactBtn');
    const icons = document.getElementById('floatingContactIcons');
    const profileIcon = document.getElementById('profileIcon');
    const floatingContainer = document.querySelector('.floating-contact-container');
    let open = false;
    let scrollTimeout;
    let iconTimeout;
    let HIDE_DELAY = 8000; // 3 giây
    let ICON_AUTO_CLOSE_DELAY = 8000; // 5 giây

    btn.onclick = function() {
        // Nếu đang mở thì đóng ngay, không set timeout
        if (open) {
            open = false;
            icons.classList.remove('show');
            btn.classList.remove('close');
            clearTimeout(iconTimeout);
            // ...reset profile icon như cũ...
            profileIcon.innerHTML = '';
            const img = document.createElement('img');
            img.src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
            img.alt = 'Profile';
            img.style.width = '50px';
            img.style.height = '50px';
            img.style.objectFit = 'contain';
            img.style.marginBottom = '5px';
            profileIcon.appendChild(img);
            profileIcon.classList.remove('profile-icon-x');
            profileIcon.classList.add('profile-icon-white', 'pulse-red');
            profileIcon.style.fontSize = '30px';
            icons.querySelectorAll('.floating-contact-icon').forEach(function(icon) {
                icon.classList.remove('pulse-red');
            });
            return;
        }
        // Nếu đang đóng thì mở ra và set timeout tự đóng sau 5s
        open = true;
        icons.classList.add('show');
        btn.classList.add('close');
        const contactIcons = icons.querySelectorAll('.floating-contact-icon');
        profileIcon.classList.remove('pulse-red');
        profileIcon.classList.remove('profile-icon-white');
        profileIcon.classList.add('profile-icon-x');
        contactIcons.forEach(function(icon) {
            icon.classList.add('pulse-red');
        });
        clearTimeout(iconTimeout);
        iconTimeout = setTimeout(() => {
            open = false;
            icons.classList.remove('show');
            btn.classList.remove('close');
            profileIcon.innerHTML = '';
            const img = document.createElement('img');
            img.src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
            img.alt = 'Profile';
            img.style.width = '50px';
            img.style.height = '50px';
            img.style.objectFit = 'contain';
            img.style.marginBottom = '5px';
            profileIcon.appendChild(img);
            profileIcon.classList.remove('profile-icon-x');
            profileIcon.classList.add('profile-icon-white', 'pulse-red');
            profileIcon.style.fontSize = '30px';
            contactIcons.forEach(function(icon) {
                icon.classList.remove('pulse-red');
            });
        }, ICON_AUTO_CLOSE_DELAY);
    };

    function openWebChat() {
        alert('Mở chat trực tiếp trên web!');
        // Thay bằng code mở popup chat thực tế nếu có
    }

    function showFloatingContact() {
        floatingContainer.classList.add('visible');
        // Khi hiện ra chỉ hiện nút, không hiện các icon liên hệ
        icons.classList.remove('show');
        btn.classList.remove('close');
        open = false;
    }

    function hideFloatingContact() {
        floatingContainer.classList.remove('visible');
        icons.classList.remove('show');
        btn.classList.remove('close');
        open = false;
    }

    function triggerShowFloatingContact() {
        // Nếu đang mở icon thì không tự đóng khi di chuyển chuột
        if (open) return;
        showFloatingContact();
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(hideFloatingContact, HIDE_DELAY);
    }

    window.addEventListener('scroll', triggerShowFloatingContact);
    window.addEventListener('wheel', triggerShowFloatingContact);
    window.addEventListener('mousemove', triggerShowFloatingContact);

    window.addEventListener('DOMContentLoaded', function() {
        // Hiển thị ảnh profile ngay từ đầu
        profileIcon.innerHTML = '';
        const img = document.createElement('img');
        img.src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
        img.alt = 'Profile';
        img.style.width = '50px';
        img.style.height = '50px';
        img.style.objectFit = 'contain';
        img.style.marginBottom = '5px';
        profileIcon.appendChild(img);
        profileIcon.classList.add('pulse-red'); // Đảm bảo có hiệu ứng tỏa ra ban đầu

        // Bỏ hiệu ứng tỏa ra khỏi các icon liên hệ khi mới load
        document.querySelectorAll('.floating-contact-icon').forEach(function(icon) {
            icon.classList.remove('pulse-red');
        });

        hideFloatingContact();
    });
</script>