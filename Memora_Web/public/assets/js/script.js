document.addEventListener('DOMContentLoaded', () => {
    // Like Button Interaction
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon && (icon.classList.contains('bx-heart') || icon.classList.contains('bxs-heart'))) {
                this.classList.toggle('active');
                if (this.classList.contains('active')) {
                    icon.classList.remove('bx-heart');
                    icon.classList.add('bxs-heart');
                    this.style.transform = "scale(1.1)";
                    setTimeout(() => this.style.transform = "scale(1)", 150);
                } else {
                    icon.classList.remove('bxs-heart');
                    icon.classList.add('bx-heart');
                }
            }
        });
    });

    // Save/Bookmark Button Interaction
    const bookmarkBtns = document.querySelectorAll('.bx-bookmark, .bxs-bookmark');
    bookmarkBtns.forEach(icon => {
        icon.parentElement.addEventListener('click', function() {
            if (icon.classList.contains('bx-bookmark')) {
                icon.classList.remove('bx-bookmark');
                icon.classList.add('bxs-bookmark');
                icon.style.color = 'var(--primary-color)';
            } else {
                icon.classList.remove('bxs-bookmark');
                icon.classList.add('bx-bookmark');
                icon.style.color = '';
            }
        });
    });
});
