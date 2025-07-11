document.addEventListener('DOMContentLoaded', function () {
    const newsCards = Array.from(document.querySelectorAll('#newsRow .news-card-item'));
    const total = newsCards.length;
    const showCount = 4;
    let start = 0;
    let isAnimating = false;

    function showGroup(idx) {
        newsCards.forEach((card, i) => {
            card.classList.remove('group-slide-out', 'group-slide-in', 'group-showing');
            card.style.display = 'none';
        });
        for (let i = 0; i < showCount; ++i) {
            const cardIdx = (idx + i) % total;
            newsCards[cardIdx].classList.add('group-showing');
            newsCards[cardIdx].style.display = 'flex';
        }
    }

    function slideGroup() {
        if (isAnimating || total <= showCount) return;
        isAnimating = true;
        // 4 card hiện tại trượt sang trái
        for (let i = 0; i < showCount; ++i) {
            const cardIdx = (start + i) % total;
            newsCards[cardIdx].classList.remove('group-showing');
            newsCards[cardIdx].classList.add('group-slide-out');
        }
        // 4 card tiếp theo chuẩn bị vào từ phải
        for (let i = 0; i < showCount; ++i) {
            const nextIdx = (start + showCount + i) % total;
            newsCards[nextIdx].style.display = 'flex';
            newsCards[nextIdx].classList.add('group-slide-in');
        }
        setTimeout(() => {
            // Ẩn hoàn toàn 4 card vừa trượt ra
            for (let i = 0; i < showCount; ++i) {
                const cardIdx = (start + i) % total;
                newsCards[cardIdx].classList.remove('group-slide-out');
                newsCards[cardIdx].style.display = 'none';
            }
            // 4 card mới trượt vào vị trí
            for (let i = 0; i < showCount; ++i) {
                const nextIdx = (start + showCount + i) % total;
                newsCards[nextIdx].classList.remove('group-slide-in');
                newsCards[nextIdx].classList.add('group-showing');
            }
            start = (start + showCount) % total;
            isAnimating = false;
        }, 800); // Thời gian khớp với transition CSS
    }

    showGroup(start);
});
